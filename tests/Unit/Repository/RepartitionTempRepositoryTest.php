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

namespace App\Tests\Repository;

use App\DataFixtures\RepartitionTempFixtures;
use App\Entity\RepartitionTemp;
use App\Repository\RepartitionTempRepository;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description RepartitionTempRepositoryTest]
 */
class RepartitionTempRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $em = null;

    private static $setup = 1000000000001;
    private static $component = '/src/Controller/ApiController.php';
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
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
        $purger = new ORMPurger($this->em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $purger->purge();
    }

    /**
     * [Description for loadFixtures]
     *
     * @return void
     *
     * Created at: 06/07/2025 12:07:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     *
     * Created at: 06/07/2025 12:07:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getRepository(): RepartitionTempRepository
    {
        return $this->em->getRepository(RepartitionTemp::class);
    }

    public function testInitialInsert(): void
    {
        $repo = $this->getRepository();
        $map = $this->buildCompleteMap();

        $result = $repo->selectOrUpdateRepartitionInitial($map);

        $this->assertEquals(200, $result['code']);
        $this->assertEmpty($result['erreur'] ?? '');

        $repartition = $repo->findOneBy(['mavenKey' => $map['maven_key']]);
        $this->assertNotNull($repartition);
        $this->assertEquals('ma-moulinette', $repartition->getName());
    }

    /**
     * [Description for testBatchInsertIssuesSQLSuccess]
     *
     * @return [type]
     *
     * Created at: 06/07/2025 17:34:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testBatchInsertIssuesSQLSuccess(): void
    {
        // Préparation des données de test
        $issues = [
            [
                'maven_key' => static::$mavenKey,
                'component' => static::$component,
                'type' => 'BUG',
                'severity' => 'CRITICAL',
                'setup' => static::$setup + 1
            ],
            [
                'maven_key' => static::$mavenKey,
                'component' => static::$component,
                'type' => 'CODE_SMELL',
                'severity' => 'MAJOR',
                'setup' => static::$setup + 1
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
            'mavenKey' => static::$mavenKey,
            'setup' => static::$setup + 1
        ]);

        $this->assertNotNull($repartitionTemp);
        $this->assertEquals(static::$setup + 1, $repartitionTemp->getSetup());
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
     *
     * Created at: 06/07/2025 17:40:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     *
     * Created at: 06/07/2025 17:40:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testDeleteOldRecords(): void
    {
        $repo = $this->getRepository();
        // On ajoute 2 enregistrements pour la même maven_key mais avec des setup différents
        $this->insertTempIssue(static::$mavenKey, static::$component, 'BUG', 'MAJOR', static::$setup + 2);
        $this->insertTempIssue(static::$mavenKey, static::$component, 'BUG', 'MAJOR', static::$setup + 3);

        // Appel de la méthode
        $result = $repo->deleteOldRecords([
            'maven_key' => static::$mavenKey,
            'setup' => static::$setup + 2,
        ]);

        // Vérifie que la suppression a fonctionné
        $this->assertEquals(['code' => 200, 'erreur' => ''], $result);

        // L'ancien enregistrement doit avoir été supprimé
        $old = $repo->findOneBy(['mavenKey' => static::$mavenKey, 'setup' => static::$setup]);
        $this->assertNull($old);

        // Le nouveau doit toujours exister
        $current = $repo->findOneBy(['mavenKey' => static::$mavenKey, 'setup' => static::$setup + 2]);
        $this->assertNotNull($current);
    }

    /**
     * [Description for testDeleteOldRecordsMissingParams]
     *
     * @return void
     *
     * Created at: 06/07/2025 17:41:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     *
     * Created at: 06/07/2025 17:43:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testSelectRepartitionByTypeAndSeveritySuccess(): void
    {
        $repo = $this->getRepository();

        $map = [
            'maven_key' => static::$mavenKey,
            'type' => 'BUG',
            'severity' => 'CRITICAL',
            'setup' => static::$setup
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
            $this->assertEquals($map['type'], $row['type']);
            $this->assertEquals($map['severity'], $row['severity']);
            $this->assertEquals($map['setup'], (int)$row['setup']);
        }
    }

    /**
     * [Description for testSelectRepartitionByTypeAndSeverityNotFound]
     *
     * @return void
     *
     * Created at: 06/07/2025 17:45:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testSelectRepartitionByTypeAndSeverityNotFound(): void
    {
        $repo = $this->getRepository();

        $map = [
            'maven_key' => 'inexistant',
            'type' => 'BUG',
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
     *
     * Created at: 06/07/2025 19:17:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testBatchInsertIssuesSQL_WithSingleIssueAsArray(): void
    {
        $singleIssue = [
            'maven_key' => static::$mavenKey,
            'component' => static::$component,
            'type' => 'BUG',
            'severity' => 'MAJOR',
            'setup' => static::$setup,
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

        $emStub = $this->createMock(EntityManagerInterface::class);
        $emStub->method('getConnection')->willReturn($connectionMock);

        $registry = $this->createMock(ManagerRegistry::class);
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
     *
     * Created at: 06/07/2025 19:23:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testBatchInsertIssuesSQL_WithEmptyArray(): void
    {
        /** @var ManagerRegistry&\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);

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
     *
     * Created at: 06/07/2025 19:25:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testBatchInsertIssuesSQL_SkipIncompleteIssue(): void
    {
        // On crée une "issue" incomplète (manque 'severity')
        $incompleteIssue = [
            'maven_key' => 'test-key',
            'component' => 'test-component',
            'type' => 'BUG',
            // 'severity' => 'MAJOR', // <-- volontairement manquant
            'setup' => 1,
        ];

        // Et une issue valide pour que quelque chose soit inséré
        $validIssue = [
            'maven_key' => 'test-key',
            'component' => 'test-component',
            'type' => 'BUG',
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

        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getConnection')->willReturn($connectionMock);

        $registry = $this->createMock(ManagerRegistry::class);
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
