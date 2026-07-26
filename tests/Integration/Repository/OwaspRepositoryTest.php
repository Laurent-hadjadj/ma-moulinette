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

namespace App\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Owasp;
use App\DataFixtures\OwaspFixtures;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description OwaspRepositoryTest]
 */
class OwaspRepositoryTest extends KernelTestCase
{

    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $version = '1.2.0-RELEASE';
    private static string $dateVersion = '2024-07-10 15:26:07+02';
    private static int $effortTotal = 0;
    private static array $a = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static array $aBlocker = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static array $aCritical = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static array $aMajor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static array $aInfo = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static array $aMinor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static string $modeCollecte = 'TRAITEMENT MANUEL';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
    private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

    private static string $erreurCode200 = 'Erreur le code retour doit être 200.';

    /**
     * [Description for setUp]
     * Création des owasp en base depuis les fixtures
     *
     * @return void
     *
     * Created at: 05/05/2024 18:15:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.owasp_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.owasp');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new OwaspFixtures()], true);
    }

    /* MODIF 2026-05-10 : tests selectOwaspVersion (utilise par
     * la page OWASP pour alimenter le breadcrumb application/version). */
    public function testSelectOwaspVersionHappyPath(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $owaspRepository = $entityManager->getRepository(Owasp::class);
        $r = $owaspRepository->selectOwaspVersion(self::$mavenKey);

        $this->assertSame(200, $r['code'], self::$erreurCode200);
        $this->assertSame('', $r['erreur']);
        $this->assertSame(self::$mavenKey, $r['application']);
        // Les fixtures posent 3 versions (1.0.0/1.1.0/1.2.0), date identique :
        // on n'asserte pas la version exacte (ordre interne PG sur dates égales),
        // juste qu'elle est non vide et fait partie de la liste connue.
        $this->assertContains($r['version'], ['1.0.0-RELEASE', '1.1.0-RELEASE', '1.2.0-RELEASE']);
    }

    public function testSelectOwaspVersionWithUnknownMavenKeyReturnsNullFields(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $owaspRepository = $entityManager->getRepository(Owasp::class);
        $r = $owaspRepository->selectOwaspVersion('fr.ma-moulinette:projet-inconnu');

        // Le contrat : code=200 + application/version a null
        // si fetchAssociative() retourne false (0 ligne).
        $this->assertSame(200, $r['code']);
        $this->assertSame('', $r['erreur']);
        $this->assertNull($r['application']);
        $this->assertNull($r['version']);
    }

    public function testSelectOwaspOrderByDateEnregistrement(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = ['maven_key' => self::$mavenKey, 'referential_owasp' => 2017];

        // Appel de la méthode
        $owaspRepository = $entityManager->getRepository(Owasp::class);
        $r = $owaspRepository->selectOwaspOrderByDateEnregistrement($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testDeleteOwaspMavenKey(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = ['maven_key' => self::$mavenKey];

        // Appel de la méthode
        $owaspRepository = $entityManager->getRepository(Owasp::class);
        $r = $owaspRepository->deleteOwaspMavenKey($map);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    public function testInsertOwasp(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $map = [];

        // Ajouter les propriétés de l'objet $owasp au tableau $map
        $map['referential_owasp'] = 2017;
        $map['maven_key'] = self::$mavenKey;
        $map['version'] = self::$version;
        $map['date_version'] = self::$dateVersion;
        $map['effort_total'] = self::$effortTotal;
        // MODIF 2026-07-18 : nouvelle colonne NOT NULL (source du comptage : facet|tag).
        $map['source'] = 'facet';

        for ($i = 0; $i < 10; $i++) {
            $map["a" . ($i + 1)] = self::$a[$i];
            $map["a" . ($i + 1) . "_blocker"] = self::$aBlocker[$i];
            $map["a" . ($i + 1) . "_critical"] = self::$aCritical[$i];
            $map["a" . ($i + 1) . "_major"] = self::$aMajor[$i];
            $map["a" . ($i + 1) . "_info"] = self::$aInfo[$i];
            $map["a" . ($i + 1) . "_minor"] = self::$aMinor[$i];
        }

        $map['mode_collecte'] = self::$modeCollecte;
        $map['utilisateur_collecte'] = self::$utilisateurCollecte;
        $map['date_enregistrement'] = new \DateTimeImmutable(self::$dateEnregistrement);

        // Appel de la méthode
        $owaspRepository = $entityManager->getRepository(Owasp::class);
        $r = $owaspRepository->insertOwasp([$map]);

        // Assert
        $this->assertEquals(200, $r['code'], self::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    /**
     * MODIF 2026-07-18 : verrouille le fix — `executeStatement()` était hors
     * de la boucle `foreach ($owaspDataList as $map)`, si bien qu'un seul
     * appel avec plusieurs lignes (ex. 2017 + 2021, cas réel de
     * BatchCollecteOwaspController) n'insérait en pratique que la DERNIÈRE
     * ligne — les liaisons de paramètres se réécrivent sur la même requête
     * préparée, elles ne s'accumulent pas en plusieurs exécutions.
     */
    public function testInsertOwaspPersistsAllRowsWhenMultipleReferentialsInSameCall(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $buildMap = function (int $referentialOwasp): array {
            $map = [
                'referential_owasp' => $referentialOwasp,
                'maven_key' => self::$mavenKey,
                'version' => self::$version,
                'date_version' => self::$dateVersion,
                'effort_total' => self::$effortTotal,
                'source' => 'facet',
            ];
            for ($i = 0; $i < 10; $i++) {
                $map["a" . ($i + 1)] = self::$a[$i];
                $map["a" . ($i + 1) . "_blocker"] = self::$aBlocker[$i];
                $map["a" . ($i + 1) . "_critical"] = self::$aCritical[$i];
                $map["a" . ($i + 1) . "_major"] = self::$aMajor[$i];
                $map["a" . ($i + 1) . "_info"] = self::$aInfo[$i];
                $map["a" . ($i + 1) . "_minor"] = self::$aMinor[$i];
            }
            $map['mode_collecte'] = self::$modeCollecte;
            $map['utilisateur_collecte'] = self::$utilisateurCollecte;
            $map['date_enregistrement'] = new DateTimeImmutable(self::$dateEnregistrement);
            return $map;
        };

        // Les fixtures posent déjà 3 lignes pour self::$mavenKey (cf. testSelectOwaspVersionHappyPath) :
        // on repart d'un état propre pour isoler précisément l'effet de l'appel testé.
        $entityManager->getConnection()->executeStatement(
            'DELETE FROM ma_moulinette.owasp WHERE maven_key = ?',
            [self::$mavenKey]
        );

        $owaspRepository = $entityManager->getRepository(Owasp::class);
        $r = $owaspRepository->insertOwasp([$buildMap(2017), $buildMap(2021)]);

        $this->assertEquals(200, $r['code'], self::$erreurCode200);

        $rows = $entityManager->getConnection()->fetchAllAssociative(
            'SELECT referential_owasp FROM ma_moulinette.owasp WHERE maven_key = ? ORDER BY referential_owasp',
            [self::$mavenKey]
        );

        $this->assertCount(2, $rows, 'Les lignes 2017 ET 2021 doivent être persistées, pas seulement la dernière.');
        $this->assertSame(2017, (int) $rows[0]['referential_owasp']);
        $this->assertSame(2021, (int) $rows[1]['referential_owasp']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // On se déconnecte pour éviter des problèmes de mémoires
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
        $entityManager = null;
    }
}
