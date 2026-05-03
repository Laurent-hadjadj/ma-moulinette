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

namespace App\Tests\Unit\Entity\Case;

use PHPUnit\Framework\TestCase;
use App\Entity\Utilisateur;

/**
 * [Description UtilisateurTest]
 */
class UtilisateurCaseTest extends TestCase
{

  public static int $resetPassword = 1;
  public static int $resetPasswordCount = 1;
  public static string $avatar = 'chiffre/01.png';
  public static string $prenom = 'admin';
  public static string $nom = '@ma-moulinette';
  public static string $courriel = 'admin@ma-moulinette.fr';
  public static string $pass = '$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K';
  public static bool $actif = true;
  public static $roles = ["ROLE_GESTIONNAIRE"];
  public static string $groupeUtilisateur = 'admin';
  public static string $groupeId = 'gest-001';
  public static $listeGroupeFonctionnel = ['fr.ma-petite-entreprise:ma-moulinette'];
  public static $preference = ['{
    "statut":{"projet":false,"favori":false,"version":false},
    "projet":[],"favori":[],"version":[]}'];
  public static string $lastActivityAt = '2024-12-15 09:42:00';
  public static string $dateModification = '1981-01-01 00:00:00';
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
      ->setPassword(self::$pass)
      ->setActif(self::$actif)
      ->setRoles(self::$roles)
      ->setGroupeUtilisateur(self::$groupeUtilisateur)
      ->setGroupeId(self::$groupeId)
      ->setListeGroupeFonctionnel(self::$listeGroupeFonctionnel)
      ->setPreference(self::$preference)
      ->setLastActivityAt(new \DateTimeImmutable(self::$lastActivityAt))
      ->setDateModification(new \DateTime(self::$dateModification))
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
  }

  public function testUtilisateurPersonne(): void
  {
    $utilisateur = new Utilisateur();
    $utilisateur->setNom(self::$nom);
    $utilisateur->setPrenom(self::$prenom);
    $this->assertEquals(self::$nom .' '. self::$prenom, $utilisateur->getPersonne());
  }

  public function testUtilisateurPersonneIsEmptyByDefault(): void
  {
    $utilisateur = new Utilisateur();
    $this->assertEquals('', $utilisateur->getPersonne(), "La personne ne peut pas être vide.");
  }

  public function testUtilisateurAvatarUrlIsNull(): void
  {
    $utilisateur = new Utilisateur();
    $this->assertNull($utilisateur->getAvatarUrl(), "L'url de l'avatar ne peux pas être vide.");
  }

  public function testUtilisateurAvatarUrlIsNotNull(): void
  {
    $utilisateur = new Utilisateur();
    $utilisateur->setAvatar(self::$avatar);
    $this->assertSame('/avatar/'.self::$avatar, $utilisateur->getAvatarUrl());
  }

  public function testGettersAndSetters(): void
    {
    // Définition de l'entité
    $entity = $this->getEntity();

    // Définition des valeurs
    $entity->setId(1);
    $entity->setResetPassword((bool) self::$resetPassword);
    $entity->setResetPasswordCount(1);
    $entity->setAvatar(self::$avatar);
    $entity->setPrenom(self::$prenom);
    $entity->setNom(self::$nom);
    $entity->setCourriel(self::$courriel);
    $entity->setPassword(self::$pass);
    $entity->setActif(self::$actif);
    $entity->setRoles(self::$roles);
    $entity->setGroupeUtilisateur(self::$groupeUtilisateur);
    $entity->setGroupeId(self::$groupeId);
    $entity->setListeGroupeFonctionnel(self::$listeGroupeFonctionnel);
    $entity->setPreference(self::$preference);
    $entity->setLastActivityAt(new \DateTimeImmutable(self::$lastActivityAt));
    $entity->setDateModification(new \DateTime(self::$dateModification));
    $entity->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));

    // Vérification des valeurs
    $this->assertEquals(1, $entity->getId(), "Erreur ID");
    $this->assertEquals(self::$resetPassword, $entity->isResetPassword(), "Erreur Reset Password");
    $this->assertEquals(self::$resetPasswordCount, $entity->getResetPasswordCount(), "Erreur Reset Password Count");
    $this->assertEquals(self::$avatar, $entity->getAvatar(), "Erreur AVATAR");
    $this->assertEquals(self::$prenom, $entity->getPrenom(), "Erreur PRENOM");
    $this->assertEquals(self::$nom, $entity->getNom(), "Erreur NOM");
    $this->assertEquals(self::$courriel, $entity->getCourriel(), "Erreur COURRIEL");
    $this->assertEquals(self::$courriel, $entity->getUserIdentifier(), "Erreur USERIdent");
    $this->assertEquals(self::$pass, $entity->getPassword(), "Erreur PASSWORD");
    $entity->eraseCredentials();
    $this->assertNotNull($entity->getPassword(), "Mot de passe null");
    $this->assertTrue($entity->isActif(), "isActif doit être vrai");
    $this->assertEquals(self::$roles, $entity->getRoles(), "Erreur ROLES");
    $this->assertEquals(self::$groupeUtilisateur, $entity->getGroupeUtilisateur(), "Erreur GROUPE_UTILISATEUR");
    $this->assertEquals(self::$groupeId, $entity->getGroupeId(), "Erreur GROUPE_ID");
    $this->assertEquals(self::$listeGroupeFonctionnel, $entity->getListeGroupeFonctionnel(), "Erreur LISTE_GROUPE_FONCTIONNEL");
    $this->assertEquals(self::$preference, $entity->getPreference(),"Erreur PREFERENCE");
    $this->assertEquals(new \DateTimeImmutable(self::$lastActivityAt), $entity->getLastActivityAt(), "Erreur LAST_ACTIVITY_AT");
    $this->assertEquals(new \DateTime(self::$dateModification), $entity->getDateModification(), "Erreur DATEModification");
    $this->assertEquals(new \DateTimeImmutable(self::$dateEnregistrement), $entity->getDateEnregistrement(), "Erreur DATEEnregistrement");
  }

  public function testCountAttribut(): void
  {
    $reflectionClass = new \ReflectionClass(new Utilisateur());
    $this->assertEquals(17, count($reflectionClass->getProperties()));
  }
}
