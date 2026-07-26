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

namespace App\Tests\Integration\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\DataFixtures\UtilisateurFixtures;
use App\Entity\Utilisateur;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

/**
 * [Description UtilisateurKernelTest]
 */
class UtilisateurKernelTest extends KernelTestCase
{

  public static int $resetPassword = 0;
  public static int $resetPasswordCount = 1;
  public static string $avatar = 'chiffre/01.png';
  public static string $prenom = 'admin';
  public static string $nom = '@ma-moulinette';
  public static string $courriel = 'admin@ma-moulinette.fr';
  public static string $password = '$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K';
  public static bool $actif = true;
  /** @var array<int|string, mixed> */
  public static array $roles = ["ROLE_GESTIONNAIRE"];
  /** @var array<int, string> */
  public static array $groupe = [];
  /** @var array<int|string, mixed> */
  public static array $preference = ['{
    "statut":{"projet":false,"favori":false,"version":false},
    "projet":[],"favori":[],"version":[]}'];
  public static string $dateEnregistrement = '1980-01-01 00:00:00';

  public function getEntity(): Utilisateur
  {
    return (new utilisateur())
      ->setResetPassword((bool) self::$resetPassword)
      ->setResetPasswordCount(1)
      ->setAvatar(self::$avatar)
      ->setPrenom(self::$prenom)
      ->setNom(self::$nom)
      ->setCourriel(self::$courriel)
      ->setPassword(self::$password)
      ->setActif(self::$actif)
      ->setRoles(self::$roles)
      ->setListeGroupeFonctionnel(self::$groupe)
      ->setPreference(self::$preference)
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
  }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        // Réinitialiser la séquence
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $sequence = 'ma_moulinette.utilisateur_id_seq';
            $connection->executeQuery("SELECT setval('$sequence', 1, false);");
        }

        $entityManager->getConnection()->executeStatement('DELETE FROM ma_moulinette.utilisateur');
        $executor = new ORMExecutor($entityManager);
        $executor->execute([new UtilisateurFixtures()], true);
    }

    public function testAjoutUtilisateurAvecCourrielExistant(): void
    {
        /* Création d'un nouvel utilisateur avec un courriel déjà existant */
        $utilisateur = $this->getEntity();

        /* Vérification qu'une exception est lancée lors de la tentative d'ajout de l'utilisateur */
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);

        /* Tentative de persistance de l'utilisateur */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        $entityManager->persist($utilisateur);
        $entityManager->flush();
    }

    public function testCompteUtilisateurNoActif(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        /* On compte le nombre d'utilisateur non actif */
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $count = $utilisateurRepository->count(['actif' => false]);
        $this->assertEquals(4, $count);
    }

    public function testUtilisateurFindOneBy(): void
    {
        /* On se connecte à la base de tests */
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);

        /* On compte le nombre d'utilisateur non actif */
        $utilisateurRepository = $entityManager->getRepository(Utilisateur::class);
        $admin = $utilisateurRepository->findOneBy(['prenom' => 'admin']);
        $aurelie = $utilisateurRepository->findOneBy(['prenom' => 'Aurélie']);
        $emma = $utilisateurRepository->findOneBy(['prenom' => 'Emma']);
        $josh = $utilisateurRepository->findOneBy(['prenom' => 'Josh']);
        $nathan = $utilisateurRepository->findOneBy(['prenom' => 'Nathan']);

        $this->assertNotNull($admin);
        $this->assertNotNull($aurelie);
        $this->assertNotNull($emma);
        $this->assertNotNull($josh);
        $this->assertNotNull($nathan);

  }
}
