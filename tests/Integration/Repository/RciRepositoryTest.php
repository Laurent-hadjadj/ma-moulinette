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
/* MODIF 2026-09-25 : tests d'intégration pour
 * RciRepository (delete / select / insert) — 3 scénarios
 * compatibles avec le contrat défini dans RciFixtures.php :
 *   - 3 rows pour fr.ma-moulinette:ma-moulinette,
 *     dates distinctes → selectMesuresRci retourne la plus récente (LIMIT 1).
 * MODIF 2026-09-29 : selectMesuresRci() utilise désormais fetchAssociative()
 * (une seule ligne attendue, LIMIT 1 déjà en place) au lieu de
 * fetchAllAssociative() — 'liste' vaut directement la ligne (array) ou false
 * (pas de ligne), ce n'est plus un tableau de lignes indexé par [0]. */

use App\Entity\Rci;
use App\DataFixtures\RciFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RciRepositoryTest extends KernelTestCase
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
            $connection->executeQuery("SELECT setval('ma_moulinette.rci_id_seq', 1, false);");
        }

        $connection->executeStatement('DELETE FROM ma_moulinette.rci');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new RciFixtures()], true);
    }

    // ─── 1. selectMesuresRci : retourne 1 ligne (LIMIT 1), la plus récente ─────

    public function testSelectRci(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $repository = $entityManager->getRepository(Rci::class);
        $r = $repository->selectMesuresRci(['maven_key' => self::$mavenKey]);

        $this->assertSame(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur']);
        $this->assertIsArray($r['liste'], 'selectMesuresRci doit retourner la ligne la plus récente (LIMIT 1).');

        $row = $r['liste'];
        $this->assertSame(self::$mavenKey, $row['maven_key']);
        // La fixture seed la plus récente au 2026-03-01 — ORDER BY DESC → c'est elle
        $this->assertStringStartsWith('2026-03-01', $row['date_enregistrement']);
    }

    public function testSelectMesuresRciWithUnknownMavenKeyReturnsFalse(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $repository = $entityManager->getRepository(Rci::class);
        $r = $repository->selectMesuresRci(['maven_key' => 'fr.ma-moulinette:projet-inconnu']);

        $this->assertSame(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur']);
        $this->assertFalse($r['liste'], 'fetchAssociative() renvoie false quand aucune ligne ne correspond.');
    }

    // ─── 2. deleteRciMavenKey : suppression de toutes les lignes ────────

    public function testDeleteRciMavenKey(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $repository = $entityManager->getRepository(Rci::class);
        $r = $repository->deleteRciMavenKey(['maven_key' => self::$mavenKey]);

        $this->assertSame(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur']);

        // Vérification : la table est vide pour cette maven_key
        $check = $repository->selectMesuresRci(['maven_key' => self::$mavenKey]);
        $this->assertFalse($check['liste'], 'Toutes les lignes doivent avoir été supprimées.');
    }

    // ─── 3. insertRci : insertion après delete → code 200 ──────────────

    public function testInsertRci(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $repository = $entityManager->getRepository(Rci::class);

        // Suppression préalable (respecte le contrat delete→insert du controller)
        $repository->deleteRciMavenKey(['maven_key' => self::$mavenKey]);

        $map = [
            'maven_key'            => self::$mavenKey,
            'ncloc'                => 28188,
            'weight'               => 16732,
            'indicator'            => 40.6,
            'rating'               => 'E',
            'frontend_ncloc'       => 16590,
            'frontend_weight'      => 3725,
            'frontend_indicator'   => 77.55,
            'frontend_rating'      => 'C',
            'backend_ncloc'        => 11330,
            'backend_weight'       => 12995,
            'backend_indicator'    => 0.0,
            'backend_rating'       => 'E',
            'autre_ncloc'          => 0,
            'autre_weight'         => 0,
            'autre_indicator'      => null,
            'autre_rating'         => null,
            'inconnu_ncloc'        => 268,
            'inconnu_weight'       => 12,
            'inconnu_indicator'    => 95.52,
            'inconnu_rating'       => 'A',
            'mode_collecte'        => 'TRAITEMENT MANUEL',
            'utilisateur_collecte' => 'laurent.hadjadj@ma-moulinette.fr',
            'date_enregistrement'  => new \DateTimeImmutable('2026-09-25 12:00:00'),
        ];

        $r = $repository->insertRci($map);

        $this->assertSame(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur']);

        // Vérification : la ligne insérée est retrouvable
        $check = $repository->selectMesuresRci(['maven_key' => self::$mavenKey]);
        $this->assertSame(200, $check['code']);
        $this->assertIsArray($check['liste']);
        $this->assertSame('16590', (string) $check['liste']['frontend_ncloc']);
        $this->assertSame('E', $check['liste']['backend_rating']);
        $this->assertSame('28188', (string) $check['liste']['ncloc']);
        $this->assertSame('E', $check['liste']['rating']);
        $this->assertNull($check['liste']['autre_indicator']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $container     = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
    }
}
