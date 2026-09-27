<?php

namespace Byte5\Tests;

use Byte5\PassGenerator;
use InvalidArgumentException;

class PassGeneratorCertificateTest extends TestCase
{
    private function passDefinition(): array
    {
        return [
            'description' => 'Test pass',
            'formatVersion' => 1,
            'organizationName' => 'Test Organization',
            'passTypeIdentifier' => 'pass.com.example.test',
            'serialNumber' => '123456',
            'teamIdentifier' => 'TEAMID123',
            'generic' => [
                'primaryFields' => [
                    ['key' => 'name', 'label' => 'NAME', 'value' => 'John Doe'],
                ],
            ],
        ];
    }

    public function test_it_uses_the_explicit_certificate_when_provided(): void
    {
        // Config points to certificates that do not exist, proving the explicit
        // certificate takes precedence and the config is never consulted.
        config()->set('passgenerator.certificate_store_path', 'does-not-exist.p12');
        config()->set('passgenerator.wwdr_certificate_path', 'does-not-exist.pem');

        $certificate = file_get_contents(__DIR__.'/Fixtures/certificate.p12');
        $wwdrCertificate = file_get_contents(__DIR__.'/Fixtures/wwdr.pem');

        $pass = new PassGenerator('explicit-cert-pass');
        $pass->useCertificate($certificate, 'testpassword');
        $pass->useWwdrCertificate($wwdrCertificate);
        $pass->setPassDefinition($this->passDefinition());

        $pkpass = $pass->create();

        $this->assertNotEmpty($pkpass);
        $this->assertSame('PK', substr($pkpass, 0, 2));
    }

    public function test_it_falls_back_to_the_config_certificate_when_no_override_is_set(): void
    {
        config()->set('passgenerator.config_disk', 'fixtures');
        config()->set('passgenerator.certificate_store_path', 'certificate.p12');
        config()->set('passgenerator.certificate_store_password', 'testpassword');
        config()->set('passgenerator.wwdr_certificate_path', 'wwdr.pem');

        $pass = new PassGenerator('config-cert-pass');
        $pass->setPassDefinition($this->passDefinition());

        $pkpass = $pass->create();

        $this->assertNotEmpty($pkpass);
        $this->assertSame('PK', substr($pkpass, 0, 2));
    }

    public function test_it_throws_when_no_certificate_is_available_anywhere(): void
    {
        config()->set('passgenerator.certificate_store_path', 'does-not-exist.p12');
        config()->set('passgenerator.wwdr_certificate_path', 'does-not-exist.pem');

        $pass = new PassGenerator('missing-cert-pass');
        $pass->setPassDefinition($this->passDefinition());

        $this->expectException(InvalidArgumentException::class);

        $pass->create();
    }
}
