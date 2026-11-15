<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchAutoController;
use App\Controller\Batch\CollecteController;
use App\Repository\BatchTraitementRepository;
use App\Service\ListeProjetPortefeuilleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class BatchAutoControllerTest extends TestCase
{
    private const TOKEN = 'secret-token';

    /** @var CollecteController&MockObject */            private MockObject $collecte;
    /** @var ParameterBagInterface&MockObject */         private MockObject $params;
    /** @var EntityManagerInterface&MockObject */        private MockObject $em;
    /** @var LoggerInterface&MockObject */               private MockObject $logger;
    /** @var LoggerInterface&MockObject */               private MockObject $profiler;
    /** @var ListeProjetPortefeuilleService&MockObject */private MockObject $listeProjetService;
    /** @var BatchTraitementRepository&MockObject */     private MockObject $batchTraitementRepo;

    private BatchAutoController $controller;

    protected function setUp(): void
    {
        $this->collecte = $this->createMock(CollecteController::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->profiler = $this->createMock(LoggerInterface::class);
        $this->listeProjetService = $this->createMock(ListeProjetPortefeuilleService::class);
        $this->batchTraitementRepo = $this->createMock(BatchTraitementRepository::class);

        $this->em->method('getRepository')->willReturn($this->batchTraitementRepo);
        $this->params->method('get')->willReturnMap([
            ['api.client_token', self::TOKEN],
        ]);

        $this->controller = new BatchAutoController(
            $this->collecte,
            $this->params,
            $this->em,
            $this->logger,
            $this->profiler,
            $this->listeProjetService
        );
    }

    /* ============ traitementListe ============ */

    public function testListeReturns400OnInvalidJson(): void
    {
        $request = $this->jsonRequest('not-json');

        $response = $this->controller->traitementListe($request);
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(400, $data['code']);
        $this->assertSame('error', $data['type']);
    }

    public function testListeReturns400WhenTokenMissing(): void
    {
        $request = $this->jsonRequest(['foo' => 'bar']);

        $response = $this->controller->traitementListe($request);
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testListeReturns403WhenTokenIsWrong(): void
    {
        $request = $this->jsonRequest(['token' => 'wrong']);

        $response = $this->controller->traitementListe($request);
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(403, $data['code']);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testListeReturnsErrorWhenRepoFails(): void
    {
        $this->batchTraitementRepo->expects($this->once())
            ->method('selectBatchTraitementAutomatiqueListe')
            ->willReturn(['code' => 500, 'erreur' => 'db down']);

        $response = $this->controller->traitementListe($this->jsonRequest(['token' => self::TOKEN]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('db down', $data['trace']);
    }

    public function testListeHappyPath(): void
    {
        $this->batchTraitementRepo->expects($this->once())
            ->method('selectBatchTraitementAutomatiqueListe')
            ->willReturn(['code' => 200, 'liste' => [['id' => 1, 'nom' => 'T1']]]);

        $response = $this->controller->traitementListe($this->jsonRequest(['token' => self::TOKEN]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertCount(1, $data['liste_traitement']);
    }

    /* ============ traitementAuto ============ */

    public function testAutoReturns400WhenJsonIsInvalid(): void
    {
        $response = $this->controller->traitementAuto($this->jsonRequest('garbage'));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testAutoReturns400WhenFieldsAreMissing(): void
    {
        // token only, missing nom_traitement/portefeuille/traitement_id
        $response = $this->controller->traitementAuto($this->jsonRequest(['token' => self::TOKEN]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testAutoReturns403WhenTokenIsWrong(): void
    {
        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => 'bad',
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testAutoReturns404WhenProjectListIsEmpty(): void
    {
        $this->listeProjetService->expects($this->once())
            ->method('listeProjet')
            ->willReturn([
                'code' => 404,
                'type' => 'warning',
                'message' => 'empty',
                'erreur' => 'no rows',
            ]);

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(404, $data['code']);
        $this->assertSame('warning', $data['type']);
    }

    public function testAutoReturnsErrorWhenUpdateFailsBeforeLoop(): void
    {
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['fr.ma-moulinette:ma-moulinette'],
        ]);

        $this->batchTraitementRepo->expects($this->once())
            ->method('updateBatchTraitement')
            ->willReturn(['code' => 500, 'erreur' => 'update fail']);

        $this->collecte->expects($this->never())->method('collecte');

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('update fail', $data['erreur']);
    }

    public function testAutoAbortsLoopWhenCollecteReturns500(): void
    {
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['fr.ma-moulinette:ma-moulinette', 'fr.ma-moulinette:projet-b'],
        ]);

        // First update OK (pre-loop), second update on 500 failure inside loop
        $this->batchTraitementRepo->method('updateBatchTraitement')->willReturn(['code' => 200]);

        $this->collecte->expects($this->once())
            ->method('collecte')
            ->willReturn(['code' => 500, 'compte_rendu' => 'boom']);

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertStringContainsString("collecte du projet fr.ma-moulinette:ma-moulinette", $data['message']);
    }

    public function testAutoHappyPathReturnsReferenceAfterFullLoop(): void
    {
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['fr.ma-moulinette:ma-moulinette'],
        ]);

        // Both updateBatchTraitement calls succeed (before + after loop)
        $this->batchTraitementRepo->method('updateBatchTraitement')->willReturn(['code' => 200]);

        $this->collecte->expects($this->once())
            ->method('collecte')
            ->willReturn(['code' => 200, 'compte_rendu' => 'ok']);

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertStringContainsString('succès', $data['message']);
    }

    public function testAutoReturnsErrorWhenFinalUpdateFails(): void
    {
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['fr.ma-moulinette:ma-moulinette'],
        ]);

        $this->batchTraitementRepo->method('updateBatchTraitement')->willReturnOnConsecutiveCalls(
            ['code' => 200],
            ['code' => 500, 'erreur' => 'final update fail'],
        );

        $this->collecte->method('collecte')->willReturn(['code' => 200, 'compte_rendu' => 'ok']);

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    /* ============ jeton : corps non objet, échec fermé, journaux ============ */

    /**
     * Corps JSON valides mais qui ne sont pas un objet : `property_exists()` levait une TypeError (erreur 500) sur ces
     * routes PUBLIQUES pour un entier, un tableau ou un booléen.
     *
     * @return iterable<string, array{string}>
     */
    public static function corpsNonObjetProvider(): iterable
    {
        yield 'entier' => ['5'];
        yield 'tableau' => ['[1,2]'];
        yield 'booléen' => ['true'];
        yield 'chaîne JSON' => ['"texte"'];
        yield 'null JSON' => ['null'];
    }

    #[DataProvider('corpsNonObjetProvider')]
    public function testListeReturns400WhenBodyIsNotAnObject(string $corps): void
    {
        $this->batchTraitementRepo->expects($this->never())->method('selectBatchTraitementAutomatiqueListe');

        $response = $this->controller->traitementListe($this->jsonRequest($corps));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    #[DataProvider('corpsNonObjetProvider')]
    public function testAutoReturns400WhenBodyIsNotAnObject(string $corps): void
    {
        $this->listeProjetService->expects($this->never())->method('listeProjet');

        $response = $this->controller->traitementAuto($this->jsonRequest($corps));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testListeReturns403WhenTokenIsEmpty(): void
    {
        $this->batchTraitementRepo->expects($this->never())->method('selectBatchTraitementAutomatiqueListe');

        $response = $this->controller->traitementListe($this->jsonRequest(['token' => '']));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testListeReturns403WhenTokenIsNotAString(): void
    {
        $this->batchTraitementRepo->expects($this->never())->method('selectBatchTraitementAutomatiqueListe');

        // Corps JSON brut : le jeton est volontairement un ENTIER, pas une chaîne.
        $response = $this->controller->traitementListe($this->jsonRequest('{"token":123}'));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAutoReturns403WhenTokenIsEmpty(): void
    {
        $this->listeProjetService->expects($this->never())->method('listeProjet');

        $response = $this->controller->traitementAuto($this->jsonRequest($this->corpsAutoComplet('')));

        $this->assertSame(403, $response->getStatusCode());
    }

    /**
     * Échec fermé : jeton attendu non configuré (vide ou valeur d'exemple) → 503, même si le client envoie exactement
     * la même valeur. L'ancienne comparaison `!==` laissait passer un jeton vide face à un jeton attendu vide.
     *
     * @return iterable<string, array{string}>
     */
    public static function jetonAttenduNonConfigureProvider(): iterable
    {
        yield 'jeton attendu vide' => [''];
        yield 'jeton attendu = valeur d\'exemple' => ['changeme'];
    }

    #[DataProvider('jetonAttenduNonConfigureProvider')]
    public function testListeReturns503WhenConfiguredTokenIsNotSet(string $attendu): void
    {
        $this->batchTraitementRepo->expects($this->never())->method('selectBatchTraitementAutomatiqueListe');
        $this->logger->expects($this->once())->method('critical');

        $response = $this->controllerAvecJetonAttendu($attendu)->traitementListe($this->jsonRequest(['token' => $attendu]));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(503, $data['code']);
        $this->assertSame('critical', $data['type']);
    }

    #[DataProvider('jetonAttenduNonConfigureProvider')]
    public function testAutoReturns503WhenConfiguredTokenIsNotSet(string $attendu): void
    {
        $this->listeProjetService->expects($this->never())->method('listeProjet');
        $this->logger->expects($this->once())->method('critical');

        $response = $this->controllerAvecJetonAttendu($attendu)->traitementAuto($this->jsonRequest($this->corpsAutoComplet($attendu)));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(503, $data['code']);
    }

    public function testListeNeJournalisePasLeJetonRecuEnCasDeRefus(): void
    {
        $secret = 'ancien-jeton-valide-a-ne-jamais-journaliser';
        $contextes = [];
        $this->logger->expects($this->once())->method('error')->willReturnCallback(
            function (string|\Stringable $message, array $contexte = []) use (&$contextes): void {
                $contextes[] = $contexte;
            }
        );

        $response = $this->controller->traitementListe($this->jsonRequest(['token' => $secret]));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertCount(1, $contextes);
        $this->assertStringNotContainsString($secret, json_encode($contextes, JSON_THROW_ON_ERROR));
    }

    public function testAutoNeJournalisePasLeJetonRecuEnCasDeRefus(): void
    {
        $secret = 'ancien-jeton-valide-a-ne-jamais-journaliser';
        $contextes = [];
        $this->logger->expects($this->once())->method('error')->willReturnCallback(
            function (string|\Stringable $message, array $contexte = []) use (&$contextes): void {
                $contextes[] = $contexte;
            }
        );

        $response = $this->controller->traitementAuto($this->jsonRequest($this->corpsAutoComplet($secret)));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringNotContainsString($secret, json_encode($contextes, JSON_THROW_ON_ERROR));
    }

    public function testRefusJournaliseLAdresseDuClient(): void
    {
        // Adresse de test, faux positif Sonar (php:S1313).
        $ipTest = '10.0.0.7';
        $contextes = [];
        $this->logger->expects($this->once())->method('error')->willReturnCallback(
            function (string|\Stringable $message, array $contexte = []) use (&$contextes): void {
                $contextes[] = $contexte;
            }
        );
        $request = $this->jsonRequest(['token' => 'mauvais']);
        $request->server->set('REMOTE_ADDR', $ipTest);

        $this->controller->traitementListe($request);

        $this->assertSame($ipTest, $contextes[0]['client_ip']);
    }

    /**
     * Un client légitime qui envoie le BON jeton avec un JSON incomplet voyait son jeton écrit en clair dans le journal
     * (la charge utile décodée était journalisée telle quelle).
     */
    public function testAutoNeJournalisePasLeBonJetonQuandDesClesManquent(): void
    {
        $contextes = [];
        $this->logger->expects($this->once())->method('error')->willReturnCallback(
            function (string|\Stringable $message, array $contexte = []) use (&$contextes): void {
                $contextes[] = $contexte;
            }
        );

        $response = $this->controller->traitementAuto($this->jsonRequest(['token' => self::TOKEN, 'nom_traitement' => 'T']));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(400, $data['code']);
        $this->assertStringNotContainsString(self::TOKEN, json_encode($contextes, JSON_THROW_ON_ERROR));
        // Le reste de la charge utile reste journalisé : seul le jeton est retiré.
        $this->assertSame('T', $contextes[0]['payload']->nom_traitement);
        $this->assertFalse(property_exists($contextes[0]['payload'], 'token'));
    }

    public function testListeConserveLesAutresChampsDeLaChargeUtileInvalideDansLeJournal(): void
    {
        $contextes = [];
        $this->logger->expects($this->once())->method('error')->willReturnCallback(
            function (string|\Stringable $message, array $contexte = []) use (&$contextes): void {
                $contextes[] = $contexte;
            }
        );

        $this->controller->traitementListe($this->jsonRequest(['foo' => 'bar']));

        $this->assertSame('bar', $contextes[0]['payload']->foo);
    }

    /* ============ helper ============ */

    /**
     * Couverture du bloc `catch` de la création du batch (identifiant de traitement qui n'est pas un ULID valide) :
     * rien n'est persisté ni collecté, et le journal n'y contient pas le jeton.
     */
    public function testAutoReturns500WhenTraitementIdIsNotAValidUlid(): void
    {
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['fr.ma-moulinette:ma-moulinette'],
        ]);
        $this->em->expects($this->never())->method('persist');
        $this->collecte->expects($this->never())->method('collecte');
        $contextes = [];
        $this->logger->expects($this->once())->method('error')->willReturnCallback(
            function (string|\Stringable $message, array $contexte = []) use (&$contextes): void {
                $contextes[] = $contexte;
            }
        );

        $corps = $this->corpsAutoComplet(self::TOKEN);
        $corps['traitement_id'] = 'pas-un-ulid';
        $response = $this->controller->traitementAuto($this->jsonRequest($corps));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('error', $data['type']);
        $this->assertSame('pas-un-ulid', $contextes[0]['traitement_id']);
        $this->assertStringNotContainsString(self::TOKEN, json_encode($contextes, JSON_THROW_ON_ERROR));
    }

    /**
     * Même contrôleur, avec un jeton attendu différent de celui de setUp() (ex. non configuré).
     */
    private function controllerAvecJetonAttendu(string $jetonAttendu): BatchAutoController
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([['api.client_token', $jetonAttendu]]);

        return new BatchAutoController(
            $this->collecte,
            $params,
            $this->em,
            $this->logger,
            $this->profiler,
            $this->listeProjetService
        );
    }

    /**
     * @return array<string, string>
     */
    private function corpsAutoComplet(string $jeton): array
    {
        return [
            'token' => $jeton,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ];
    }

    /**
     * @param array<string, string>|string $body
     */
    private function jsonRequest(array|string $body): Request
    {
        $content = is_string($body) ? $body : json_encode($body, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
        return new Request([], [], [], [], [], [], $content);
    }
}
