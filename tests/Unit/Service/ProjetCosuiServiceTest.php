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

use App\Entity\{Historique, Repartition};
use App\Repository\{HistoriqueRepository, RepartitionRepository};
use App\Service\ProjetCosuiService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class ProjetCosuiServiceTest extends TestCase
{
    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $params;

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var HistoriqueRepository&MockObject */
    private MockObject $histoRepo;

    /** @var RepartitionRepository&MockObject */
    private MockObject $repartRepo;

    private ProjetCosuiService $service;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->histoRepo = $this->createMock(HistoriqueRepository::class);
        $this->repartRepo = $this->createMock(RepartitionRepository::class);

        // Constructor lit tous les paramètres
        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo-test.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'DEV'],
            ['version', '2.0.0'],
        ]);

        // getRepository dispatch selon l'entité
        $this->em->method('getRepository')->willReturnMap([
            [Historique::class, $this->histoRepo],
            [Repartition::class, $this->repartRepo],
        ]);

        $this->service = new ProjetCosuiService($this->logger, $this->params, $this->em);
    }

    // ─────────────────────────── initializers statiques ───────────────────────────

    public function testInitialRenderContainsAllInitializerKeys(): void
    {
        $render = $this->service->initialRender();

        // genericRender injecte les paramètres d'entreprise
        $this->assertSame('logo-test.png', $render['logo_entreprise']);
        $this->assertSame('MM', $render['marque_entreprise_short']);
        $this->assertSame('DEV', $render['env']);
        $this->assertSame('2.0.0', $render['version']);

        // initializeNotesRender fournit les clés de notes actuelles
        $this->assertSame('NaN', $render['monApplication']);
        $this->assertSame('F', $render['note_reliability']);
        $this->assertSame(0, $render['bug_blocker']);

        // initializeReferenceRender fournit les initial_*
        $this->assertSame('F', $render['initial_note_reliability']);
        $this->assertSame(0, $render['initial_bug_blocker']);
        $this->assertSame(100, $render['initial_sqale_debt_ratio']);

        // initializeEvolutionRender fournit evolution_*
        $this->assertSame(0, $render['evolution_hotspot']);
        $this->assertSame(0, $render['evolution_bug_blocker']);

        // chartDataRender() est appelé EN DERNIER dans initialRender(), ses chaînes vides
        // écrasent les valeurs d'initializeChartRender(). Ce comportement est volontaire
        // — initialRender() est utilisé comme squelette vierge pour un nouveau projet.
        $this->assertSame('', $render['dataset1']);
        $this->assertSame('', $render['label1']);
    }

    public function testInitializeNotesRenderHasDynamicTypeSeverityKeys(): void
    {
        $render = ProjetCosuiService::initializeNotesRender();

        // Matrice reliability/vulnerability/code_smell × blocker/critical/major
        foreach (['reliability', 'vulnerability', 'code_smell'] as $type) {
            foreach (['blocker', 'critical', 'major'] as $severity) {
                $this->assertArrayHasKey("{$type}_{$severity}", $render);
                $this->assertArrayHasKey("nombre_presentation_{$type}_{$severity}", $render);
                $this->assertArrayHasKey("nombre_metier_{$type}_{$severity}", $render);
                $this->assertSame('--', $render["nombre_metier_{$type}_{$severity}"]);
            }
        }
    }

    public function testInitializeReferenceRenderHasInitialAndModalKeys(): void
    {
        $render = ProjetCosuiService::initializeReferenceRender();

        foreach (['bug', 'vulnerability', 'code_smell'] as $type) {
            foreach (['blocker', 'critical', 'major'] as $severity) {
                $this->assertSame(0, $render["initial_{$type}_{$severity}"]);
                $this->assertSame(0, $render["modal_initial_{$type}_{$severity}"]);
            }
        }
        $this->assertSame(0, $render['modal_initial_hotspot']);
    }

    public function testInitializeEvolutionRenderContainsAllCombinations(): void
    {
        $render = ProjetCosuiService::initializeEvolutionRender();

        $this->assertArrayHasKey('evolution_hotspot', $render);
        foreach (['hotspot', 'code_smell', 'vulnerability', 'bug'] as $type) {
            foreach (['blocker', 'critical', 'major'] as $severity) {
                $this->assertArrayHasKey("evolution_{$type}_{$severity}", $render);
                $this->assertSame(0, $render["evolution_{$type}_{$severity}"]);
            }
        }
    }

    public function testChartDataRenderReturnsEmptyDefaults(): void
    {
        $this->assertSame(
            ['dataset1' => '', 'dataset2' => '', 'label1' => '', 'label2' => ''],
            ProjetCosuiService::chartDataRender()
        );
    }

    // ─────────────────────────── generateRender : chemins d'erreur ───────────────────────────

    public function testGenerateRenderReturnsErrorWhenNotesQueryFails(): void
    {
        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetLast')
            ->with(['maven_key' => 'fr.ma-moulinette:ma-moulinette'])
            ->willReturn(['code' => 500, 'erreur' => 'DB down']);

        $result = $this->service->generateRender('fr.ma-moulinette:ma-moulinette');

        $this->assertSame(500, $result['code']);
        $this->assertSame('error', $result['type']);
        $this->assertSame('DB down', $result['trace']);
        $this->assertStringContainsString('données projet actuel', $result['message']);
    }

    public function testGenerateRenderReturns404WhenHistoriqueIsEmpty(): void
    {
        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetLast')
            ->willReturn(['code' => 200, 'infos' => []]);

        $result = $this->service->generateRender('fr.ma-moulinette:ma-moulinette');

        $this->assertSame(404, $result['code']);
        $this->assertSame('warning', $result['type']);
    }

    public function testGenerateRenderReturnsErrorWhenReferenceQueryFails(): void
    {
        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetLast')
            ->willReturn([
                'code' => 200,
                'infos' => [$this->sampleNotesRow()],
            ]);

        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetReference')
            ->willReturn(['code' => 503, 'erreur' => 'ref failed']);

        $result = $this->service->generateRender('fr.ma-moulinette:ma-moulinette');

        $this->assertSame(503, $result['code']);
        $this->assertSame('error', $result['type']);
        $this->assertSame('ref failed', $result['trace']);
    }

    public function testGenerateRenderReturnsCriticalWhenSetupQueryThrows(): void
    {
        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetLast')
            ->willReturn(['code' => 200, 'infos' => [$this->sampleNotesRow()]]);

        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetReference')
            ->willReturn(['code' => 200, 'reference' => [$this->sampleReferenceRow()]]);

        $this->repartRepo->expects($this->once())
            ->method('findLatestSetupByMavenKey')
            ->willThrowException(new \RuntimeException('setup kaboom'));

        $this->logger->expects($this->atLeastOnce())->method('critical');

        $result = $this->service->generateRender('fr.ma-moulinette:ma-moulinette');

        $this->assertSame(500, $result['code']);
        $this->assertSame('critical', $result['type']);
    }

    public function testGenerateRenderHappyPathWhenSetupIsNaN(): void
    {
        // Quand setup renvoie 'NaN' : traitement() shortcircuite et ne touche pas à createQueryBuilder.
        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetLast')
            ->willReturn(['code' => 200, 'infos' => [$this->sampleNotesRow()]]);

        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetReference')
            ->willReturn(['code' => 200, 'reference' => [$this->sampleReferenceRow()]]);

        $this->repartRepo->expects($this->once())
            ->method('findLatestSetupByMavenKey')
            ->with('fr.ma-moulinette:ma-moulinette')
            ->willReturn(['code' => 200, 'result' => 'NaN']);

        // createQueryBuilder NE doit PAS être appelé (setup='NaN' court-circuite)
        $this->em->expects($this->never())->method('createQueryBuilder');

        $result = $this->service->generateRender('fr.ma-moulinette:ma-moulinette');

        // Notes actuelles injectées
        $this->assertSame('Mon App', $result['monApplication']);
        $this->assertSame('1.2.3', $result['version_application']);
        $this->assertSame('RELEASE', $result['type_application']);
        $this->assertSame('A', $result['note_reliability']);
        $this->assertSame(5, $result['bug_blocker']);

        // Référence injectée (initial_* + modal_initial_*)
        $this->assertSame('1.0.0', $result['initial_version_application']);
        $this->assertSame('B', $result['initial_note_reliability']);
        $this->assertSame(10, $result['initial_bug_blocker']);
        // Les initial_* se retrouvent en modal_initial_* via injectNotesReference
        $this->assertSame(10, $result['modal_initial_bug_blocker']);

        // setup → 'NaN'
        $this->assertSame('NaN', $result['setup']);

        // Radar chart construit à partir des notes
        $this->assertIsString($result['dataset1']);
        $this->assertSame('1.0.0', $result['label1']);
        $this->assertSame('1.2.3', $result['label2']);

        // Evolutions calculées : initial_bug_blocker=10 → bug_blocker=5 (baisse = 'up')
        $this->assertSame('up', $result['evolution_bug_blocker']);
    }

    public function testGenerateRenderProceedsWhenReferenceIsEmpty(): void
    {
        // Cas : reference renvoie result=false → on continue sans injecter les données de référence.
        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetLast')
            ->willReturn(['code' => 200, 'infos' => [$this->sampleNotesRow()]]);

        $this->histoRepo->expects($this->once())
            ->method('selectHistoriqueProjetReference')
            ->willReturn(['code' => 200, 'reference' => []]);

        $this->repartRepo->expects($this->once())
            ->method('findLatestSetupByMavenKey')
            ->willReturn(['code' => 200, 'result' => 'NaN']);

        $result = $this->service->generateRender('fr.ma-moulinette:ma-moulinette');

        // Pas d'erreur, les valeurs par défaut 'F' restent en place pour les initial_*
        $this->assertSame('F', $result['initial_note_reliability']);
        // Mais les notes actuelles sont OK
        $this->assertSame('A', $result['note_reliability']);
    }

    // ─────────────────────────── fixtures ───────────────────────────

    private function sampleNotesRow(): array
    {
        return [
            'name' => 'Mon App',
            'version' => '1.2.3-RELEASE',
            'date_version' => '22/04/2026',
            'note_reliability' => 'A',
            'note_security' => 'B',
            'note_hotspot' => 'C',
            'note_sqale' => 'A',
            'bug_blocker' => 5,
            'bug_critical' => 2,
            'bug_major' => 10,
            'vulnerability_blocker' => 0,
            'vulnerability_critical' => 1,
            'vulnerability_major' => 3,
            'code_smell_blocker' => 0,
            'code_smell_critical' => 7,
            'code_smell_major' => 20,
            'nombre_hotspot' => 4,
            'coverage' => 75,
            'sqale_debt_ratio' => 8,
        ];
    }

    private function sampleReferenceRow(): array
    {
        return [
            'version' => '1.0.0-RELEASE',
            'date_version' => '01/01/2026',
            'note_reliability' => 'B',
            'note_security' => 'C',
            'note_hotspot' => 'B',
            'note_sqale' => 'A',
            'bug_blocker' => 10,
            'bug_critical' => 5,
            'bug_major' => 15,
            'vulnerability_blocker' => 2,
            'vulnerability_critical' => 3,
            'vulnerability_major' => 4,
            'code_smell_blocker' => 1,
            'code_smell_critical' => 8,
            'code_smell_major' => 25,
            'nombre_hotspot' => 6,
            'coverage' => 60,
            'sqale_debt_ratio' => 12,
        ];
    }
}
