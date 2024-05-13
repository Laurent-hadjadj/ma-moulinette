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

namespace App\Tests\Unit\Entity;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\UtilisateurFixtures;
use App\Entity\Utilisateur;
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
  public static $actif = true;
  public static $roles = ["ROLE_GESTIONNAIRE"];
  public static $equipe = [];
  public static $preference = ['{
    "statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
    "projet":[],"favori":[],"version":[],"bookmark":[]}'];
  public static $dateEnregistrement = '1980-01-01 00:00:00';

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

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute([new UtilisateurFixtures()]);
    }

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
