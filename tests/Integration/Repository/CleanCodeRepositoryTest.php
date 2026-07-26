<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
/* MODIF 2026-05-17 : tests d'intégration pour
 * CleanCodeRepository (delete / select / insert) — 3 scénarios
 * compatibles avec le contrat défini dans CleanCodeFixtures.php :
 *   - 3 rows pour fr.ma-moulinette:ma-moulinette,
 *     dates distinctes → selectCleanCode retourne la plus récente (LIMIT 1). */

use App\Entity\CleanCode;
use App\DataFixtures\CleanCodeFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CleanCodeRepositoryTest extends KernelTestCase
{
    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $erreurCode200 = 'Erreur : le code retour doit être 200.';

    protected function setUp(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $connection    = $entityManager->getConnection();

        // Réinitialisation de la séquence PostgreSQL
        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $connection->executeQuery("SELECT setval('ma_moulinette.clean_code_id_seq', 1, false);");
        }

        $connection->executeStatement('DELETE FROM ma_moulinette.clean_code');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new CleanCodeFixtures()], true);
    }

    // ─── 1. selectCleanCode : retourne 1 ligne (LIMIT 1), la plus récente ─────

    public function testSelectCleanCode(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $repository = $entityManager->getRepository(CleanCode::class);
        $r = $repository->selectCleanCode(['maven_key' => self::$mavenKey]);

        $this->assertSame(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur']);
        $this->assertCount(1, $r['liste'], 'selectCleanCode doit retourner exactement 1 ligne (LIMIT 1).');

        $row = $r['liste'][0];
        $this->assertSame(self::$mavenKey, $row['maven_key']);
        $this->assertSame('ma-moulinette', $row['project_name']);
        // La fixture seed la plus récente au 2026-03-01 — ORDER BY DESC → c'est elle
        $this->assertStringStartsWith('2026-03-01', $row['date_enregistrement']);
    }

    public function testSelectCleanCodeWithUnknownMavenKeyReturnsEmptyList(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $repository = $entityManager->getRepository(CleanCode::class);
        $r = $repository->selectCleanCode(['maven_key' => 'fr.ma-moulinette:projet-inconnu']);

        $this->assertSame(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur']);
        $this->assertEmpty($r['liste'], 'Aucune donnée attendue pour une maven_key inconnue.');
    }

    // ─── 2. deleteCleanCodeMavenKey : suppression de toutes les lignes ────────

    public function testDeleteCleanCodeMavenKey(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $repository = $entityManager->getRepository(CleanCode::class);
        $r = $repository->deleteCleanCodeMavenKey(['maven_key' => self::$mavenKey]);

        $this->assertSame(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur']);

        // Vérification : la table est vide pour cette maven_key
        $check = $repository->selectCleanCode(['maven_key' => self::$mavenKey]);
        $this->assertEmpty($check['liste'], 'Toutes les lignes doivent avoir été supprimées.');
    }

    // ─── 3. insertCleanCode : insertion après delete → code 200 ──────────────

    public function testInsertCleanCode(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $repository = $entityManager->getRepository(CleanCode::class);

        // Suppression préalable (respecte le contrat delete→insert du controller)
        $repository->deleteCleanCodeMavenKey(['maven_key' => self::$mavenKey]);

        $map = [
            'maven_key'               => self::$mavenKey,
            'project_name'            => 'ma-moulinette',
            'issue_total'             => 42,
            'cc_consistent'           => 10,
            'cc_intentional'          => 8,
            'cc_adaptable'            => 6,
            'cc_responsible'          => 4,
            'quality_maintainability' => 15,
            'quality_reliability'     => 12,
            'quality_security'        => 5,
            'impact_blocker'          => 1,
            'impact_high'             => 7,
            'impact_medium'           => 20,
            'impact_low'              => 10,
            'impact_info'             => 4,
            'owasp_top10'             => 3,
            'sans_top25'              => 2,
            'cwe'                     => 6,
            'mode_collecte'           => 'TRAITEMENT MANUEL',
            'utilisateur_collecte'    => 'laurent.hadjadj@ma-moulinette.fr',
            'date_enregistrement'     => new \DateTimeImmutable('2026-05-15 12:00:00'),
        ];

        $r = $repository->insertCleanCode($map);

        $this->assertSame(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur']);

        // Vérification : la ligne insérée est retrouvable
        $check = $repository->selectCleanCode(['maven_key' => self::$mavenKey]);
        $this->assertSame(200, $check['code']);
        $this->assertCount(1, $check['liste']);
        $this->assertSame('42', (string) $check['liste'][0]['issue_total']);
        $this->assertSame('10', (string) $check['liste'][0]['cc_consistent']);
        $this->assertSame('3',  (string) $check['liste'][0]['owasp_top10']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
    }
}
