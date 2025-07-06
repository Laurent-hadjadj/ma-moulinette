<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Repository;

use App\Entity\Utilisateur;
use App\DataFixtures\UtilisateurFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * [Description UtilisateurRepositoryTest]
 */
class UtilisateurRepositoryTest extends KernelTestCase
{

    public static $courriel = 'aurelie.petit-coeur@ma-moulinette.fr';
    public static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    public static $version = '2.0.0-RELEASE';
    public static $dateModification = '1981-01-01 00:00:00';
    public static $erreurCode200 = 'Erreur le code retour doit être 200';

    /**
     * [Description for setUp]
     * Création des utilisateurs en base depuis les fixtures
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
        $entityManager = $container->get('doctrine')->getManager();

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform('SET search_path TO ma_moulinette_test');

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            $sequence = 'ma_moulinette.utilisateur_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new UtilisateurFixtures()]);
    }

    public function testUpdateUtilisateurFavoriVersionAjoutProjetEtVersion(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Récupère un utilisateur de test depuis les fixtures
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $utilisateur = $utilisateurRepository->findOneBy(['courriel' => static::$courriel]);
        $preference = $utilisateur->getPreference();

        // Assurer un état de départ contrôlé

        $preference['statut'] = [];
        $preference['suivi_projet'] = [];
        $preference['favori_projet'] = [];
        $preference['favori_version'] =  [];
        $preference['bookmark'] = 'mon projet en bookmark';

        // Nouveau projet à ajouter en favori
        $map = [
            'favori' => 1,
            'courriel' => static::$courriel,
            'maven_key' => static::$mavenKey,
            'version' => static::$version,
            'date_version' => static::$dateModification,
        ];

        // Appel de la méthode à tester
        $response = $utilisateurRepository->updateUtilisateurFavoriVersion($preference, $map);
        // Assertions
        $this->assertEquals(200, $response['code']);
        $this->assertContains(static::$mavenKey, $response['preference']['favori_projet']);
        $this->assertEquals([
                [static::$mavenKey => [static::$version]]
            ], $response['preference']['favori_version']);
    }

    /**
     * [Description for testUpdateUtilisateurFavoriVersionAjoutProjetEtVersion_favoriIsFalse]
     *
     * @return void
     *
     * Created at: 06/07/2025 15:41:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testUpdateUtilisateurFavoriVersionAjoutProjetEtVersion_favoriIsFalse(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Récupère un utilisateur de test depuis les fixtures
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $utilisateur = $utilisateurRepository->findOneBy(['courriel' => static::$courriel]);
        $preference = $utilisateur->getPreference();

        // Assurer un état de départ contrôlé

        $preference['statut'] = [];
        $preference['suivi_projet'] = [];
        $preference['favori_projet'] = [];
        $preference['bookmark'] = [];
        $preference['favori_version'] = [
            [
                "fr.ma-petite-entreprise:ma-moulinette" => ["1.0.1-RELEASE"]
            ]
        ];
        // Nouveau projet à ajouter en favori
        $map = [
            'favori' => 0,
            'courriel' => static::$courriel,
            'maven_key' => static::$mavenKey,
            'version' => static::$version,
            'date_version' => static::$dateModification,
        ];

        // Appel de la méthode à tester
        $response = $utilisateurRepository->updateUtilisateurFavoriVersion($preference, $map);
        // Assertions
        $this->assertEquals(200, $response['code']);
    }

    /**
     * [Description for testUpdateUtilisateurFavoriVersionAjoutProjetEtVersion_favoriIsFalse2]
     *
     * @return void
     *
     * Created at: 06/07/2025 15:43:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testUpdateUtilisateurFavoriVersionAjoutProjetEtVersion_favoriIsFalse2(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Récupère un utilisateur de test depuis les fixtures
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $utilisateur = $utilisateurRepository->findOneBy(['courriel' => static::$courriel]);
        $preference = $utilisateur->getPreference();

        // Assurer un état de départ contrôlé

        $preference['statut'] = [];
        $preference['suivi_projet'] = [];
        $preference['favori_projet'] = [];
        $preference['bookmark'] = [];
        $preference['favori_version'] = [
            [
                "fr.ma-petite-entreprise:ma-moulinette" => [
                    "1.0.0-RELEASE",
                    '2.0.0-RELEASE',
                    '3.0.0-RELEASE']
            ]
        ];

        // Nouveau projet à ajouter en favori
        $map = [
            'favori' => 0,
            'courriel' => static::$courriel,
            'maven_key' => static::$mavenKey,
            'version' => static::$version,
            'date_version' => static::$dateModification,
        ];

        // Appel de la méthode à tester
        $response = $utilisateurRepository->updateUtilisateurFavoriVersion($preference, $map);

        // Assertions
        $this->assertEquals(200, $response['code']);
        // On décode le jsonArray qui a été utilisé pour stocker les données
        $updatedPreferences = json_decode($response[0], true);

        $versions = $updatedPreferences['favori_version'][0]['fr.ma-petite-entreprise:ma-moulinette'] ?? [];

        $this->assertNotContains('2.0.0-RELEASE', $versions);
        $this->assertContains('1.0.0-RELEASE', $versions);
        $this->assertContains('3.0.0-RELEASE', $versions);
        $this->assertCount(2, $versions);
    }

    /**
     * [Description for testUpdateUtilisateurFavoriProjet_Errer400]
     *
     * @return void
     *
     * Created at: 06/07/2025 15:04:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testUpdateUtilisateurFavoriProjet_Errer400(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Récupère un utilisateur de test depuis les fixtures
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $utilisateur = $utilisateurRepository->findOneBy(['courriel' => static::$courriel]);
        $preference = $utilisateur->getPreference();

        $map = [ 'maven_key' => static::$mavenKey, 'courriel' => static::$courriel ];
        $preference['statut'] = [];
        $preference['suivi_projet'] = [];
        $preference['favori_projet'] = [static::$mavenKey];
        $preference['favori_version'] = [];
        $preference['bookmark'] = ''; // erreur 400

        // Appel de la méthode à tester
        $response = $utilisateurRepository->updateUtilisateurFavoriProjet($preference, $map);
        // Assertions
        $this->assertEquals(400, $response['code']);
    }

    public function testUpdateUtilisateurFavoriProjetFavoriProjet_isNull(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        // Récupère un utilisateur de test depuis les fixtures
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $utilisateur = $utilisateurRepository->findOneBy(['courriel' => static::$courriel]);
        $preference = $utilisateur->getPreference();

        $map = [ 'maven_key' => static::$mavenKey, 'courriel' => static::$courriel ];
        $preference['statut'] = [];
        $preference['suivi_projet'] = [];
        $preference['favori_projet'] = [];
        $preference['favori_version'] = [];
        $preference['bookmark'] = [];

        // Appel de la méthode à tester
        $response = $utilisateurRepository->updateUtilisateurFavoriProjet($preference, $map);

        // Assertions
        $this->assertEquals(200, $response['code']);
        $this->assertEquals(1, $response['statut']);
        $this->assertEmpty($response['erreur']);
    }


    /**
     * [Description for testUpdateUtilisateurResetPassword]
     *  Teste la mise à jour du paramètre init pour le reset du password
     *
     * @return void
     *
     * Created at: 06/05/2024 13:55:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testUpdateUtilisateurResetPassword(): void
    {
        // Connexion à la base de données
        self::bootKernel();
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $map = [
            'init' => 1,
            'date_modification' => new \DateTimeImmutable(static::$dateModification),
            'courriel' => static::$courriel
        ];

        // Appel de la méthode
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $r = $utilisateurRepository->updateUtilisateurResetPassword($map);

        // Assert
        $this->assertEquals(200, $r['code'], static::$erreurCode200);
        $this->assertEmpty($r['erreur'], $r['erreur']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // On se déconnecte pour éviter des problèmes de mémoires
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $entityManager->close();
        $entityManager = null;
    }

}
