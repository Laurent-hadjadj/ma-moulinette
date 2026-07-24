<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\InvalidCipherKeyException;
use App\Service\ActuatorCredentialCipher;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class ActuatorCredentialCipherTest extends TestCase
{
    /** @var LoggerInterface&MockObject */ private MockObject $logger;

    private ActuatorCredentialCipher $cipher;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cipher = new ActuatorCredentialCipher(base64_encode(str_repeat('a', 32)), $this->logger);
    }

    public function testConstructorRejectsKeyWithWrongLength(): void
    {
        $this->expectException(InvalidCipherKeyException::class);
        new ActuatorCredentialCipher(base64_encode('trop-court'), $this->logger);
    }

    public function testConstructorRejectsInvalidBase64(): void
    {
        $this->expectException(InvalidCipherKeyException::class);
        new ActuatorCredentialCipher('%%% pas du base64 %%%', $this->logger);
    }

    public function testEncryptThenDecryptRoundTrips(): void
    {
        $chiffre = $this->cipher->encrypt('mot-de-passe-secret');

        $this->assertNotNull($chiffre);
        $this->assertStringStartsWith('enc_v1:', $chiffre);
        $this->assertNotSame('mot-de-passe-secret', $chiffre);
        $this->assertSame('mot-de-passe-secret', $this->cipher->decrypt($chiffre));
    }

    public function testEncryptReturnsNullForNullOrEmpty(): void
    {
        $this->assertNull($this->cipher->encrypt(null));
        $this->assertNull($this->cipher->encrypt(''));
    }

    public function testDecryptReturnsNullForNullOrEmpty(): void
    {
        $this->assertNull($this->cipher->decrypt(null));
        $this->assertNull($this->cipher->decrypt(''));
    }

    public function testDecryptReturnsLegacyPlaintextUnchanged(): void
    {
        // Valeur historique enregistrée avant l'introduction du chiffrement (pas de préfixe enc_v1:)
        $this->assertSame('ancien-mot-de-passe-en-clair', $this->cipher->decrypt('ancien-mot-de-passe-en-clair'));
    }

    public function testDecryptReturnsNullOnCorruptedCiphertext(): void
    {
        $this->logger->expects($this->once())->method('error');

        $result = $this->cipher->decrypt('enc_v1:' . base64_encode('donnee-corrompue-trop-courte'));

        $this->assertNull($result);
    }

    public function testDecryptFailsWithWrongKey(): void
    {
        $chiffre = $this->cipher->encrypt('secret');

        $autreCipher = new ActuatorCredentialCipher(base64_encode(str_repeat('b', 32)), $this->logger);
        $this->assertNull($autreCipher->decrypt($chiffre));
    }
}
