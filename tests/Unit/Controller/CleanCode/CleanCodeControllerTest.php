<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Controller\CleanCode;

/* MODIF 2026-05-17 : tests unitaires pour
 * CleanCodeController (token manquant / invalide / no-data / happy path avec
 * indicateurs calculés).
 * MODIF 2026-07-19 : ajout de la couverture du filtrage par groupe
 * fonctionnel (verifierPerimetreProjet, alignement sur OwaspControllerTest). */

use App\Controller\CleanCode\CleanCodeController;
use App\Entity\{CleanCode, ListeProjet, Utilisateur};
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Repository\{CleanCodeRepository, ListeProjetRepository};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class CleanCodeControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';

    /** @var ParameterBagInterface&MockObject */    private MockObject $params;
    /** @var EntityManagerInterface&MockObject */   private MockObject $em;
    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var CleanCodeRepository&MockObject */      private MockObject $cleanCodeRepo;
    /** @var ListeProjetRepository&MockObject */    private MockObject $listeProjetRepo;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var FlashBag&MockObject */                 private MockObject $flashBag;
    /** @var TokenStorageInterface&MockObject */    private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */           private MockObject $token;

    /** @var UserAgentTrackingFacade&MockObject */ private MockObject $tracking;

    private CleanCodeController $controller;

    protected function setUp(): void
    {
        $this->params          = $this->createMock(ParameterBagInterface::class);
        $this->em              = $this->createMock(EntityManagerInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->cleanCodeRepo   = $this->createMock(CleanCodeRepository::class);
        $this->listeProjetRepo = $this->createMock(ListeProjetRepository::class);
        $this->twig            = $this->createMock(Environment::class);
        $this->flashBag        = $this->createMock(FlashBag::class);
        $this->tokenStorage    = $this->createMock(TokenStorageInterface::class);
        $this->token           = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);
        $this->tracking        = $this->createMock(UserAgentTrackingFacade::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise',      'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long',  'Ma Moulinette'],
            ['environnement',        'test'],
            ['version',              '2.0.0'],
        ]);

        $this->em->method('getRepository')->willReturnCallback(
            fn(string $class) => match ($class) {
                ListeProjet::class => $this->listeProjetRepo,
                default            => $this->cleanCodeRepo,
            }
        );

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['twig',          true],
            ['request_stack', true],
            ['parameter_bag', true],
            ['security.token_storage', true],
        ]);
        $container->method('get')->willReturnMap([
            ['twig',          1, $this->twig],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        $this->controller = new CleanCodeController(
            $this->params,
            $this->em,
            $this->logger,
            $this->tracking
        );
        $this->controller->setContainer($container);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function buildValidToken(string $mavenKey = self::MAVEN_KEY, int $salt = 12345): string
    {
        return str_rot13(base64_encode("{$salt}|{$mavenKey}"));
    }

    private function buildRequest(?string $token): Request
    {
        return new Request($token === null ? [] : ['token' => $token]);
    }

    /**
     * MODIF 2026-07-19 : configure l'utilisateur authentifié + la liste de
     * projets de son groupe fonctionnel pour que verifierPerimetreProjet()
     * autorise $mavenKey.
     */
    private function authorizeMavenKey(string $mavenKey = self::MAVEN_KEY): void
    {
        $user = new Utilisateur();
        $user->setCourriel('user@ma-moulinette.fr');
        $user->setListeGroupeFonctionnel(['TeamA']);
        $this->token->method('getUser')->willReturn($user);

        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => $mavenKey]],
        ]);
    }

    // ─── 1. Token manquant → flash info (navigation sans contexte) ───────────

    public function testIndexFlashesInfoWhenTokenIsMissing(): void
    {
        // MODIF 2026-07-22 : token absent = pas une anomalie, distingué désormais
        // d'un token invalide (test suivant), qui reste une vraie erreur 400.
        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(
                fn($v) => $v['type'] === 'info' && !str_contains($v['message'], 'Erreur 400')
            ));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('clean-code/index.html.twig', $this->anything())
            ->willReturn('<html>missing-token</html>');

        $response = $this->controller->index($this->buildRequest(null));

        $this->assertSame('<html>missing-token</html>', $response->getContent());
    }

    // ─── 2. Token invalide (non base64 après rot13) → flash error ────────────

    public function testIndexFlashesErrorWhenTokenIsInvalid(): void
    {
        // Chaîne qui ne donne pas du base64 valide après str_rot13
        $invalidToken = '###not-base64###';

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>invalid</html>');

        $this->controller->index($this->buildRequest($invalidToken));
    }

    // ─── 3. Token sans le séparateur pipe → flash error ──────────────────────

    public function testIndexFlashesErrorWhenTokenHasWrongFormat(): void
    {
        // Token décodable mais sans '|' (parts != 2)
        $token = str_rot13(base64_encode('un-seul-segment-sans-pipe'));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>bad-format</html>');

        $this->controller->index($this->buildRequest($token));
    }

    // ─── 4. Token valide mais aucune donnée → flash warning ──────────────────

    public function testIndexFlashesWarningWhenNoCleanCodeData(): void
    {
        $this->authorizeMavenKey();

        $this->cleanCodeRepo->expects($this->once())
            ->method('selectCleanCode')
            ->with(['maven_key' => self::MAVEN_KEY])
            ->willReturn(['code' => 200, 'liste' => [], 'erreur' => '']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('clean-code/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>no-data</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken()));

        $this->assertSame(self::MAVEN_KEY, $capturedCtx['maven_key']);
        $this->assertNull($capturedCtx['data']);
    }

    // ─── 4bis. Le décodage du token ne doit pas forcer la casse ──────────────

    public function testIndexPreservesCaseOfMavenKeyFromToken(): void
    {
        /* MODIF 2026-07-19 : verrouille le fix — decodeToken() ne doit plus
         * forcer la casse en minuscules (une clé Maven mixed-case comme
         * "fr.ma-moulinette:Ma-Moulinette" était sinon cherchée en base sous
         * une forme qui ne correspond à aucune ligne, la comparaison SQL
         * étant sensible à la casse). */
        $mixedCaseKey = 'fr.ma-moulinette:Ma-Moulinette';
        $this->authorizeMavenKey($mixedCaseKey);

        $this->cleanCodeRepo->expects($this->once())
            ->method('selectCleanCode')
            ->with(['maven_key' => $mixedCaseKey])
            ->willReturn(['code' => 200, 'liste' => [], 'erreur' => '']);

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>ok</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken($mixedCaseKey)));
    }

    /* ============ Filtrage par groupe fonctionnel (MODIF 2026-07-19) ============ */

    public function testIndexFlashesWarning404WhenNoGroupeFonctionnel(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('user@ma-moulinette.fr');
        $user->setListeGroupeFonctionnel([]);
        $this->token->method('getUser')->willReturn($user);

        $this->cleanCodeRepo->expects($this->never())->method('selectCleanCode');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(
                fn($v) => $v['type'] === 'warning' && str_contains($v['message'], 'groupe fonctionnel')
            ));

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>no-groupe</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken()));
    }

    public function testIndexFlashesWarning406WhenProjectNotInGroupe(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('user@ma-moulinette.fr');
        $user->setListeGroupeFonctionnel(['TeamA']);
        $this->token->method('getUser')->willReturn($user);

        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.ma-moulinette:autre-projet']],
        ]);

        $this->cleanCodeRepo->expects($this->never())->method('selectCleanCode');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>hors-perimetre</html>');

        $this->controller->index($this->buildRequest($this->buildValidToken()));
    }

    // ─── 5. Happy path : indicateurs calculés injectés dans le template ───────

    public function testIndexHappyPathInjectsComputedIndicators(): void
    {
        $this->authorizeMavenKey();

        $row = [
            'project_name'        => 'ma-moulinette',
            'date_enregistrement' => '2026-03-01 00:00:00',
            'issue_total'         => 20,
            'cc_consistent'       => 5,
            'cc_intentional'      => 3,
            'cc_adaptable'        => 4,
            'cc_responsible'      => 2,
            'quality_maintainability' => 10,
            'quality_reliability'     => 5,
            'quality_security'        => 5,
            'impact_blocker'      => 2,
            'impact_high'         => 8,
            'impact_medium'       => 6,
            'impact_low'          => 3,
            'impact_info'         => 1,
            'owasp_top10'         => 3,
            'sans_top25'          => 2,
            'cwe'                 => 4,
        ];

        $this->cleanCodeRepo->expects($this->once())
            ->method('selectCleanCode')
            ->with(['maven_key' => self::MAVEN_KEY])
            ->willReturn(['code' => 200, 'liste' => [$row], 'erreur' => '']);

        $this->flashBag->expects($this->never())->method('add');

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('clean-code/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $response = $this->controller->index($this->buildRequest($this->buildValidToken()));

        $this->assertSame('<html>ok</html>', $response->getContent());

        $this->assertSame(self::MAVEN_KEY,   $capturedCtx['maven_key']);
        $this->assertSame('ma-moulinette',   $capturedCtx['project_name']);
        $this->assertSame(20,                $capturedCtx['issue_total']);

        // risk_score : (2×4 + 8×3 + 6×2 + 3×1 + 1×0.5) / 20 = 47.5/20 = 2.38
        $this->assertSame(2.38, $capturedCtx['risk_score']);
        $this->assertSame('high', $capturedCtx['risk_level']);

        // responsible_pct : round(2/14 × 100, 1) = 14.3
        $this->assertSame(14.3, $capturedCtx['responsible_pct']);

        // security_pct : round(5/20 × 100, 1) = 25.0
        $this->assertSame(25.0, $capturedCtx['security_pct']);

        // chart_data est une chaîne JSON
        $this->assertIsString($capturedCtx['chart_data']);
        $decoded = json_decode($capturedCtx['chart_data'], true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('categories', $decoded);
        $this->assertArrayHasKey('severities', $decoded);
        $this->assertSame(['CONSISTENT', 'INTENTIONAL', 'ADAPTABLE', 'RESPONSIBLE'], $decoded['categories']['labels']);
        $this->assertSame([5, 3, 4, 2], $decoded['categories']['values']);
    }
}
