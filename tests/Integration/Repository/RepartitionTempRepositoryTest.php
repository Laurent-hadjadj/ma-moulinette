<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Tests\Integration\Repository;

use App\DataFixtures\RepartitionTempFixtures;
use App\Entity\RepartitionTemp;
use App\Repository\RepartitionTempRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/* MODIF 2026-05-08 : bascule des mocks "stub-only"
 * (EntityManager, ManagerRegistry) vers `createStub()` et ajout de
 * `#[AllowMockObjectsWithoutExpectations]` sur les tests qui partial-mockent
 * le repository sans `expects()`. Éteint les notices PHPUnit 13. */

/**
 * [Description RepartitionTempRepositoryTest]
 */
class RepartitionTempRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $em = null;

    private static int $setup = 1000000000001;
    private static string $component = '/src/Controller/ApiController.php';
    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em = $em;
        $this->purgeDatabase();
        $this->loadFixtures();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em?->close();
        $this->em = null;
    }

    private function purgeDatabase(): void
    {
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.repartition_temp');
    }

    /**
     * [Description for loadFixtures]
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function loadFixtures(): void
    {
        $fixture = new RepartitionTempFixtures();
        $fixture->load($this->em);
    }

    /**
     * [Description for getRepository]
     *
     * @return RepartitionTempRepository
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function getRepository(): RepartitionTempRepository
    {
        return $this->em->getRepository(RepartitionTemp::class);
    }

    /**
     * [Description for testBatchInsertIssuesSQLSuccess]
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function testBatchInsertIssuesSQLSuccess(): void
    {
        // Préparation des données de test
        $issues = [
            [
                'maven_key' => self::$mavenKey,
                'component' => self::$component,
                'category' => 'BUG',
                'severity' => 'CRITICAL',
                'setup' => self::$setup + 1
            ],
            [
                'maven_key' => self::$mavenKey,
                'component' => self::$component,
                'category' => 'CODE_SMELL',
                'severity' => 'MAJOR',
                'setup' => self::$setup + 1
            ]
        ];

        // Exécution de la méthode à tester
        $repo = $this->getRepository();
        $result = $repo->batchInsertIssuesSQL($issues);

        // Vérifications
        $this->assertEquals(['code' => 200, 'erreur' => ''], $result);
        $this->assertEmpty($result['erreur'] ?? '');

        // Vérifie que l’un des enregistrements a bien été inséré
        $repartitionTemp = $repo->findOneBy([
            'mavenKey' => self::$mavenKey,
            'setup' => self::$setup + 1
        ]);

        $this->assertNotNull($repartitionTemp);
        $this->assertEquals(self::$setup + 1, $repartitionTemp->getSetup());
    }

    /**
     * [Description for insertTempIssue]
     *
     * @param string $mavenKey
     * @param string $component
     * @param string $type
     * @param string $severity
     * @param int $setup
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function insertTempIssue(string $mavenKey, string $component, string $type, string $severity, int $setup): void
    {
        $issue = new RepartitionTemp();
        $issue->setMavenKey($mavenKey);
        $issue->setComponent($component);
        $issue->setType($type);
        $issue->setSeverity($severity);
        $issue->setSetup($setup);

        $this->em->persist($issue);
        $this->em->flush();
        $this->em->clear(); // Important pour éviter des collisions d'identité
    }

    /**
     * [Description for testDeleteOldRecords]
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function testDeleteOldRecords(): void
    {
        $repo = $this->getRepository();
        // On ajoute 2 enregistrements pour la même maven_key mais avec des setup différents
        $this->insertTempIssue(self::$mavenKey, self::$component, 'BUG', 'MAJOR', self::$setup + 2);
        $this->insertTempIssue(self::$mavenKey, self::$component, 'BUG', 'MAJOR', self::$setup + 3);

        // Appel de la méthode
        $result = $repo->deleteOldRecords([
            'maven_key' => self::$mavenKey,
            'setup' => self::$setup + 2,
        ]);

        // Vérifie que la suppression a fonctionné
        $this->assertEquals(['code' => 200, 'erreur' => ''], $result);

        // L'ancien enregistrement doit avoir été supprimé
        $old = $repo->findOneBy(['mavenKey' => self::$mavenKey, 'setup' => self::$setup]);
        $this->assertNull($old);

        // Le nouveau doit toujours exister
        $current = $repo->findOneBy(['mavenKey' => self::$mavenKey, 'setup' => self::$setup + 2]);
        $this->assertNotNull($current);
    }

    /**
     * [Description for testDeleteOldRecordsMissingParams]
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function testDeleteOldRecordsMissingParams(): void
    {
        $repo = $this->getRepository();
        $result = $repo->deleteOldRecords([]); // paramètres vides

        $this->assertEquals(400, $result['code']);
        $this->assertStringContainsString('Paramètres manquants', $result['erreur']);
    }

    /**
     * [Description for testSelectRepartitionByTypeAndSeveritySuccess]
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function testSelectRepartitionByTypeAndSeveritySuccess(): void
    {
        $repo = $this->getRepository();

        $map = [
            'maven_key' => self::$mavenKey,
            'category' => 'BUG',
            'severity' => 'CRITICAL',
            'setup' => self::$setup
        ];

        $result = $repo->selectRepartitionByTypeAndSeverity($map);

        $this->assertEquals(200, $result['code']);
        $this->assertIsArray($result['liste']);
        $this->assertEmpty($result['erreur']);

        // Vérifie qu'au moins une ligne correspond si tes fixtures couvrent ce cas
        $this->assertGreaterThan(0, count($result['liste']));

        // Optionnel : valider le contenu retourné
        foreach ($result['liste'] as $row) {
            $this->assertEquals($map['maven_key'], $row['maven_key']);
            $this->assertEquals($map['category'], $row['type']);
            $this->assertEquals($map['severity'], $row['severity']);
            $this->assertEquals($map['setup'], (int)$row['setup']);
        }
    }

    /**
     * [Description for testSelectRepartitionByTypeAndSeverityNotFound]
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function testSelectRepartitionByTypeAndSeverityNotFound(): void
    {
        $repo = $this->getRepository();

        $map = [
            'maven_key' => 'inexistant',
            'category' => 'BUG',
            'severity' => 'HIGH',
            'setup' => 999999999
        ];

        $result = $repo->selectRepartitionByTypeAndSeverity($map);

        $this->assertEquals(200, $result['code']);
        $this->assertIsArray($result['liste']);
        $this->assertCount(0, $result['liste']);
        $this->assertEmpty($result['erreur']);
    }


    /**
     * [Description for testBatchInsertIssuesSQL_WithSingleIssueAsArray]
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testBatchInsertIssuesSQL_WithSingleIssueAsArray(): void
    {
        $singleIssue = [
            'maven_key' => self::$mavenKey,
            'component' => self::$component,
            'category' => 'BUG',
            'severity' => 'MAJOR',
            'setup' => self::$setup,
        ];

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once())
            ->method('executeStatement')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->callback(function ($params) use ($singleIssue) {
                    return in_array($singleIssue['maven_key'], $params) &&
                        in_array($singleIssue['component'], $params);
                })
            );

        $emStub = $this->createStub(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(RepartitionTempRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager'])
            ->getMock();
        $repo->method('getEntityManager')->willReturn($emStub);

        $result = $repo->batchInsertIssuesSQL($singleIssue);

        $this->assertEquals(['code' => 200, 'erreur' => ''], $result);
    }

    /**
     * [Description for testBatchInsertIssuesSQL_WithEmptyArray]
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function testBatchInsertIssuesSQL_WithEmptyArray(): void
    {
        /** @var ManagerRegistry&\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createStub(ManagerRegistry::class);

        $repo = new RepartitionTempRepository($registry);

        $result = $repo->batchInsertIssuesSQL([]);

        $this->assertEquals([
            'code' => 404,
            'erreur' => 'Aucunes données présentes dans la collection.'
        ], $result);
    }

    /**
     * [Description for testBatchInsertIssuesSQL_SkipIncompleteIssue]
     *
     * @return void
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testBatchInsertIssuesSQL_SkipIncompleteIssue(): void
    {
        // On crée une "issue" incomplète (manque 'severity')
        $incompleteIssue = [
            'maven_key' => 'test-key',
            'component' => 'test-component',
            'category' => 'BUG',
            // 'severity' => 'MAJOR', // <-- volontairement manquant
            'setup' => 1,
        ];

        // Et une issue valide pour que quelque chose soit inséré
        $validIssue = [
            'maven_key' => 'test-key',
            'component' => 'test-component',
            'category' => 'BUG',
            'severity' => 'CRITICAL',
            'setup' => 1,
        ];

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects($this->once()) // doit être appelé **une seule fois**
            ->method('executeStatement')
            ->with(
                $this->stringContains('INSERT INTO'),
                $this->callback(function ($params) {
                    // Seule l'issue valide doit être présente
                    return count($params) === 5; // 5 valeurs = 1 ligne insérée
                })
            );

        $emMock = $this->createStub(EntityManagerInterface::class);
        $emMock->method('getConnection')->willReturn($connectionMock);

        $registry = $this->createStub(ManagerRegistry::class);
        $repo = $this->getMockBuilder(RepartitionTempRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager'])
            ->getMock();

        $repo->method('getEntityManager')->willReturn($emMock);

        // Appel avec une issue incomplète et une correcte
        $result = $repo->batchInsertIssuesSQL([$incompleteIssue, $validIssue]);

        // Résultat attendu = succès (car issue valide insérée)
        $this->assertEquals(['code' => 200, 'erreur' => ''], $result);
    }
}
