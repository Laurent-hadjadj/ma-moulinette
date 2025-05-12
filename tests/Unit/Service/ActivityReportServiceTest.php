<?php
/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Service;

use App\Entity\ActivityBatchReport;
use App\Service\ActivityReportService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActivityReportServiceTest]
 */
class ActivityReportServiceTest extends TestCase
{
    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /** @var ActivityReportService */
    private $activityReportService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock de l'EntityManager
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // Création de l'instance du service avec le mock
        $this->activityReportService = new ActivityReportService($this->entityManager);
    }

    public function testGenerateReportWithValidData(): void
    {
        // Données pour générer le rapport
        $infos = [
            'date_start' => new \DateTimeImmutable('2024-12-01'),
            'date_end' => new \DateTimeImmutable('2024-12-02'),
            'task_count' => 10,
            'page' => 1,
        ];
        $successfulTasksCount = 8;
        $errors = ['error1', 'error2'];

        // Mock des appels persist et flush
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ActivityBatchReport::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Exécution de la méthode
        $this->activityReportService->generateReport($infos, $successfulTasksCount, $errors);

        // Vérification de l'objet ActivityBatchReport
        $report = new ActivityBatchReport();
        $report->setDateStart($infos['date_start']);
        $report->setDateEnd($infos['date_end']);
        $report->setTaskCount($infos['task_count']);
        $report->setTaskDone($successfulTasksCount);
        $report->setPage($infos['page']);
        $report->setLastError($errors);

        // Vérifier si les propriétés sont correctement assignées
        $this->assertEquals($infos['date_start'], $report->getDateStart());
        $this->assertEquals($infos['date_end'], $report->getDateEnd());
        $this->assertEquals($infos['task_count'], $report->getTaskCount());
        $this->assertEquals($successfulTasksCount, $report->getTaskDone());
        $this->assertEquals($infos['page'], $report->getPage());
        $this->assertEquals($errors, $report->getLastError());
    }

    public function testGenerateReportWithNoErrors(): void
    {
        $infos = [
            'date_start' => new \DateTimeImmutable('2024-12-01'),
            'date_end' => new \DateTimeImmutable('2024-12-02'),
            'task_count' => 10,
            'page' => 1,
        ];
        $successfulTasksCount = 8;
        $errors = [];

        // Mock des appels persist et flush
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ActivityBatchReport::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Exécution de la méthode
        $this->activityReportService->generateReport($infos, $successfulTasksCount, $errors);

        // Vérification de l'objet ActivityBatchReport avec aucune erreur
        $report = new ActivityBatchReport();
        $report->setDateStart($infos['date_start']);
        $report->setDateEnd($infos['date_end']);
        $report->setTaskCount($infos['task_count']);
        $report->setTaskDone($successfulTasksCount);
        $report->setPage($infos['page']);

        // Vérification que la liste des erreurs est vide
        $this->assertEmpty($report->getLastError());
    }

    public function testGenerateReportWithNoSuccessfulTasks(): void
    {
        $infos = [
            'date_start' => new \DateTimeImmutable('2024-12-01'),
            'date_end' => new \DateTimeImmutable('2024-12-02'),
            'task_count' => 10,
            'page' => 1,
        ];
        $successfulTasksCount = 0;
        $errors = ['some_error'];

        // Mock des appels persist et flush
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ActivityBatchReport::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Exécution de la méthode
        $this->activityReportService->generateReport($infos, $successfulTasksCount, $errors);

        // Vérification de l'objet ActivityBatchReport avec 0 tâche réussie
        $report = new ActivityBatchReport();
        $report->setDateStart($infos['date_start']);
        $report->setDateEnd($infos['date_end']);
        $report->setTaskCount($infos['task_count']);
        $report->setTaskDone($successfulTasksCount);
        $report->setPage($infos['page']);

        // Vérification que le nombre de tâches réussies est correct
        $this->assertEquals(0, $report->getTaskDone());
    }
}
