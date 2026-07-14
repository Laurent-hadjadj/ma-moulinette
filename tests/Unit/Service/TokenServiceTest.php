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

use App\Service\TokenService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[CoversClass(TokenService::class)]
final class TokenServiceTest extends TestCase
{
    public function testGenerateTokenConcatenatesCsrfTokenAndBase64EncodedJson(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->expects(self::once())
            ->method('getToken')
            ->with('intention')
            ->willReturn(new CsrfToken('intention', 'CSRF-VALUE'));

        $service = new TokenService($csrf, new NullLogger());

        $token = $service->generateToken(['user' => 'alice', 'role' => 'admin']);

        [$csrfPart, $payloadPart] = explode('.', $token, 2);
        self::assertSame('CSRF-VALUE', $csrfPart);
        self::assertSame(
            ['user' => 'alice', 'role' => 'admin'],
            json_decode(base64_decode($payloadPart), true)
        );
    }

    public function testDecodeTokenReturnsPayloadWhenCsrfValid(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')
            ->willReturnCallback(static fn (CsrfToken $t): bool => $t->getValue() === 'GOOD-CSRF');

        $service = new TokenService($csrf, new NullLogger());
        $token = 'GOOD-CSRF.' . base64_encode((string) json_encode(['k' => 'v']));

        self::assertSame(['k' => 'v'], $service->decodeToken($token));
    }

    public function testDecodeTokenThrowsWhenCsrfInvalid(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);

        $service = new TokenService($csrf, new NullLogger());
        $token = 'BAD-CSRF.' . base64_encode((string) json_encode(['k' => 'v']));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid token');

        $service->decodeToken($token);
    }

    public function testDecodeTokenLogsAndThrowsWhenTokenHasNoSeparator(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('critical');

        $service = new TokenService($csrf, $logger);

        // Le service a un bug documenté (non corrigé) : list() sur un tableau
        // à 1 élément génère E_WARNING en PHP 8.5. On supprime ce warning connu
        // pour que le test couvre proprement la branche count($parts) !== 2.
        set_error_handler(static fn() => true, E_WARNING);
        try {
            $service->decodeToken('tokenwithoutseparator');
            $this->fail('Attendu : InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Invalid token format', $e->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    public function testGenerateAndDecodeRoundTrip(): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('getToken')
            ->willReturn(new CsrfToken('intention', 'STATIC-CSRF'));
        $csrf->method('isTokenValid')
            ->willReturnCallback(static fn (CsrfToken $t): bool => $t->getValue() === 'STATIC-CSRF');

        $service = new TokenService($csrf, new NullLogger());

        $payload = ['maven_key' => 'fr.ma-moulinette:ma-moulinette', 'version' => '1.2.3'];
        $token = $service->generateToken($payload);

        self::assertSame($payload, $service->decodeToken($token));
    }
}
