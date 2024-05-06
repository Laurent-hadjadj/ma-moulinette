<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Main;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\UtilisateurFixtures;
use App\Entity\Main\Utilisateur;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description UtilisateurKernalTest]
 */
class UtilisateurKernelTest extends KernelTestCase
{

  public static $init = 0;
  public static $avatar = 'chiffre/01.png';
  public static $prenom = 'admin';
  public static $nom = '@ma-moulinette';
  public static $courriel = 'admin@ma-moulinette.fr';
  public static $password = '$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K';
  public static $actif = 1;
  public static $roles = ["ROLE_GESTIONNAIRE"];
  public static $equipe = [];
  public static $preference = ['{
    "statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
    "projet":[],"favori":[],"version":[],"bookmark":[]}'];
  public static $dateEnregistrement = '1980-01-01 00:00:00';

  /**
   * [Description for getEntity]
   * Prépare le jeu de données
   *
   * @return Utilisateur
   *
   * Created at: 02/05/2024 20:44:25 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function getEntity(): Utilisateur
  {
    return (new utilisateur())
      ->setInit(static::$init)
      ->setAvatar(static::$avatar)
      ->setPrenom(static::$prenom)
      ->setNom(static::$nom)
      ->setCourriel(static::$courriel)
      ->setPassword(static::$password)
      ->setActif(static::$actif)
      ->setRoles(static::$roles)
      ->setEquipe(static::$equipe)
      ->setPreference(static::$preference)
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }

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
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new UtilisateurFixtures()]);
    }

    /**
     * [Description for testAjoutUtilisateurAvecCourrielExistant]
     *
     * @return void
     *
     * Created at: 05/05/2024 18:18:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testAjoutUtilisateurAvecCourrielExistant(): void
    {
        /* Création d'un nouvel utilisateur avec un courriel déjà existant */
        $utilisateur = $this->getEntity();

        /* Vérification qu'une exception est lancée lors de la tentative d'ajout de l'utilisateur */
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);

        /* Tentative de persistance de l'utilisateur */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        $entityManager->persist($utilisateur);
        $entityManager->flush();
    }

    /**
     * [Description for testCompteUtilisateurNoActif]
     *
     * @return void
     *
     * Created at: 05/05/2024 22:07:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testCompteUtilisateurNoActif(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        /* On compte le nombre d'utilisateur non actif */
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $count = $utilisateurRepository->count(['actif' => false]);
        $this->assertEquals(4, $count);
    }

    /**
     * [Description for testUtilisateurFindOne]
     *
     * @return void
     *
     * Created at: 05/05/2024 22:07:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testUtilisateurFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();

        /* On compte le nombre d'utilisateur non actif */
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $admin = $utilisateurRepository->findOneBy(['prenom' => 'admin']);
        $aurelie = $utilisateurRepository->findOneBy(['prenom' => 'Aurélie']);
        $emma = $utilisateurRepository->findOneBy(['prenom' => 'Emma']);
        $josh = $utilisateurRepository->findOneBy(['prenom' => 'Josh']);
        $nathan = $utilisateurRepository->findOneBy(['prenom' => 'Nathan']);

        $this->assertCount(1, [$admin]);
        $this->assertCount(1, [$aurelie]);
        $this->assertCount(1, [$emma]);
        $this->assertCount(1, [$josh]);
        $this->assertCount(1, [$nathan]);

  }
}
