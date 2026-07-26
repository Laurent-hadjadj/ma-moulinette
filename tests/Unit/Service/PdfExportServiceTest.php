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

use App\Entity\{BatchExecution, BatchExecutionJournal};
use App\Service\PdfExportService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;

/**
 * Tests unitaires pour PdfExportService.
 *
 * Dompdf est instancié directement à l'intérieur de la méthode et ne peut
 * pas être mocké ; on valide donc qu'on obtient un binaire PDF valide
 * ("%PDF-" en en-tête) en lui fournissant un HTML minimal via un Twig stub.
 * Le logo est lu depuis le projet réel (kernel.project_dir) — le test
 * nécessite donc `assets/images/marque-mm-400x128.png`.
 */
#[AllowMockObjectsWithoutExpectations]
class PdfExportServiceTest extends TestCase
{
    /** @var Environment&MockObject */
    private MockObject $twig;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $params;

    private PdfExportService $service;
    private string $projectDir;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->params = $this->createMock(ParameterBagInterface::class);

        // Le service lit `kernel.project_dir` pour localiser le logo — on pointe sur le vrai projet.
        $this->projectDir = realpath(__DIR__ . '/../../..');
        $this->params->method('get')->willReturnMap([
            ['kernel.project_dir', $this->projectDir],
        ]);

        $this->service = new PdfExportService($this->twig, $this->params);
    }

    public function testGenerateRapportPdfReturnsValidPdfBinary(): void
    {
        $batchExecution = $this->buildBatchExecutionMock();

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html><body><h1>Test Rapport</h1></body></html>');

        $pdf = $this->service->generateRapportPdf($batchExecution, 'Confidentiel');

        // Un PDF valide commence par "%PDF-" et fait plusieurs Ko au minimum
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testGenerateRapportPdfPassesExpectedDataToTwigTemplate(): void
    {
        $executionId = new Ulid();
        $traitementId = new Ulid();
        $dateEnregistrement = new \DateTimeImmutable('2026-04-22 09:30:00');

        $journal = $this->createMock(BatchExecutionJournal::class);
        $journal->method('getId')->willReturn(11);
        $journal->method('getNomProjet')->willReturn('fr.ma-moulinette:ma-moulinette');
        $journal->method('getPortefeuille')->willReturn('PF-alpha');
        $journal->method('getCode')->willReturn(200);
        $journal->method('getDateExecution')
            ->willReturn(new \DateTimeImmutable('2026-04-22 09:35:17'));

        $batchExecution = $this->buildBatchExecutionMock(
            collectes: [$journal],
            id: 42,
            nomTraitement: 'batch-de-test',
            executionId: $executionId,
            traitementId: $traitementId,
            modeCollecte: 'manual',
            utilisateurCollecte: 'admin@ma-moulinette.fr',
            dateEnregistrement: $dateEnregistrement,
        );

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedContext = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '/rapport/rapport-zurb-foundation.html.twig',
                $this->callback(function (array $context) use (&$capturedContext) {
                    $capturedContext = $context;
                    return true;
                })
            )
            ->willReturn('<html><body>ok</body></html>');

        $this->service->generateRapportPdf($batchExecution, 'Interne');

        $this->assertSame('Interne', $capturedContext['document_type']);
        $this->assertIsString($capturedContext['logoBase64']);
        $this->assertNotEmpty($capturedContext['logoBase64']);

        $batch = $capturedContext['batch'];
        $this->assertSame(42, $batch['id']);
        $this->assertSame('batch-de-test', $batch['nomTraitement']);
        $this->assertSame($executionId->toRfc4122(), $batch['executionId']);
        $this->assertSame($traitementId->toRfc4122(), $batch['traitementId']);
        $this->assertSame('manual', $batch['modeCollecte']);
        $this->assertSame('admin@ma-moulinette.fr', $batch['utilisateurCollecte']);
        $this->assertSame('22/04/2026 09:30', $batch['dateEnregistrement']);

        $this->assertCount(1, $batch['collectes']);
        $this->assertSame(
            [
                'id' => 11,
                'nomProjet' => 'fr.ma-moulinette:ma-moulinette',
                'portefeuille' => 'PF-alpha',
                'code' => 200,
                'dateExecution' => '22/04/2026 09:35',
            ],
            $batch['collectes'][0]
        );
    }

    public function testGenerateRapportPdfFallsBackToDefaultDocumentTypeWhenNull(): void
    {
        $batchExecution = $this->buildBatchExecutionMock();

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedContext = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $context) use (&$capturedContext) {
                    $capturedContext = $context;
                    return true;
                })
            )
            ->willReturn('<html></html>');

        $this->service->generateRapportPdf($batchExecution, null);

        // Fallback : 'Document confidentiel' quand $document_type est null
        $this->assertSame('Document confidentiel', $capturedContext['document_type']);
    }

    /* MODIF 2026-05-05 : suppression de
     * `testGenerateRapportPdfHandlesNullUlidFieldsGracefully`. Le scenario testait
     * `getExecutionId()` / `getTraitementId()` renvoyant null, mais l’entité
     * BatchExecution a depuis ete alignée sur le DDL (Ulid non-nullable).
     * Ce test couvrait un comportement qui ne peut plus se produire. */

    /* MODIF 2026-05-17 : tests generateOwaspPdf.
     * Même pattern que generateRapportPdf : Dompdf instancié réellement,
     * Twig mocké, params mocké sur kernel.project_dir. */

    public function testGenerateOwaspPdfReturnsValidPdfBinary(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body><h1>OWASP</h1></body></html>');

        $pdf = $this->service->generateOwaspPdf(['maven_key' => 'fr.ma-moulinette:ma-moulinette'], 'Document Interne');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testGenerateOwaspPdfFallsBackToConfidentielWhenDocumentTypeIsNull(): void
    {
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '/rapport/rapport-owasp.html.twig',
                $this->callback(function (array $ctx) use (&$capturedCtx) {
                    $capturedCtx = $ctx;
                    return true;
                })
            )
            ->willReturn('<html></html>');

        $this->service->generateOwaspPdf([], null);

        $this->assertSame('Document confidentiel', $capturedCtx['document_type']);
    }

    public function testGenerateOwaspPdfPassesDataToTwigAsRapport(): void
    {
        $data = ['maven_key' => 'fr.ma-moulinette:ma-moulinette', 'version' => '2.0'];

        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                '/rapport/rapport-owasp.html.twig',
                $this->callback(function (array $ctx) use (&$capturedCtx) {
                    $capturedCtx = $ctx;
                    return true;
                })
            )
            ->willReturn('<html></html>');

        $this->service->generateOwaspPdf($data, 'Document Interne');

        $this->assertSame($data,              $capturedCtx['rapport']);
        $this->assertSame('Document Interne', $capturedCtx['document_type']);
        $this->assertNotEmpty($capturedCtx['logoBase64']);
    }

    /* MODIF 2026-05-19 : tests generateCleanCodeSynthesePdf */

    public function testGenerateCleanCodeSynthesePdfReturnsValidPdfBinary(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body><h1>Synthèse CC</h1></body></html>');

        $pdf = $this->service->generateCleanCodeSynthesePdf([
            'projets'     => [],
            'scope_label' => 'Périmètre : TeamA',
        ], 'Document public');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testGenerateCleanCodeSynthesePdfFallsBackToPublicWhenDocumentTypeIsNull(): void
    {
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html></html>');

        $this->service->generateCleanCodeSynthesePdf([
            'projets'     => [],
            'scope_label' => '',
        ], null);

        // La méthode ne plante pas avec document_type null (mock once() couvre l'assertion)
    }

    public function testGenerateCleanCodeSynthesePdfPassesExpectedDataToTwig(): void
    {
        $projets = [
            [
                'project_name'  => 'mon-app',
                'version'       => '1.0.0',
                'note_mnt'      => 'A',
                'note_fia'      => 'B',
                'note_sec'      => 'C',
                'note_hsp'      => 'A',
                'risk_score'    => 1.5,
                'risk_level'    => 'medium',
                'bugs'          => 0,
                'vulnerabilities' => 2,
                'coverage'      => 78.5,
            ],
        ];

        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'rapport/clean-code/_synthese-landscape.html.twig',
                $this->callback(function (array $ctx) use (&$capturedCtx) {
                    $capturedCtx = $ctx;
                    return true;
                })
            )
            ->willReturn('<html></html>');

        $this->service->generateCleanCodeSynthesePdf([
            'projets'     => $projets,
            'scope_label' => 'Périmètre : TeamA',
        ], 'Document interne');

        $this->assertSame($projets,           $capturedCtx['projets']);
        $this->assertSame('Périmètre : TeamA', $capturedCtx['scope_label']);
        $this->assertNotEmpty($capturedCtx['logoBase64']);
        $this->assertNotEmpty($capturedCtx['date_generation']);
    }

    /* MODIF 2026-07-20 : tests generateUserRoleLogPdf */

    public function testGenerateUserRoleLogPdfReturnsValidPdfBinary(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body><h1>Journal des rôles</h1></body></html>');

        $pdf = $this->service->generateUserRoleLogPdf([], '2 ligne(s) sélectionnée(s)');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testGenerateUserRoleLogPdfPassesExpectedDataToTwig(): void
    {
        $lignes = [
            [
                'id' => 1,
                'userEmail' => 'emma.durand@ma-moulinette.fr',
                'editorEmail' => 'admin@ma-moulinette.fr',
                'oldRoles' => ['ROLE_UTILISATEUR'],
                'newRoles' => ['ROLE_GESTIONNAIRE'],
                'oldActive' => true,
                'newActive' => true,
                'alerts' => [],
                'date' => '20/07/2026 10:30:00',
            ],
        ];

        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'rapport/journal-roles/_rapport.html.twig',
                $this->callback(function (array $ctx) use (&$capturedCtx) {
                    $capturedCtx = $ctx;
                    return true;
                })
            )
            ->willReturn('<html></html>');

        $this->service->generateUserRoleLogPdf($lignes, '1 ligne(s) sélectionnée(s)', 'Document interne');

        $this->assertSame($lignes, $capturedCtx['lignes']);
        $this->assertSame('1 ligne(s) sélectionnée(s)', $capturedCtx['filtre_label']);
        $this->assertNotEmpty($capturedCtx['logoBase64']);
        $this->assertNotEmpty($capturedCtx['date_generation']);
    }

    /* MODIF 2026-06-07 : tests generateDcPdf + generateSuiviPdf.
     * Ces méthodes appellent params->get('rapport.pdf.watermark.mode') en plus de
     * 'kernel.project_dir' : création d'un service dédié via makeServiceForSuiviDc()
     * pour éviter tout conflit avec l'expectation with('kernel.project_dir') du setUp. */

    private function makeServiceForSuiviDc(string $watermarkMode = 'never'): PdfExportService
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('<html><body><p>test</p></body></html>');

        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['kernel.project_dir', $this->projectDir],
            ['rapport.pdf.watermark.mode', $watermarkMode],
        ]);

        return new PdfExportService($twig, $params);
    }

    public function testGenerateDcPdfReturnsValidPdfBinaryWithoutFindings(): void
    {
        $service = $this->makeServiceForSuiviDc('never');

        $pdf = $service->generateDcPdf([
            'project_group'    => 'fr.ma-moulinette',
            'project_artifact' => 'ma-moulinette',
            'project_version'  => '1.0.0',
            'findings'         => [],
        ], 'Document interne');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testGenerateDcPdfReturnsValidPdfBinaryWithFindings(): void
    {
        $service = $this->makeServiceForSuiviDc('never');

        $pdf = $service->generateDcPdf([
            'project_group'    => 'fr.ma-moulinette',
            'project_artifact' => 'ma-moulinette',
            'project_version'  => '2.0.0',
            'findings'         => [['cve' => 'CVE-2025-1234', 'severity' => 'HIGH']],
        ], 'Document interne');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testGenerateSuiviPdfReturnsValidPdfBinary(): void
    {
        $service = $this->makeServiceForSuiviDc('never');

        $pdf = $service->generateSuiviPdf([
            'maven_key'    => 'fr.ma-moulinette:ma-moulinette',
            'project_name' => 'Mon Application',
        ], 'Document public');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function testGenerateSuiviPdfWithWatermarkAlwaysMode(): void
    {
        $service = $this->makeServiceForSuiviDc('always');

        $pdf = $service->generateSuiviPdf([
            'maven_key'    => 'fr.ma-moulinette:ma-moulinette',
            'project_name' => 'Mon Application',
        ], 'Confidentiel');

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    /**
     * @param array<int, BatchExecutionJournal> $collectes
     */
    private function buildBatchExecutionMock(
        array $collectes = [],
        int $id = 1,
        string $nomTraitement = 'default-batch',
        ?Ulid $executionId = null,
        ?Ulid $traitementId = null,
        string $modeCollecte = 'manual',
        string $utilisateurCollecte = 'user@ma-moulinette.fr',
        ?\DateTimeImmutable $dateEnregistrement = null,
    ): BatchExecution&MockObject {
        $mock = $this->createMock(BatchExecution::class);
        $mock->method('getCollectes')->willReturn(new ArrayCollection($collectes));
        $mock->method('getId')->willReturn($id);
        $mock->method('getNomTraitement')->willReturn($nomTraitement);
        /* MODIF 2026-05-05 : getExecutionId/getTraitementId
         * sont desormais non-nullables cote entity. Mock retourne un Ulid par defaut. */
        $mock->method('getExecutionId')->willReturn($executionId ?? new Ulid());
        $mock->method('getTraitementId')->willReturn($traitementId ?? new Ulid());
        $mock->method('getModeCollecte')->willReturn($modeCollecte);
        $mock->method('getUtilisateurCollecte')->willReturn($utilisateurCollecte);
        $mock->method('getDateEnregistrement')
            ->willReturn($dateEnregistrement ?? new \DateTimeImmutable('2026-01-01 00:00:00'));

        return $mock;
    }
}
