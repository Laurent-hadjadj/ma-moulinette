<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\{OwaspDast, ZapScan};
use App\Repository\OwaspDastRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * MODIF 2026-10-18 : OwaspDastRepository n'avait jusqu'ici aucun test, ni Unit
 * ni Integration, malgré findLatestByMavenKey() construit sur une sous-requête
 * DQL (MAX(generatedAt) sur ZapScan) non triviale à vérifier sans vraie base.
 */
class OwaspDastRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private OwaspDastRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(OwaspDast::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.owasp_dast');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_scan');
    }

    private function makeScan(string $mavenKey, \DateTimeImmutable $generatedAt): ZapScan
    {
        $scan = (new ZapScan())
            ->setMavenKey($mavenKey)
            ->setProjectVersion('1.0.0')
            ->setGeneratedAt($generatedAt);
        $this->em->persist($scan);
        return $scan;
    }

    private function makeOwaspDast(ZapScan $scan, int $referentialOwasp, string $mavenKey): OwaspDast
    {
        $row = (new OwaspDast())
            ->setScan($scan)
            ->setReferentialOwasp($referentialOwasp)
            ->setMavenKey($mavenKey)
            ->setProjectVersion('1.0.0');
        $this->em->persist($row);
        return $row;
    }

    public function testFindByScanAndReferentialReturnsNullWhenNotFound(): void
    {
        $scan = $this->makeScan('fr.ma-moulinette:ma-moulinette', new \DateTimeImmutable('2026-10-01'));
        $this->em->flush();

        $this->assertNull($this->repo->findByScanAndReferential((int) $scan->getId(), 2021));
    }

    public function testFindByScanAndReferentialReturnsEntity(): void
    {
        $scan = $this->makeScan('fr.ma-moulinette:ma-moulinette', new \DateTimeImmutable('2026-10-01'));
        $this->makeOwaspDast($scan, 2021, 'fr.ma-moulinette:ma-moulinette');
        $this->em->flush();

        $found = $this->repo->findByScanAndReferential((int) $scan->getId(), 2021);

        $this->assertNotNull($found);
        $this->assertSame(2021, $found->getReferentialOwasp());
    }

    public function testFindLatestByMavenKeyReturnsRowsFromMostRecentScanOnly(): void
    {
        $mavenKey = 'fr.ma-moulinette:ma-moulinette';
        $oldScan = $this->makeScan($mavenKey, new \DateTimeImmutable('2026-09-01'));
        $recentScan = $this->makeScan($mavenKey, new \DateTimeImmutable('2026-10-01'));
        $this->em->flush();

        $this->makeOwaspDast($oldScan, 2017, $mavenKey);
        $this->makeOwaspDast($recentScan, 2017, $mavenKey);
        $this->makeOwaspDast($recentScan, 2021, $mavenKey);
        $this->em->flush();

        $rows = $this->repo->findLatestByMavenKey($mavenKey);

        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertSame($recentScan->getId(), $row->getScan()->getId());
        }
    }

    public function testFindLatestByMavenKeyReturnsEmptyArrayWhenNoScan(): void
    {
        $this->assertSame([], $this->repo->findLatestByMavenKey('fr.ma-moulinette:inconnu'));
    }

    public function testHandleDatabaseExceptionGenericMessage(): void
    {
        $result = $this->repo->handleDatabaseException(new \RuntimeException('boom'));
        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    public function testHandleDatabaseExceptionUniqueReturnsCustomCode(): void
    {
        $exception = new class('dup') extends \Doctrine\DBAL\Exception\UniqueConstraintViolationException {
            public function __construct(string $message)
            {
                \Exception::__construct($message);
            }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(23505, $result['code']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
    }
}
