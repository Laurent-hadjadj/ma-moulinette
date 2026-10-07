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

namespace App\Tests\Unit\Controller\Projet;

use App\Controller\Projet\ApiEnregistrementController;
use App\Entity\{CleanCode, Historique, Utilisateur};
use App\Repository\{CleanCodeRepository, HistoriqueRepository};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiEnregistrementControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */          private MockObject $em;
    /** @var TokenStorageInterface&MockObject */           private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */                  private MockObject $token;
    /** @var LoggerInterface&MockObject */                 private MockObject $logger;
    /** @var HistoriqueRepository&MockObject */            private MockObject $historiqueRepo;
    /** @var CleanCodeRepository&MockObject */              private MockObject $cleanCodeRepo;
    /** @var AuthorizationCheckerInterface&MockObject */   private MockObject $authChecker;

    private ApiEnregistrementController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->historiqueRepo = $this->createMock(HistoriqueRepository::class);
        $this->cleanCodeRepo = $this->createMock(CleanCodeRepository::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);

        // MODIF 2026-09-28 : routage par classe (CleanCode avait auparavant le
        // mock HistoriqueRepository en retour pour toute classe, ce qui masquait
        // silencieusement le contenu réel de $cc via le __call magique de
        // Doctrine\ORM\EntityRepository — cf. session RCI).
        // Pas de stub par défaut sur selectCleanCode ici : un mock non configuré
        // renvoie déjà null (-> $cc = []), et un défaut posé ici primerait sur
        // le willReturn() spécifique d'un test (les stubs PHPUnit sont évalués
        // dans l'ordre d'enregistrement).
        // MODIF 2026-10-06 : RCI suit désormais le même flux que
        // anomalie/nosonar/mesures : valeurs envoyées dans le payload par
        // enregistrement.js (lues depuis le DOM), simple mapping $data->rci_*
        // côté contrôleur. Plus de dépendance à RciRepository ici.
        $this->em->method('getRepository')->willReturnMap([
            [Historique::class, $this->historiqueRepo],
            [CleanCode::class, $this->cleanCodeRepo],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['security.authorization_checker', 'security.token_storage'], true)
        );
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        // Default user for appUser() trait
        $defaultUser = new Utilisateur();
        $defaultUser->setCourriel('default@test');
        $this->token->method('getUser')->willReturn($defaultUser);

        $this->controller = new ApiEnregistrementController($this->em, $this->logger);
        $this->controller->setContainer($container);
    }

    public function testEnregistrementReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->enregistrement($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testEnregistrementReturns400OnInvalidJson(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->enregistrement($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testEnregistrementReturnsErrorOnInsertFailure(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 500, 'erreur' => 'db fail']);

        $response = $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('db fail', $data['trace']);
    }

    public function testEnregistrementReturns23505OnDuplicate(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 23505, 'erreur' => 'duplicate key']);

        $response = $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(23505, $data['code']);
        $this->assertSame('duplicate key', $data['erreur']);
    }

    public function testEnregistrementHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 200]);

        $response = $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    public function testEnregistrementReturns500OnException(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        $this->historiqueRepo->method('insertHistoriqueAjoutProjet')
            ->willThrowException(new \RuntimeException('boom'));

        $response = $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('boom', $data['trace']);
    }

    /**
     * Régression 2026-05-03 : payload UI envoie 'null' (string) ou '' pour les
     * champs numériques absents → INSERT échoue avec SQLSTATE[22P02] sur Postgres
     * (cast 'null' → integer impossible). Le controller doit normaliser ces
     * valeurs en null PHP avant le bind, sans toucher aux champs string.
     */
    public function testEnregistrementNormalizesStringNullAndEmptyToNullPhp(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        // Payload mixé : 'null' string, '' vide, et vrai null PHP sur les champs numériques
        $payload = $this->buildPayload();
        $payload['tests'] = 'null';                // ← string 'null' (cas reproduisant le bug prod)
        $payload['violations'] = '';               // ← string vide
        $payload['reliability_rating'] = null;     // ← vrai null PHP
        $payload['files'] = 'null';                // ← string 'null' sur un autre champ
        // Champs string : on n'y touche pas (maven_key etc.)

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedMap = [];
        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->with($this->callback(function (array $map) use (&$capturedMap) {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->enregistrement($this->jsonRequest($payload));

        // Tous les champs numériques 'null'/''/null doivent être null PHP (pas string)
        $this->assertNull($capturedMap['tests'], 'string "null" doit être normalisée en null PHP');
        $this->assertNull($capturedMap['violations'], 'string vide doit être normalisée en null PHP');
        $this->assertNull($capturedMap['reliability_rating'], 'null PHP doit rester null');
        $this->assertNull($capturedMap['files']);
        // Les champs string ne sont pas touchés (whitelist dans le controller)
        $this->assertSame('k', $capturedMap['maven_key']);
        $this->assertSame('1.0', $capturedMap['version']);
    }

    /* ============ helpers ============ */

    /**
     * @param array<string, mixed>|string $body
     */
    private function jsonRequest(array|string $body): Request
    {
        if (is_string($body)) {
            $content = $body;
        } elseif ($body === []) {
            $content = '{}';
        } else {
            $content = json_encode($body);
        }
        return new Request([], [], [], [], [], [], $content);
    }

    /**
     * MODIF 2026-07-23 : actuator_info valait auparavant toujours [] (jamais
     * persisté par ce chemin interactif) — vérifie que le JSON envoyé par le
     * front (objet décodé depuis json_decode($request->getContent())) est
     * bien transmis tel quel à insertHistoriqueAjoutProjet.
     */
    public function testEnregistrementPropagatesActuatorInfoToHistorique(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        $expectedActuatorInfo = [
            'date_extraction' => '2026-07-23 20:00:26',
            'code' => 200,
            'message' => 'OK',
            'app.version' => '1.9.1',
        ];

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->with($this->anything(), $expectedActuatorInfo, $this->anything())
            ->willReturn(['code' => 200]);

        $payload = $this->buildPayload();
        $payload['actuator_info'] = $expectedActuatorInfo;

        $response = $this->controller->enregistrement($this->jsonRequest($payload));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    /**
     * MODIF 2026-10-06 : RCI suit le même flux que les autres indicateurs
     * (anomalie, nosonar, mesures...) — enregistrement.js lit les
     * valeurs depuis le DOM (posées par peinture.js) et les envoie dans le
     * payload ; le contrôleur se contente de les reporter dans $map, sans
     * requête à la table rci (contrairement au pattern clean_code, propre à
     * la page CoSUI).
     */
    public function testEnregistrementMergesRciGlobalAndModuleIndicatorsFromPayload(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        $capturedMap = [];
        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->with($this->callback(function (array $map) use (&$capturedMap): bool {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $payload = $this->buildPayload();
        $payload['rci_indicator'] = 40.6;
        $payload['rci_rating'] = 'E';
        $payload['rci_ncloc'] = 28188;
        $payload['rci_weight'] = 16732;
        $payload['rci_frontend_indicator'] = 77.55;
        $payload['rci_frontend_rating'] = 'D';
        $payload['rci_frontend_ncloc'] = 16590;
        $payload['rci_frontend_weight'] = 3725;
        $payload['rci_backend_indicator'] = 0.0;
        $payload['rci_backend_rating'] = 'E';
        $payload['rci_backend_ncloc'] = 11330;
        $payload['rci_backend_weight'] = 12995;
        $payload['rci_autre_indicator'] = null;
        $payload['rci_autre_rating'] = null;
        $payload['rci_autre_ncloc'] = 0;
        $payload['rci_autre_weight'] = 0;
        $payload['rci_inconnu_indicator'] = 95.52;
        $payload['rci_inconnu_rating'] = 'A';
        $payload['rci_inconnu_ncloc'] = 268;
        $payload['rci_inconnu_weight'] = 12;

        $this->controller->enregistrement($this->jsonRequest($payload));

        $this->assertSame(40.6, $capturedMap['rci_indicator']);
        $this->assertSame('E', $capturedMap['rci_rating']);
        $this->assertSame(28188, $capturedMap['rci_ncloc']);
        $this->assertSame(16732, $capturedMap['rci_weight']);
        $this->assertSame(77.55, $capturedMap['rci_frontend_indicator']);
        $this->assertSame('D', $capturedMap['rci_frontend_rating']);
        $this->assertSame(16590, $capturedMap['rci_frontend_ncloc']);
        $this->assertSame(3725, $capturedMap['rci_frontend_weight']);
        $this->assertSame('E', $capturedMap['rci_backend_rating']);
        $this->assertNull($capturedMap['rci_autre_indicator']);
        $this->assertSame(0, $capturedMap['rci_autre_ncloc']);
        $this->assertSame('A', $capturedMap['rci_inconnu_rating']);
        $this->assertSame(12, $capturedMap['rci_inconnu_weight']);
    }

    /**
     * MODIF 2026-10-06 : répartition par portée SonarQube (MAIN/TEST), globale
     * et par catégorie — même flux DOM -> payload que RCI et les autres
     * indicateurs.
     */
    public function testEnregistrementMergesScopeMainTestIndicatorsFromPayload(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        $capturedMap = [];
        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->with($this->callback(function (array $map) use (&$capturedMap): bool {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $payload = $this->buildPayload();
        $payload['scope_main'] = 1500;
        $payload['scope_test'] = 456;
        $payload['bug_scope_main'] = 300;
        $payload['bug_scope_test'] = 50;
        $payload['vulnerability_scope_main'] = 600;
        $payload['vulnerability_scope_test'] = 5;
        $payload['code_smell_scope_main'] = 600;
        $payload['code_smell_scope_test'] = 401;

        $this->controller->enregistrement($this->jsonRequest($payload));

        $this->assertSame(1500, $capturedMap['scope_main']);
        $this->assertSame(456, $capturedMap['scope_test']);
        $this->assertSame(300, $capturedMap['bug_scope_main']);
        $this->assertSame(50, $capturedMap['bug_scope_test']);
        $this->assertSame(600, $capturedMap['vulnerability_scope_main']);
        $this->assertSame(5, $capturedMap['vulnerability_scope_test']);
        $this->assertSame(600, $capturedMap['code_smell_scope_main']);
        $this->assertSame(401, $capturedMap['code_smell_scope_test']);
    }

    public function testEnregistrementLeavesScopeColumnsNullWhenAbsentFromPayload(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        // buildPayload() ne porte aucune clé scope_* (projet jamais collecté côté scopes).
        $capturedMap = [];
        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->with($this->callback(function (array $map) use (&$capturedMap): bool {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));

        $this->assertNull($capturedMap['scope_main']);
        $this->assertNull($capturedMap['bug_scope_main']);
    }

    public function testEnregistrementLeavesRciColumnsNullWhenAbsentFromPayload(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        // buildPayload() ne porte aucune clé rci_* (projet jamais collecté côté RCI).
        $capturedMap = [];
        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->with($this->callback(function (array $map) use (&$capturedMap): bool {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));

        $this->assertNull($capturedMap['rci_indicator']);
        $this->assertNull($capturedMap['rci_rating']);
        $this->assertNull($capturedMap['rci_frontend_indicator']);
    }

    public function testEnregistrementSendsEmptyActuatorInfoWhenAbsent(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->token->method('getUser')->willReturn($this->makeUser());

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->with($this->anything(), [], $this->anything())
            ->willReturn(['code' => 200]);

        $response = $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    private function makeUser(): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('u@x');
        return $u;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        // Payload aligné sur les clés SonarQube (= colonnes DB historique).
        return [
            'maven_key' => 'k',
            'analyse_key' => 'a',
            'version' => '1.0',
            'date_version' => '2026-04-10',
            'project_name' => 'App',
            'version_release' => 1,
            'version_snapshot' => 0,
            'version_autre' => 0,
            'suppress_warning' => 0,
            'no_sonar' => 0,
            'todo' => 0,
            'logger_info' => 0,
            'logger_warn' => 0,
            'logger_error' => 0,
            'logger_debug' => 0,
            'lines' => 1000,
            'ncloc' => 800,
            'files' => 5,
            'classes' => 10,
            'functions' => 20,
            'coverage' => 80,
            'duplicated_lines_density' => 1,
            'sqale_debt_ratio' => 2,
            'tests' => 10,
            'violations' => 5,
            'sqale_index' => 100,
            'bugs' => 1,
            'vulnerabilities' => 0,
            'code_smells' => 3,
            'bug_blocker' => 0,
            'bug_critical' => 1,
            'bug_major' => 0,
            'bug_minor' => 0,
            'bug_info' => 0,
            'vulnerability_blocker' => 0,
            'vulnerability_critical' => 0,
            'vulnerability_major' => 0,
            'vulnerability_minor' => 0,
            'vulnerability_info' => 0,
            'code_smell_blocker' => 0,
            'code_smell_critical' => 0,
            'code_smell_major' => 0,
            'code_smell_minor' => 0,
            'code_smell_info' => 3,
            'repartition_frontend' => 1,
            'repartition_backend' => 2,
            'repartition_autre' => 0,
            'repartition_inconnu' => 0,
            'blocker_violations' => 0,
            'critical_violations' => 1,
            'major_violations' => 0,
            'minor_violations' => 0,
            'info_violations' => 0,
            'reliability_rating' => 2.0,
            'security_rating' => 1.0,
            'sqale_rating' => 1.0,
            'security_review_rating' => 1.0,
            'menace_potentielle_totale' => 0,
            'menace_potentielle_to_review_high' => 0,
            'menace_potentielle_to_review_medium' => 0,
            'menace_potentielle_to_review_low' => 0,
            'menace_potentielle_reviewed_high' => 0,
            'menace_potentielle_reviewed_medium' => 0,
            'menace_potentielle_reviewed_low' => 0,
        ];
    }
}
