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

  public static $resetPassword = 1;
  public static $resetPasswordCount = 1;
  public static $avatar = 'chiffre/01.png';
  public static $prenom = 'admin';
  public static $nom = '@ma-moulinette';
  public static $courriel = 'admin@ma-moulinette.fr';
  public static $pass = '$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K';
  public static $actif = true;
  public static $roles = ["ROLE_GESTIONNAIRE"];
  public static $groupeUtilisateur = 'admin';
  public static $groupeId = 'gest-001';
  public static $listeGroupeFonctionnel = ['fr.ma-petite-entreprise:ma-moulinette'];
  public static $preference = ['{
    "statut":{"projet":false,"favori":false,"version":false},
    "projet":[],"favori":[],"version":[]}'];
  public static $lastActivityAt = '2024-12-15 09:42:00';
  public static $dateModification = '1981-01-01 00:00:00';
  public static $dateEnregistrement = '1980-01-01 00:00:00';

  public function getEntity(): Utilisateur
  {
    return (new utilisateur())
      ->setResetPassword((bool) static::$resetPassword)
      ->setResetPasswordCount(1)
      ->setAvatar(static::$avatar)
      ->setPrenom(static::$prenom)
      ->setNom(static::$nom)
      ->setCourriel(static::$courriel)
      ->setPassword(static::$pass)
      ->setActif(static::$actif)
      ->setRoles(static::$roles)
      ->setGroupeUtilisateur(static::$groupeUtilisateur)
      ->setGroupeId(static::$groupeId)
      ->setListeGroupeFonctionnel(static::$listeGroupeFonctionnel)
      ->setPreference(static::$preference)
      ->setLastActivityAt(new \DateTimeImmutable(static::$lastActivityAt))
      ->setDateModification(new \DateTime(static::$dateModification))
      ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
  }

  public function testUtilisateurPersonne(): void
  {
    $utilisateur = new Utilisateur();
    $utilisateur->setNom(static::$nom);
    $utilisateur->setPrenom(static::$prenom);
    $this->assertEquals(static::$nom .' '. static::$prenom, $utilisateur->getPersonne());
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
    $utilisateur->setAvatar(static::$avatar);
    $this->assertSame('/avatar/'.static::$avatar, $utilisateur->getAvatarUrl());
  }

  public function testGettersAndSetters(): void
    {
    // Définition de l'entité
    $entity = $this->getEntity();

    // Définition des valeurs
    $entity->setId(1);
    $entity->setResetPassword((bool) static::$resetPassword);
    $entity->setResetPasswordCount(1);
    $entity->setAvatar(static::$avatar);
    $entity->setPrenom(static::$prenom);
    $entity->setNom(static::$nom);
    $entity->setCourriel(static::$courriel);
    $entity->setPassword(static::$pass);
    $entity->setActif(static::$actif);
    $entity->setRoles(static::$roles);
    $entity->setGroupeUtilisateur(static::$groupeUtilisateur);
    $entity->setGroupeId(static::$groupeId);
    $entity->setListeGroupeFonctionnel(static::$listeGroupeFonctionnel);
    $entity->setPreference(static::$preference);
    $entity->setLastActivityAt(new \DateTimeImmutable(static::$lastActivityAt));
    $entity->setDateModification(new \DateTime(static::$dateModification));
    $entity->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));

    // Vérification des valeurs
    $this->assertEquals(1, $entity->getId(), "Erreur ID");
    $this->assertEquals(static::$resetPassword, $entity->isResetPassword(), "Erreur Reset Password");
    $this->assertEquals(static::$resetPasswordCount, $entity->getResetPasswordCount(), "Erreur Reset Password Count");
    $this->assertEquals(static::$avatar, $entity->getAvatar(), "Erreur AVATAR");
    $this->assertEquals(static::$prenom, $entity->getPrenom(), "Erreur PRENOM");
    $this->assertEquals(static::$nom, $entity->getNom(), "Erreur NOM");
    $this->assertEquals(static::$courriel, $entity->getCourriel(), "Erreur COURRIEL");
    $this->assertEquals(static::$courriel, $entity->getUserIdentifier(), "Erreur USERIdent");
    $this->assertEquals(static::$pass, $entity->getPassword(), "Erreur PASSWORD");
    $entity->eraseCredentials();
    $this->assertNotNull($entity->getPassword(), "Mot de passe null");
    $this->assertTrue($entity->isActif(), "isActif doit être vrai");
    $this->assertEquals(static::$roles, $entity->getRoles(), "Erreur ROLES");
    $this->assertEquals(static::$groupeUtilisateur, $entity->getGroupeUtilisateur(), "Erreur GROUPE_UTILISATEUR");
    $this->assertEquals(static::$groupeId, $entity->getGroupeId(), "Erreur GROUPE_ID");
    $this->assertEquals(static::$listeGroupeFonctionnel, $entity->getListeGroupeFonctionnel(), "Erreur LISTE_GROUPE_FONCTIONNEL");
    $this->assertEquals(static::$preference, $entity->getPreference(),"Erreur PREFERENCE");
    $this->assertEquals(new \DateTimeImmutable(static::$lastActivityAt), $entity->getLastActivityAt(), "Erreur LAST_ACTIVITY_AT");
    $this->assertEquals(new \DateTime(static::$dateModification), $entity->getDateModification(), "Erreur DATEModification");
    $this->assertEquals(new \DateTimeImmutable(static::$dateEnregistrement), $entity->getDateEnregistrement(), "Erreur DATEEnregistrement");
  }

  public function testCountAttribut(): void
  {
    $reflectionClass = new \ReflectionClass(new Utilisateur());
    $this->assertEquals(17, count($reflectionClass->getProperties()));
  }
}
