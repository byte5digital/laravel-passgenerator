<?php

namespace Byte5\Definitions\Dictionary;

use Illuminate\Support\Fluent;
use InvalidArgumentException;

class Barcode extends Fluent
{
    public public const FORMAT_AZTEX = 'PKBarcodeFormatAztec';

    public const FORMAT_CODE128 = 'PKBarcodeFormatCode128';

    public const FORMAT_PDF417 = 'PKBarcodeFormatPDF417';

    public const FORMAT_QR = 'PKBarcodeFormatQR';

    /** @var array */
    private $validFormats = [
        self::FORMAT_AZTEX,
        self::FORMAT_CODE128,
        self::FORMAT_PDF417,
        self::FORMAT_QR,
    ];

    /**
     * @param  string  $message  Message or payload to be displayed as a barcode.
     * @param  string  $format  Barcode format. See class constants.
     * @param  string  $messageEncoding  Text encoding that is used to convert the message
     *                                   from the string representation to a data representation
     *                                   to render the barcode.
     */
    public function __construct(string $message, string $format, string $messageEncoding = 'iso-8859-1')
    {
        if (! in_array($format, $this->validFormats)) {
            throw new InvalidArgumentException('Invalid barcode format');
        }

        parent::__construct(compact('message', 'format', 'messageEncoding'));
    }

    /**
     * Text displayed near the barcode. For example, a human-readable
     * version of the barcode data in case the barcode doesn’t scan.
     */
    public function setAltText(string $altText): self
    {
        $this->attributes['altText'] = $altText;

        return $this;
    }

    /**
     * Barcode format. For the barcode dictionary, you can use only the following values:
     * PKBarcodeFormatQR, PKBarcodeFormatPDF417, or PKBarcodeFormatAztec.
     * For dictionaries in the barcodes array, you may also use PKBarcodeFormatCode128.
     * They are all constants on this class.
     */
    public function setFormat(string $format): self
    {
        if (! in_array($format, $this->validFormats)) {
            throw new InvalidArgumentException('Invalid barcode format');
        }
        $this->attributes['format'] = $format;

        return $this;
    }

    /**
     * Message or payload to be displayed as a barcode.
     */
    public function setMessage(string $message): self
    {
        $this->attributes['message'] = $message;

        return $this;
    }

    /**
     * Text encoding that is used to convert the message from the
     * string representation to a data representation to render the barcode.
     * The value is typically iso-8859-1, but you may use another encoding
     * that is supported by your barcode scanning infrastructure.
     */
    public function setMessageEncoding(string $messageEncoding): self
    {
        $this->attributes['messageEncoding'] = $messageEncoding;

        return $this;
    }
}
