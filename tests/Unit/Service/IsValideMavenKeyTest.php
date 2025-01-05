<?php

namespace App\Tests\Service;

use App\Entity\Historique;
use App\Entity\InformationProjet;
use App\Repository\HistoriqueRepository;
use App\Repository\InformationProjetRepository;
use App\Service\IsValideMavenKey;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * [Description IsValideMavenKeyTest]
 */
class IsValideMavenKeyTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private IsValideMavenKey $service;

    private $notFound = 'Not Found';

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new IsValideMavenKey($this->entityManager);
    }

    public function testIsValideInformationSuccess(): void
    {
        $mavenKey = 'valid-maven-key';

        $repository = $this->createMock(InformationProjetRepository::class);
        $repository->method('selectInformationProjetIsValide')->with(['maven_key' => $mavenKey])
            ->willReturn(['code' => 200, 'is_valide' => true]);

        $this->entityManager->method('getRepository')->with(InformationProjet::class)
            ->willReturn($repository);

        $result = $this->service->isValideInformation($mavenKey);

        $this->assertEquals(['code' => 200, 'request' => true], $result);
    }

    public function testIsValideInformationError(): void
    {
        $mavenKey = 'invalid-maven-key';

        $repository = $this->createMock(InformationProjetRepository::class);
        $repository->method('selectInformationProjetIsValide')->with(['maven_key' => $mavenKey])
            ->willReturn(['code' => 404, 'erreur' => static::$notFound]);

        $this->entityManager->method('getRepository')->with(InformationProjet::class)
            ->willReturn($repository);

        $result = $this->service->isValideInformation($mavenKey);

        $this->assertEquals(['code' => 404, 'request' => static::$notFound], $result);
    }

    public function testIsValideHistoriqueSuccess(): void
    {
        $mavenKey = 'valid-maven-key';

        $repository = $this->createMock(HistoriqueRepository::class);
        $repository->method('selectHistoriqueIsValide')->with(['maven_key' => $mavenKey])
            ->willReturn(['code' => 200, 'is_valide' => true]);

        $this->entityManager->method('getRepository')->with(Historique::class)
            ->willReturn($repository);

        $result = $this->service->isValideHistorique($mavenKey);

        $this->assertEquals(['code' => 200, 'request' => true], $result);
    }

    public function testIsValideHistoriqueError(): void
    {
        $mavenKey = 'invalid-maven-key';

        $repository = $this->createMock(HistoriqueRepository::class);
        $repository->method('selectHistoriqueIsValide')->with(['maven_key' => $mavenKey])
            ->willReturn(['code' => 404, 'erreur' => static::$notFound]);

        $this->entityManager->method('getRepository')->with(Historique::class)
            ->willReturn($repository);

        $result = $this->service->isValideHistorique($mavenKey);

        $this->assertEquals(['code' => 404, 'request' => static::$notFound], $result);
    }
}
