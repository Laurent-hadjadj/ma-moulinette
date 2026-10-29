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

use App\Entity\OwaspDetails;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description OwaspDetailsRepositoryTest]
 *
 * Pas de fixtures dédiées (table créée le 28/10/2026, pas de OwaspDetailsFixtures) —
 * chaque test construit son propre jeu de lignes via insertOwaspDetails(), pattern
 * déjà utilisé par OwaspRepositoryTest pour les cas round-trip.
 *
 * Created at: 28/10/2026 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
class OwaspDetailsRepositoryTest extends KernelTestCase
{
    private static string $erreurCode200 = 'Erreur le code retour doit être 200.';
    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $version = '1.2.0-RELEASE';
    private static string $dateVersion = '2024-07-10 15:26:07+02';
    private static string $modeCollecte = 'TRAITEMENT MANUEL';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
    private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.owasp_details_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement(
            'DELETE FROM ma_moulinette.owasp_details WHERE maven_key = ?',
            [self::$mavenKey]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRow(int $referentialOwasp, string $severity, string $ruleKey, ?int $line = 10, string $message = 'message', string $module = 'frontend'): array
    {
        return [
            'referential_owasp' => $referentialOwasp,
            'maven_key' => self::$mavenKey,
            'version' => self::$version,
            'date_version' => new \DateTimeImmutable(self::$dateVersion),
            'rule_key' => $ruleKey,
            'categories' => 'A1',
            'severity' => $severity,
            'source' => 'core',
            'module' => $module,
            'file_path' => 'src/Foo.java',
            'line' => $line,
            'message' => $message,
            'mode_collecte' => self::$modeCollecte,
            'utilisateur_collecte' => self::$utilisateurCollecte,
            'date_enregistrement' => new \DateTimeImmutable(self::$dateEnregistrement),
        ];
    }

    public function testInsertOwaspDetails(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $owaspDetailsRepository = $entityManager->getRepository(OwaspDetails::class);
        $r = $owaspDetailsRepository->insertOwaspDetails([$this->buildRow(2021, 'CRITICAL', 'java:S2077')]);

        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    /**
     * Round-trip complet : tous les champs persistés sont relus à l'identique,
     * y compris `line` nullable (règle au niveau fichier/architecture).
     */
    public function testInsertOwaspDetailsPersistsAllFieldsIncludingNullLine(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $row = $this->buildRow(2021, 'CRITICAL', 'java:S2077', null, 'Message complet', 'backend');
        $owaspDetailsRepository = $entityManager->getRepository(OwaspDetails::class);
        $insert = $owaspDetailsRepository->insertOwaspDetails([$row]);
        $this->assertEquals(200, $insert['code'], self::$erreurCode200);

        $select = $owaspDetailsRepository->selectOwaspDetailsTop10BySeverity([
            'maven_key' => self::$mavenKey,
            'referential_owasp' => 2021,
        ]);
        $this->assertSame(200, $select['code']);
        $this->assertCount(1, $select['details']);

        $persisted = $select['details'][0];
        $this->assertSame(self::$mavenKey, $persisted['maven_key']);
        $this->assertSame(2021, (int) $persisted['referential_owasp']);
        $this->assertSame('java:S2077', $persisted['rule_key']);
        $this->assertSame('A1', $persisted['categories']);
        $this->assertSame('CRITICAL', $persisted['severity']);
        $this->assertSame('core', $persisted['source']);
        $this->assertSame('backend', $persisted['module']);
        $this->assertSame('src/Foo.java', $persisted['file_path']);
        $this->assertNull($persisted['line']);
        $this->assertSame('Message complet', $persisted['message']);
    }

    /**
     * Tri explicite par criticité (Bloquant > Critique > Majeur > Mineur),
     * pas alphabétique — BLOCKER/CRITICAL/MAJOR/MINOR ne sont pas dans cet
     * ordre une fois triés comme de simples chaînes.
     */
    public function testSelectOwaspDetailsTop10BySeverityOrdersByCriticality(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $rows = [
            $this->buildRow(2021, 'MINOR', 'rule-minor'),
            $this->buildRow(2021, 'BLOCKER', 'rule-blocker'),
            $this->buildRow(2021, 'MAJOR', 'rule-major'),
            $this->buildRow(2021, 'CRITICAL', 'rule-critical'),
        ];

        $owaspDetailsRepository = $entityManager->getRepository(OwaspDetails::class);
        $insert = $owaspDetailsRepository->insertOwaspDetails($rows);
        $this->assertEquals(200, $insert['code'], self::$erreurCode200);

        $select = $owaspDetailsRepository->selectOwaspDetailsTop10BySeverity([
            'maven_key' => self::$mavenKey,
            'referential_owasp' => 2021,
        ]);
        $this->assertSame(200, $select['code']);
        $this->assertCount(4, $select['details']);

        $orderedRuleKeys = array_column($select['details'], 'rule_key');
        $this->assertSame(['rule-blocker', 'rule-critical', 'rule-major', 'rule-minor'], $orderedRuleKeys);
    }

    public function testSelectOwaspDetailsTop10BySeverityLimitsToTen(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $rows = [];
        for ($i = 1; $i <= 12; $i++) {
            $rows[] = $this->buildRow(2021, 'MAJOR', "rule-{$i}");
        }

        $owaspDetailsRepository = $entityManager->getRepository(OwaspDetails::class);
        $insert = $owaspDetailsRepository->insertOwaspDetails($rows);
        $this->assertEquals(200, $insert['code'], self::$erreurCode200);

        $select = $owaspDetailsRepository->selectOwaspDetailsTop10BySeverity([
            'maven_key' => self::$mavenKey,
            'referential_owasp' => 2021,
        ]);
        $this->assertSame(200, $select['code']);
        $this->assertCount(10, $select['details'], 'Jamais plus de 10 lignes, même si 12 sont classées.');
    }

    public function testCountOwaspDetailsReturnsTotalNotLimitedToTen(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $rows = [];
        for ($i = 1; $i <= 12; $i++) {
            $rows[] = $this->buildRow(2021, 'MAJOR', "rule-{$i}");
        }

        $owaspDetailsRepository = $entityManager->getRepository(OwaspDetails::class);
        $insert = $owaspDetailsRepository->insertOwaspDetails($rows);
        $this->assertEquals(200, $insert['code'], self::$erreurCode200);

        $count = $owaspDetailsRepository->countOwaspDetails([
            'maven_key' => self::$mavenKey,
            'referential_owasp' => 2021,
        ]);
        $this->assertSame(200, $count['code']);
        $this->assertSame(12, $count['total'], 'countOwaspDetails() ignore la limite à 10 du tableau de détail.');
    }

    /**
     * Les lignes sont scopées par (maven_key, referential_owasp) — une issue
     * classée sur les 2 référentiels donne 2 lignes distinctes (cf.
     * OwaspRuleClassifierService::classify()), chaque lecture par référentiel
     * ne doit retourner que les siennes.
     */
    public function testSelectAndCountAreScopedByReferentialOwasp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $owaspDetailsRepository = $entityManager->getRepository(OwaspDetails::class);
        $insert = $owaspDetailsRepository->insertOwaspDetails([
            $this->buildRow(2017, 'CRITICAL', 'rule-2017-a'),
            $this->buildRow(2017, 'MAJOR', 'rule-2017-b'),
            $this->buildRow(2021, 'MINOR', 'rule-2021-a'),
        ]);
        $this->assertEquals(200, $insert['code'], self::$erreurCode200);

        $select2017 = $owaspDetailsRepository->selectOwaspDetailsTop10BySeverity([
            'maven_key' => self::$mavenKey,
            'referential_owasp' => 2017,
        ]);
        $count2017 = $owaspDetailsRepository->countOwaspDetails([
            'maven_key' => self::$mavenKey,
            'referential_owasp' => 2017,
        ]);
        $this->assertCount(2, $select2017['details']);
        $this->assertSame(2, $count2017['total']);

        $select2021 = $owaspDetailsRepository->selectOwaspDetailsTop10BySeverity([
            'maven_key' => self::$mavenKey,
            'referential_owasp' => 2021,
        ]);
        $count2021 = $owaspDetailsRepository->countOwaspDetails([
            'maven_key' => self::$mavenKey,
            'referential_owasp' => 2021,
        ]);
        $this->assertCount(1, $select2021['details']);
        $this->assertSame(1, $count2021['total']);
        $this->assertSame('rule-2021-a', $select2021['details'][0]['rule_key']);
    }

    public function testDeleteOwaspDetailsMavenKeyRemovesAllReferentials(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $owaspDetailsRepository = $entityManager->getRepository(OwaspDetails::class);
        $insert = $owaspDetailsRepository->insertOwaspDetails([
            $this->buildRow(2017, 'CRITICAL', 'rule-2017'),
            $this->buildRow(2021, 'CRITICAL', 'rule-2021'),
        ]);
        $this->assertEquals(200, $insert['code'], self::$erreurCode200);

        $delete = $owaspDetailsRepository->deleteOwaspDetailsMavenKey(['maven_key' => self::$mavenKey]);
        $this->assertEquals(200, $delete['code'], self::$erreurCode200);
        $this->assertEmpty($delete['erreur'], $delete['erreur']);

        $count2017 = $owaspDetailsRepository->countOwaspDetails(['maven_key' => self::$mavenKey, 'referential_owasp' => 2017]);
        $count2021 = $owaspDetailsRepository->countOwaspDetails(['maven_key' => self::$mavenKey, 'referential_owasp' => 2021]);
        $this->assertSame(0, $count2017['total'], 'deleteOwaspDetailsMavenKey() supprime les 2 référentiels, pas un seul.');
        $this->assertSame(0, $count2021['total']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
        $entityManager = null;
    }
}
