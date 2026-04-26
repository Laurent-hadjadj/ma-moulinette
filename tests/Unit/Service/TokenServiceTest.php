<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\TokenService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class TokenServiceTest extends TestCase
{
    /** @var CsrfTokenManagerInterface&MockObject */
    private MockObject $csrfManager;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private TokenService $service;

    protected function setUp(): void
    {
        $this->csrfManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new TokenService($this->csrfManager, $this->logger);
    }

    public function testGenerateTokenReturnsCsrfPrefixedBase64EncodedPayload(): void
    {
        $this->csrfManager->expects($this->once())
            ->method('getToken')
            ->with('intention')
            ->willReturn(new CsrfToken('intention', 'csrf-value-xyz'));

        $this->logger->expects($this->never())->method('critical');

        $data = ['userId' => 42, 'scope' => 'admin'];

        $token = $this->service->generateToken($data);

        $this->assertStringStartsWith('csrf-value-xyz.', $token);

        [$csrf, $payload] = explode('.', $token, 2);
        $this->assertSame('csrf-value-xyz', $csrf);
        $this->assertSame($data, json_decode(base64_decode($payload), true));
    }

    public function testDecodeTokenReturnsDecodedPayloadWhenCsrfIsValid(): void
    {
        $payload = ['userId' => 42, 'scope' => 'admin'];
        $encoded = base64_encode(json_encode($payload));
        $token = 'csrf-valid.' . $encoded;

        $this->csrfManager->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(function (CsrfToken $csrfToken) {
                return $csrfToken->getId() === 'intention'
                    && $csrfToken->getValue() === 'csrf-valid';
            }))
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with('[Welcome] ℹ️ Le token est correcte.', $this->arrayHasKey('parts'));

        $this->assertSame($payload, $this->service->decodeToken($token));
    }

    public function testDecodeTokenThrowsWhenCsrfIsInvalid(): void
    {
        $encoded = base64_encode(json_encode(['x' => 1]));

        $this->csrfManager->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false);

        $this->logger->expects($this->never())->method('critical');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid token');

        $this->service->decodeToken('wrong-csrf.' . $encoded);
    }

    public function testDecodeTokenThrowsAndLogsWhenTokenHasNoSeparator(): void
    {
        $this->csrfManager->expects($this->never())->method('isTokenValid');

        $this->logger->expects($this->once())
            ->method('critical')
            ->with(
                '[Welcome] 🔴 Le token est incorrect ou mal formé.',
                $this->arrayHasKey('parts')
            );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid token format');

        $this->service->decodeToken('no-dot-in-this-string');
    }
}
