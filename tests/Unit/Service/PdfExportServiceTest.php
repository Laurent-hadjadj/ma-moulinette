<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\BatchExecution;
use App\Entity\BatchExecutionJournal;
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
        $this->params->expects($this->atLeastOnce())
            ->method('get')
            ->with('kernel.project_dir')
            ->willReturn($this->projectDir);

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
        $journal->method('getNomProjet')->willReturn('com.acme:app');
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

        $capturedContext = null;
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

        $this->assertIsArray($capturedContext);
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
                'nomProjet' => 'com.acme:app',
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

        $capturedContext = null;
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

    public function testGenerateRapportPdfHandlesNullUlidFieldsGracefully(): void
    {
        // Les getters executionId/traitementId peuvent renvoyer null → ?->toRfc4122() = null
        $batchExecution = $this->buildBatchExecutionMock(
            executionId: null,
            traitementId: null,
        );

        $capturedContext = null;
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

        $this->service->generateRapportPdf($batchExecution, 'Public');

        $this->assertNull($capturedContext['batch']['executionId']);
        $this->assertNull($capturedContext['batch']['traitementId']);
    }

    private function buildBatchExecutionMock(
        array $collectes = [],
        int $id = 1,
        string $nomTraitement = 'default-batch',
        ?Ulid $executionId = null,
        ?Ulid $traitementId = null,
        string $modeCollecte = 'manual',
        string $utilisateurCollecte = 'user@test.fr',
        ?\DateTimeImmutable $dateEnregistrement = null,
    ): BatchExecution&MockObject {
        $mock = $this->createMock(BatchExecution::class);
        $mock->method('getCollectes')->willReturn(new ArrayCollection($collectes));
        $mock->method('getId')->willReturn($id);
        $mock->method('getNomTraitement')->willReturn($nomTraitement);
        $mock->method('getExecutionId')->willReturn($executionId);
        $mock->method('getTraitementId')->willReturn($traitementId);
        $mock->method('getModeCollecte')->willReturn($modeCollecte);
        $mock->method('getUtilisateurCollecte')->willReturn($utilisateurCollecte);
        $mock->method('getDateEnregistrement')
            ->willReturn($dateEnregistrement ?? new \DateTimeImmutable('2026-01-01 00:00:00'));

        return $mock;
    }
}
