<?php

namespace Byte5;

use Byte5\Definitions\DefinitionInterface;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Safe\Exceptions\JsonException;
use Safe\Exceptions\OpensslException;
use Safe\Exceptions\UrlException;
use ZipArchive;

use function Safe\base64_decode;
use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\openssl_pkcs7_sign;
use function Safe\openssl_pkey_get_private;
use function Safe\openssl_x509_read;

class PassGenerator
{
    /**
     * The store with the pass ID certificate.
     */
    private string $certStore;

    /**
     * The password to unlock the certificate store.
     */
    private string $certStorePassword;

    /**
     * Path to the Apple Worldwide Developer Relations Intermediate Certificate.
     */
    private string $wwdrCertPath;

    /**
     * The JSON definition for the pass (pass.json).
     */
    private string $passJson;

    /**
     * All the assets (images) to be included on the pass.
     */
    private array $assets = [];

    /**
     * Filename for the pass. If provided, it'll be the pass_id with .pkpass
     * extension, otherwise a random name will be assigned.
     */
    private string $passFilename;

    /**
     * Relative path to the pass on its temp folder.
     */
    private string $passRelativePath;

    /**
     * Real path to the pass on its temp folder.
     */
    private string $passRealPath;

    /**
     * Some file names as defined by Apple.
     */
    private string $signatureFilename = 'signature';

    private string $manifestFilename = 'manifest.json';

    private string $passJsonFilename = 'pass.json';

    /**
     * Constructor.
     *
     * @param  bool|string  $passId  [optional] If given, it'll be used to name the pass file.
     * @param  bool  $replaceExistent  [optional] If true, it'll replace any existing pass with the same filename.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function __construct($passId = false, bool $replaceExistent = false)
    {
        // Set certificate
        $certPath = config('passgenerator.certificate_store_path');

        if (Storage::disk(config('passgenerator.storage_disk'))->exists($certPath)) {
            $this->certStore = Storage::disk(config('passgenerator.storage_disk'))->get($certPath);
        } else {
            throw new InvalidArgumentException(
                'No certificate found on '.$certPath
            );
        }

        // Set password
        $this->certStorePassword = config('passgenerator.certificate_store_password');

        // Set WWDR certificate
        $wwdrCertPath = config('passgenerator.wwdr_certificate_path');

        $validCert = false;
        if (Storage::disk(config('passgenerator.storage_disk'))->exists($wwdrCertPath)) {
            $validCert = true;
        }

        try {
            openssl_x509_read(Storage::disk(config('passgenerator.storage_disk'))->get($wwdrCertPath));
        } catch (OpensslException $e) {
            $validCert = false;
        }

        if ($validCert) {
            $this->wwdrCertPath = $wwdrCertPath;
        } else {
            $errorMsg = 'No valid intermediate certificate was found on '.$wwdrCertPath.PHP_EOL;
            $errorMsg .= 'The WWDR intermediate certificate must be on PEM format, ';
            $errorMsg .= 'the DER version can be found at https://www.apple.com/certificateauthority/ ';
            $errorMsg .= "But you'll need to export it into PEM.";

            throw new InvalidArgumentException($errorMsg);
        }

        if (! $passId) {
            $passId = uniqid('pass_', true);
        }

        $this->passRelativePath = config('passgenerator.storage_path').'/'.$passId;
        $this->passFilename = $passId.'.pkpass';

        if (Storage::disk(config('passgenerator.storage_disk'))->exists($this->passRelativePath . '.pkpass')) {
            if ($replaceExistent) {
                Storage::disk(config('passgenerator.storage_disk'))->delete($this->passRelativePath . '.pkpass');
            } else {
                throw new RuntimeException(
                    'The file '.$this->passFilename.' already exists, try another pass_id or download.'
                );
            }
        }

        $this->passRealPath = Storage::disk(config('passgenerator.storage_disk'))->path($this->passRelativePath);
    }

    /**
     * Clean up the temp folder if the execution was stopped for some reason
     * If it was already removed, nothing happens.
     */
    public function __destruct()
    {
        Storage::disk(config('passgenerator.storage_disk'))
            ->deleteDirectory(config('passgenerator.storage_path').'/'.$this->passRelativePath);
    }

    /**
     * Add an asset to the pass. Use this function to add images to the pass.
     *
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function addAsset(string $assetPath, ?string $name = null)
    {
        if (is_file($assetPath)) {
            if (empty($name)) {
                $this->assets[basename($assetPath)] = $assetPath;
            } else {
                $this->assets[$name] = $assetPath;
            }

            return;
        }

        throw new InvalidArgumentException("The file $assetPath does NOT exist");
    }

    /**
     * Set the pass definition with an array.
     *
     * @param  DefinitionInterface|array  $definition
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function setPassDefinition($definition)
    {
        if ($definition instanceof DefinitionInterface) {
            $definition = $definition->getPassDefinition();
        }

        if (! is_array($definition)) {
            throw new InvalidArgumentException('An invalid Pass definition was provided.');
        }

        $this->passJson = json_encode($definition);
    }

    /**
     * Set the pass definition with a JSON string.
     *
     *
     * @return void
     *
     * @throws JsonException
     */
    public function setPassDefinitionJson(string $jsonDefinition)
    {
        // Test it with Safe\json_decode
        json_decode($jsonDefinition);

        $this->passJson = $jsonDefinition;
    }

    /**
     * Create the signed .pkpass file.
     *
     * @return string
     */
    public function create()
    {
        $this->createTempFolder();

        // Create and store the json manifest
        $manifest = $this->createJsonManifest();

        Storage::disk(config('passgenerator.storage_disk'))
            ->put($this->passRelativePath.'/manifest.json', $manifest);

        // Sign manifest with the certificate
        $this->signManifest();

        // Create the actual pass
        $this->zipItAll();

        // Get it out of the tmp folder and clean everything up
        Storage::disk(config('passgenerator.storage_disk'))->move(
            $this->passRelativePath.'/'.$this->passFilename,
            $this->passRelativePath . '.pkpass');

        Storage::disk(config('passgenerator.storage_disk'))
            ->deleteDirectory($this->passRelativePath);

        // Return the contents, but keep the pkpass stored for future downloads
        return Storage::disk(config('passgenerator.storage_disk'))
            ->get($this->passRelativePath . '.pkpass');
    }

    /**
     * Get a pass if it was already created.
     *
     *
     * @return string|bool If exists, the content of the pass.
     */
    public static function getPass(string $passId)
    {
        if (Storage::disk(config('passgenerator.storage_disk'))->exists(config('passgenerator.storage_path').'/'.$passId.'.pkpass')) {
            return Storage::disk(config('passgenerator.storage_disk'))->get(config('passgenerator.storage_path').'/'.$passId.'.pkpass');
        }

        return false;
    }

    /**
     * Get the path to a pass if it was already created.
     *
     *
     * @return string|bool
     */
    public function getPassFilePath(string $passId)
    {
        if (Storage::disk(config('passgenerator.storage_disk'))->exists(config('passgenerator.storage_path').'/'.$passId.'.pkpass')) {
            return $this->passRealPath.'/../'.$this->passFilename;
        }

        return false;
    }

    /**
     * Get the valid MIME type for the pass.
     */
    public static function getPassMimeType(): string
    {
        return 'application/vnd.apple.pkpass';
    }

    /**
     * Create the JSON manifest with all the hashes from the included files.
     */
    private function createJsonManifest(): string
    {
        $hashes['pass.json'] = sha1($this->passJson);

        foreach ($this->assets as $filename => $path) {
            $hashes[$filename] = sha1(file_get_contents($path));
        }

        return json_encode((object) $hashes);
    }

    /**
     * Remove all the MIME and email stuff around the DER signature and decode it from base64.
     *
     *
     * @return string A clean DER signature
     *
     * @throws UrlException
     *
     * @internal param string $signature The returned result of openssl_pkcs7_sign()
     */
    private function removeMimeFromEmailSignature(string $emailSignature): string
    {
        $lastHeaderLine = 'Content-Disposition: attachment; filename="smime.p7s"';

        $footerLineStart = "\n------";

        // Remove first the header, first find the new-line on the last line of the header and cut all the previous
        $firstSignatureLine = mb_strpos($emailSignature, "\n", mb_strpos($emailSignature, $lastHeaderLine));

        $cleanSignature = mb_strcut($emailSignature, $firstSignatureLine + 1);

        // Now remove the 'footer',
        $endOfSignature = mb_strpos($cleanSignature, $footerLineStart);

        $cleanSignature = mb_strcut($cleanSignature, 0, $endOfSignature);

        // Clean and decode
        $cleanSignature = trim($cleanSignature);

        return base64_decode($cleanSignature);
    }

    /**
     * Sign the manifest with the provided certificates and store the signature.
     *
     * @see -) http://php.net/manual/en/function.openssl-pkcs7-sign.php#111336 for PKCS7 flags.
     *      -) https://en.wikipedia.org/wiki/X.509 for further info on PEM, DER and other certificate stuff
     *      -) http://php.net/manual/en/function.openssl-pkcs7-sign.php for the return of signing function
     *      -) and a google "smime.p7s" for further fun... on how broken cryptography on the internet is.
     *
     * @throws RuntimeException
     * @throws OpensslException
     */
    private function signManifest(): void
    {
        // note for openssl 3:
        // if you get the error "php error:0308010C:digital envelope routines::unsupported"
        // unpack the key: openssl pkcs12 -in PassType.p12 -nodes -out key_decrypted.tmp
        // pack it using a modern algo: openssl pkcs12 -export -in key_decrypted.tmp -out new.p12 -certpbe AES-256-CBC -keypbe AES-256-CBC

        $manifestPath = $this->passRealPath.'/'.$this->manifestFilename;

        $signaturePath = $this->passRealPath.'/'.$this->signatureFilename;

        $certs = [];

        // Try to read the cert
        \Safe\openssl_pkcs12_read($this->certStore, $certs, $this->certStorePassword);

        // Get the certificate resource
        $certResource = openssl_x509_read($certs['cert']);

        // Get the private key out of the cert
        $privateKey = openssl_pkey_get_private($certs['pkey'], $this->certStorePassword);

        // Sign the manifest and store int in the signature file
        openssl_pkcs7_sign(
            $manifestPath,
            $signaturePath,
            $certResource, // @phpstan-ignore argument.type
            $privateKey, // @phpstan-ignore argument.type
            [],
            PKCS7_BINARY | PKCS7_DETACHED,
            Storage::disk(config('passgenerator.storage_disk'))->path($this->wwdrCertPath)
        );

        // PKCS7 returns a signature on PEM format (.p7s), we only need the DER signature so Apple does not cry.
        // It turns out we are lucky since p7s format is just a Base64 encoded DER signature
        // enclosed between some email headers a MIME bs, so we just need to remove some lines
        $signature = Storage::disk(config('passgenerator.storage_disk'))->get($this->passRelativePath.'/'.$this->signatureFilename);

        $signature = $this->removeMimeFromEmailSignature($signature);

        Storage::disk(config('passgenerator.storage_disk'))->put($this->passRelativePath.'/'.$this->signatureFilename,
            $signature);
    }

    /**
     * Create a the pass zipping all files into one.
     *
     * @throws RuntimeException
     */
    private function zipItAll(): void
    {
        $zipPath = $this->passRealPath.'/'.$this->passFilename;

        $manifestPath = $this->passRealPath.'/'.$this->manifestFilename;

        $signaturePath = $this->passRealPath.'/'.$this->signatureFilename;

        $zip = new ZipArchive;

        if (! $zip->open($zipPath, ZipArchive::CREATE)) {
            throw new RuntimeException('There was a problem while creating the zip file');
        }

        // Add the manifest
        $zip->addFile($manifestPath, $this->manifestFilename);

        // Add the signature
        $zip->addFile($signaturePath, $this->signatureFilename);

        // Add pass.json
        $zip->addFromString($this->passJsonFilename, $this->passJson);

        // Add all the assets
        foreach ($this->assets as $name => $path) {
            $zip->addFile($path, $name);
        }

        $zip->close();
    }

    /*
     * Create a temporary folder to store all files before creating the pass.
     */
    private function createTempFolder(): void
    {
        if (! is_dir($this->passRealPath)) {
            Storage::disk(config('passgenerator.storage_disk'))->makeDirectory($this->passRelativePath);
        }
    }
}
