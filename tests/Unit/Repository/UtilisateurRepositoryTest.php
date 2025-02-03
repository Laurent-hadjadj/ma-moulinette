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
