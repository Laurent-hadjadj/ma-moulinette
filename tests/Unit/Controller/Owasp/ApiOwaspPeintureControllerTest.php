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

namespace App\Tests\Unit\Controller\Owasp;

use App\Controller\Owasp\ApiOwaspPeintureController;
use App\Entity\{HotspotDetails, HotspotOwasp, ListeProjet, Owasp, Utilisateur};
use App\Repository\{HotspotDetailsRepository, HotspotOwaspRepository, ListeProjetRepository, OwaspRepository};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiOwaspPeintureControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var OwaspRepository&MockObject */
    private MockObject $owaspRepo;

    /** @var HotspotOwaspRepository&MockObject */
    private MockObject $hotspotOwaspRepo;

    /** @var HotspotDetailsRepository&MockObject */
    private MockObject $hotspotDetailsRepo;

    /** @var ListeProjetRepository&MockObject */
    private MockObject $listeProjetRepo;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var TokenStorageInterface&MockObject */
    private MockObject $tokenStorage;

    /** @var TokenInterface&MockObject */
    private MockObject $token;

    private ApiOwaspPeintureController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->owaspRepo = $this->createMock(OwaspRepository::class);
        $this->hotspotOwaspRepo = $this->createMock(HotspotOwaspRepository::class);
        $this->hotspotDetailsRepo = $this->createMock(HotspotDetailsRepository::class);
        $this->listeProjetRepo = $this->createMock(ListeProjetRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);

        $this->em->method('getRepository')->willReturnMap([
            [Owasp::class, $this->owaspRepo],
            [HotspotOwasp::class, $this->hotspotOwaspRepo],
            [HotspotDetails::class, $this->hotspotDetailsRepo],
            [ListeProjet::class, $this->listeProjetRepo],
        ]);

        // Container mock pour AbstractController::json() + security.token_storage
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'security.token_storage'
        );
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        $this->controller = new ApiOwaspPeintureController($this->em, $this->logger);
        $this->controller->setContainer($container);
    }

    /**
     * MODIF 2026-07-17 : autorise self::MAVEN_KEY pour l'utilisateur courant
     * (groupe fonctionnel + ListeProjetRepository), condition désormais
     * requise par ProjetPerimetreGuard avant tout accès aux données. Un seul
     * stub par mock (jamais de valeur par défaut dans setUp()) pour éviter
     * toute ambiguïté d'ordre entre tests — cf. CosuiControllerTest.
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

    // ═══════════════════════ peintureOwaspListe ════════════════════════════

    public function testPeintureOwaspListeReturns400WhenMavenKeyOrReferentialMissing(): void
    {
        $response = $this->controller->peintureOwaspListe(
            $this->jsonRequest(['referential_owasp' => 2017]) // pas de maven_key
        );

        $this->assertJsonStatus($response, 400, 'error');
    }

    public function testPeintureOwaspListePropagatesRepositoryError(): void
    {
        $this->authorizeMavenKey();
        $this->owaspRepo->expects($this->once())
            ->method('selectOwaspOrderByDateEnregistrement')
            ->willReturn(['code' => 500, 'erreur' => 'boom']);

        $response = $this->controller->peintureOwaspListe($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'referential_owasp' => 2017,
        ]));

        $this->assertJsonStatus($response, 500, 'error');
    }

    public function testPeintureOwaspListeReturns406WhenListIsEmpty(): void
    {
        $this->authorizeMavenKey();
        $this->owaspRepo->expects($this->once())
            ->method('selectOwaspOrderByDateEnregistrement')
            ->willReturn(['code' => 200, 'liste' => []]);

        $payload = $this->decode($this->controller->peintureOwaspListe($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'referential_owasp' => 2017,
        ])));

        $this->assertSame(406, $payload['code']);
        $this->assertSame([], $payload['liste']);
    }

    public function testPeintureOwaspListeReturnsAggregatesAndA1ToA10Counts(): void
    {
        $this->authorizeMavenKey();
        $row = $this->buildOwaspRow();
        $this->owaspRepo->expects($this->once())
            ->method('selectOwaspOrderByDateEnregistrement')
            ->with([
                'maven_key' => self::MAVEN_KEY,
                'referential_owasp' => 2021,
            ])
            ->willReturn(['code' => 200, 'liste' => [$row]]);

        $payload = $this->decode($this->controller->peintureOwaspListe($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'referential_owasp' => 2021,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2021, $payload['referential_owasp']);
        $this->assertSame('1.0.0', $payload['version']);

        // somme sur a1..a10 = 1+2+3+4+5+6+7+8+9+10 = 55
        $this->assertSame(55, $payload['total']);

        // somme bloquant (a1_blocker..a10_blocker) = 10 × 1 = 10
        $this->assertSame(10, $payload['bloquant']);
        $this->assertSame(20, $payload['critique']);
        $this->assertSame(30, $payload['majeur']);
        $this->assertSame(40, $payload['mineur']);
        $this->assertSame('facet', $payload['source']);

        // Valeurs exposées individuellement
        $this->assertSame(1, $payload['a1']);
        $this->assertSame(10, $payload['a10']);
        $this->assertSame(1, $payload['a1Blocker']);
        $this->assertSame(2, $payload['a1Critical']);
    }

    public function testPeintureOwaspListeExposesTagSourceWhenSetInRow(): void
    {
        // MODIF 2026-07-18 : la ligne persistée par le secours par tag
        // (BatchCollecteOwaspController) porte source='tag' — vérifie que
        // l'API de peinture la relaie telle quelle au front.
        $this->authorizeMavenKey();
        $row = array_merge($this->buildOwaspRow(), ['source' => 'tag']);
        $this->owaspRepo->method('selectOwaspOrderByDateEnregistrement')
            ->willReturn(['code' => 200, 'liste' => [$row]]);

        $payload = $this->decode($this->controller->peintureOwaspListe($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'referential_owasp' => 2017,
        ])));

        $this->assertSame('tag', $payload['source']);
    }

    // ═══════════════════════ peintureOwaspHotspotInfo ══════════════════════

    public function testPeintureOwaspHotspotInfoReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->peintureOwaspHotspotInfo($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, 'error');
    }

    public function testPeintureOwaspHotspotInfoPropagatesReviewedQueryError(): void
    {
        $this->authorizeMavenKey();
        $this->hotspotOwaspRepo->expects($this->once())
            ->method('countHotspotOwaspStatus')
            ->with(['maven_key' => self::MAVEN_KEY, 'status' => 'REVIEWED'])
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $this->hotspotOwaspRepo->expects($this->never())->method('countHotspotOwaspProbability');

        $response = $this->controller->peintureOwaspHotspotInfo($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 500, 'error');
    }

    public function testPeintureOwaspHotspotInfoAggregatesReviewedAndToReviewTotals(): void
    {
        $this->authorizeMavenKey();
        $this->hotspotOwaspRepo->expects($this->exactly(2))
            ->method('countHotspotOwaspStatus')
            ->willReturnCallback(function (array $map) {
                $nombre = $map['status'] === 'REVIEWED' ? 12 : 8;
                return ['code' => 200, 'request' => [['nombre' => $nombre]]];
            });

        $this->hotspotOwaspRepo->expects($this->once())
            ->method('countHotspotOwaspProbability')
            ->willReturn(['code' => 200, 'nombre' => [
                ['probability' => 'HIGH', 'total' => 3],
                ['probability' => 'MEDIUM', 'total' => 7],
                ['probability' => 'LOW', 'total' => 10],
                ['probability' => 'OTHER', 'total' => 99], // ignoré
            ]]);

        $payload = $this->decode($this->controller->peintureOwaspHotspotInfo($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(12, $payload['reviewed']);
        $this->assertSame(8, $payload['toReview']);
        $this->assertSame(20, $payload['total']);
        $this->assertSame(3, $payload['high']);
        $this->assertSame(7, $payload['medium']);
        $this->assertSame(10, $payload['low']);
    }

    // ═══════════════════════ peintureOwaspHotspotListe ═════════════════════

    public function testPeintureOwaspHotspotListeReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->peintureOwaspHotspotListe($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, 'error');
    }

    public function testPeintureOwaspHotspotListePropagatesRepositoryError(): void
    {
        $this->authorizeMavenKey();
        $this->hotspotOwaspRepo->expects($this->once())
            ->method('countHotspotOwaspMenaces')
            ->willReturn(['code' => 503, 'erreur' => 'timeout']);

        $response = $this->controller->peintureOwaspHotspotListe($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $payload = $this->decode($response);
        $this->assertSame(503, $payload['code']);
    }

    public function testPeintureOwaspHotspotListeReturnsMenaceA1ToA10Counts(): void
    {
        $this->authorizeMavenKey();
        $this->hotspotOwaspRepo->expects($this->once())
            ->method('countHotspotOwaspMenaces')
            ->willReturn(['code' => 200, 'menaces' => [
                ['menace' => 'a1', 'total' => 5],
                ['menace' => 'a5', 'total' => 3],
                ['menace' => 'a10', 'total' => 1],
            ]]);

        $payload = $this->decode($this->controller->peintureOwaspHotspotListe($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(5, $payload['menaceA1']);
        $this->assertSame(3, $payload['menaceA5']);
        $this->assertSame(1, $payload['menaceA10']);
        // Les menaces absentes sont à 0
        $this->assertSame(0, $payload['menaceA2']);
        $this->assertSame(0, $payload['menaceA9']);
    }

    // ═══════════════════════ peintureOwaspHotspotDetails ═══════════════════

    public function testPeintureOwaspHotspotDetailsReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->peintureOwaspHotspotDetails($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, 'error');
    }

    public function testPeintureOwaspHotspotDetailsPropagatesRepositoryError(): void
    {
        $this->authorizeMavenKey();
        $this->hotspotDetailsRepo->expects($this->once())
            ->method('selectHotspotDetailsByStatus')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $response = $this->controller->peintureOwaspHotspotDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 500, 'error');
    }

    public function testPeintureOwaspHotspotDetailsReturnsDetailsOnSuccess(): void
    {
        $this->authorizeMavenKey();
        $repoResult = ['code' => 200, 'liste' => [['id' => 1]]];
        $this->hotspotDetailsRepo->expects($this->once())
            ->method('selectHotspotDetailsByStatus')
            ->willReturn($repoResult);

        $payload = $this->decode($this->controller->peintureOwaspHotspotDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        // Le controller injecte le payload repo complet sous 'details'
        $this->assertSame($repoResult, $payload['details']);
    }

    // ═══════════════════════ peintureOwaspSeverity ═════════════════════════

    public function testPeintureOwaspSeverityReturns400WhenMavenKeyOrMenaceMissing(): void
    {
        $response = $this->controller->peintureOwaspSeverity($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY, // pas de 'menace'
        ]));

        $this->assertJsonStatus($response, 400, 'error');
    }

    public function testPeintureOwaspSeverityReturnsAllThreeProbabilityBuckets(): void
    {
        $this->authorizeMavenKey();
        $this->hotspotOwaspRepo->expects($this->exactly(3))
            ->method('countHotspotOwaspMenaceByStatus')
            ->willReturnCallback(function (array $map) {
                $totals = ['HIGH' => 4, 'MEDIUM' => 2, 'LOW' => 1];
                return [
                    'code' => 200,
                    'nombre' => ['total' => $totals[$map['probability']]],
                ];
            });

        $payload = $this->decode($this->controller->peintureOwaspSeverity($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'menace' => 'a1',
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(4, $payload['high']);
        $this->assertSame(2, $payload['medium']);
        $this->assertSame(1, $payload['low']);
    }

    public function testPeintureOwaspSeverityPropagatesErrorFromHighQuery(): void
    {
        $this->authorizeMavenKey();
        $this->hotspotOwaspRepo->expects($this->once())
            ->method('countHotspotOwaspMenaceByStatus')
            ->willReturn(['code' => 500, 'erreur' => 'high failed']);

        $response = $this->controller->peintureOwaspSeverity($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'menace' => 'a1',
        ]));

        $this->assertJsonStatus($response, 500, 'error');
    }

    public function testPeintureOwaspSeverityPropagatesErrorFromMediumQuery(): void
    {
        $this->authorizeMavenKey();
        // HIGH OK, MEDIUM fails
        $this->hotspotOwaspRepo->method('countHotspotOwaspMenaceByStatus')->willReturnCallback(
            fn(array $map): array => $map['probability'] === 'HIGH'
                ? ['code' => 200, 'nombre' => ['total' => 4]]
                : ['code' => 500, 'erreur' => 'medium failed']
        );

        $response = $this->controller->peintureOwaspSeverity($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'menace' => 'a1',
        ]));

        $this->assertJsonStatus($response, 500);
    }

    public function testPeintureOwaspSeverityPropagatesErrorFromLowQuery(): void
    {
        $this->authorizeMavenKey();
        // HIGH and MEDIUM OK, LOW fails
        $this->hotspotOwaspRepo->method('countHotspotOwaspMenaceByStatus')->willReturnCallback(
            fn(array $map): array => $map['probability'] === 'LOW'
                ? ['code' => 500, 'erreur' => 'low failed']
                : ['code' => 200, 'nombre' => ['total' => 4]]
        );

        $response = $this->controller->peintureOwaspSeverity($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'menace' => 'a1',
        ]));

        $this->assertJsonStatus($response, 500);
    }

    public function testPeintureOwaspHotspotInfoPropagatesToReviewQueryError(): void
    {
        $this->authorizeMavenKey();
        // REVIEWED OK, TO_REVIEW fails
        $this->hotspotOwaspRepo->method('countHotspotOwaspStatus')->willReturnCallback(
            fn(array $map): array => $map['status'] === 'REVIEWED'
                ? ['code' => 200, 'request' => [['nombre' => 5]]]
                : ['code' => 500, 'erreur' => 'toreview fail']
        );

        $this->hotspotOwaspRepo->expects($this->never())->method('countHotspotOwaspProbability');

        $response = $this->controller->peintureOwaspHotspotInfo($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 500, 'error');
    }

    public function testPeintureOwaspHotspotInfoPropagatesProbabilityQueryError(): void
    {
        $this->authorizeMavenKey();
        $this->hotspotOwaspRepo->method('countHotspotOwaspStatus')
            ->willReturn(['code' => 200, 'request' => [['nombre' => 5]]]);

        $this->hotspotOwaspRepo->expects($this->once())
            ->method('countHotspotOwaspProbability')
            ->willReturn(['code' => 500, 'erreur' => 'proba fail']);

        $response = $this->controller->peintureOwaspHotspotInfo($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 500, 'error');
    }

    // ═══════════════════ Filtrage par groupe fonctionnel (MODIF 2026-07-17) ═══
    // La maven_key parvient ici via sessionStorage côté client (pas via un
    // token décodé côté serveur comme sur Suivi/COSUI/la page OWASP) : le
    // filtrage doit donc se faire directement dans chacun des 5 endpoints.

    public function testPeintureOwaspListeReturns404WhenNoGroupeFonctionnel(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('user@ma-moulinette.fr');
        $user->setListeGroupeFonctionnel([]);
        $this->token->method('getUser')->willReturn($user);

        $this->owaspRepo->expects($this->never())->method('selectOwaspOrderByDateEnregistrement');

        $response = $this->controller->peintureOwaspListe($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'referential_owasp' => 2021,
        ]));

        $this->assertJsonStatus($response, 404, 'warning');
    }

    public function testPeintureOwaspListeReturns406WhenProjectNotInGroupe(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('user@ma-moulinette.fr');
        $user->setListeGroupeFonctionnel(['TeamA']);
        $this->token->method('getUser')->willReturn($user);
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.autre:projet-different']],
        ]);

        $this->owaspRepo->expects($this->never())->method('selectOwaspOrderByDateEnregistrement');

        $response = $this->controller->peintureOwaspListe($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'referential_owasp' => 2021,
        ]));

        $this->assertJsonStatus($response, 406, 'warning');
    }

    public function testPeintureOwaspHotspotInfoReturns406WhenProjectNotInGroupe(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('user@ma-moulinette.fr');
        $user->setListeGroupeFonctionnel(['TeamA']);
        $this->token->method('getUser')->willReturn($user);
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.autre:projet-different']],
        ]);

        $this->hotspotOwaspRepo->expects($this->never())->method('countHotspotOwaspStatus');

        $response = $this->controller->peintureOwaspHotspotInfo($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 406, 'warning');
    }

    public function testPeintureOwaspHotspotListeReturns406WhenProjectNotInGroupe(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('user@ma-moulinette.fr');
        $user->setListeGroupeFonctionnel(['TeamA']);
        $this->token->method('getUser')->willReturn($user);
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.autre:projet-different']],
        ]);

        $this->hotspotOwaspRepo->expects($this->never())->method('countHotspotOwaspMenaces');

        $response = $this->controller->peintureOwaspHotspotListe($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 406, 'warning');
    }

    public function testPeintureOwaspHotspotDetailsReturns406WhenProjectNotInGroupe(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('user@ma-moulinette.fr');
        $user->setListeGroupeFonctionnel(['TeamA']);
        $this->token->method('getUser')->willReturn($user);
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.autre:projet-different']],
        ]);

        $this->hotspotDetailsRepo->expects($this->never())->method('selectHotspotDetailsByStatus');

        $response = $this->controller->peintureOwaspHotspotDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 406, 'warning');
    }

    public function testPeintureOwaspSeverityReturns406WhenProjectNotInGroupe(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('user@ma-moulinette.fr');
        $user->setListeGroupeFonctionnel(['TeamA']);
        $this->token->method('getUser')->willReturn($user);
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.autre:projet-different']],
        ]);

        $this->hotspotOwaspRepo->expects($this->never())->method('countHotspotOwaspMenaceByStatus');

        $response = $this->controller->peintureOwaspSeverity($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
            'menace' => 'a1',
        ]));

        $this->assertJsonStatus($response, 406, 'warning');
    }

    // ═══════════════════════ helpers ═══════════════════════════════════════

    /**
     * @param array<string, mixed> $body
     */
    private function jsonRequest(array $body): Request
    {
        // JSON_FORCE_OBJECT assure que $body vide => "{}" et non "[]",
        // sinon json_decode renverrait un array et property_exists lèverait un TypeError.
        return new Request([], [], [], [], [], [], json_encode($body, JSON_FORCE_OBJECT));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true);
    }

    private function assertJsonStatus(JsonResponse $response, int $code, ?string $type = null): void
    {
        $payload = $this->decode($response);
        $this->assertSame($code, $payload['code'], "Code attendu $code");
        if ($type !== null) {
            $this->assertSame($type, $payload['type'] ?? null);
        }
    }

    /**
     * Ligne OWASP complète avec a1..a10 = 1..10,
     * severity suffixes _blocker=1, _critical=2, _major=3, _minor=4.
     *
     * @return array<string, int|string>
     */
    private function buildOwaspRow(): array
    {
        $row = [
            'referential_owasp' => 2021,
            'version' => '1.0.0',
            'date_version' => '2026-04-22',
        ];
        for ($i = 1; $i <= 10; $i++) {
            $row["a{$i}"] = $i;
            $row["a{$i}_blocker"] = 1;
            $row["a{$i}_critical"] = 2;
            $row["a{$i}_major"] = 3;
            $row["a{$i}_minor"] = 4;
        }
        return $row;
    }
}
