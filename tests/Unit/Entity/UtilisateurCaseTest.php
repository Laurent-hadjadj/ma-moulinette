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

use PHPUnit\Framework\TestCase;
use App\Entity\Utilisateur;

/**
 * [Description UtilisateurTest]
 */
class UtilisateurCaseTest extends TestCase
{

  public static $id = 1;
  public static $init = 1;
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
  public static $dateModification = '1981-01-01 00:00:00';
  public static $dateEnregistrement = '1980-01-01 00:00:00';

  public function getEntity(): Utilisateur
  {
    return (new utilisateur())
      ->setId(static::$id)
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
      ->setDateModification(new \DateTime(static::$dateModification))
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
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
    $this->assertSame('build/avatar/'.static::$avatar, $utilisateur->getAvatarUrl());
  }


  public function testGettersAndSetters(): void
    {
    // Définition de l'entité
    $entity = $this->getEntity();

    // Définition des valeurs
    $entity->setId(static::$id);
    $entity->setInit(static::$init);
    $entity->setAvatar(static::$avatar);
    $entity->setPrenom(static::$prenom);
    $entity->setNom(static::$nom);
    $entity->setCourriel(static::$courriel);
    $entity->setPassword(static::$password);
    $entity->setActif(static::$actif);
    $entity->setRoles(static::$roles);
    $entity->setEquipe(static::$equipe);
    $entity->setPreference(static::$preference);
    $entity->setDateModification(new \DateTime(static::$dateModification));
    $entity->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));

    // Vérification des valeurs
    $this->assertEquals(static::$id, $entity->getId(), "Erreur ID");
    $this->assertEquals(static::$init, $entity->getInit(), "Erreur INIT");
    $this->assertEquals(static::$avatar, $entity->getAvatar(), "Erreur AVATAR");
    $this->assertEquals(static::$prenom, $entity->getPrenom(), "Erreur PRENOM");
    $this->assertEquals(static::$nom, $entity->getNom(), "Erreur NOM");
    $this->assertEquals(static::$courriel, $entity->getCourriel(), "Erreur COURRIEL");
    $this->assertEquals(static::$courriel, $entity->getUserIdentifier(), "Erreur USERIdent");
    $this->assertEquals(static::$password, $entity->getPassword(), "Erreur PASSWORD");
    $entity->eraseCredentials();
    $this->assertNotNull($entity->getPassword(), "Mot de passe null");
    $this->assertTrue(true, $entity->isActif(), "isActif doit être vari");
    $this->assertEquals(static::$roles, $entity->getRoles(), "Erreur ROLES");
    $this->assertEquals(static::$equipe, $entity->getEquipe(), "Erreur EQUIPE");
    $this->assertEquals(static::$preference, $entity->getPreference(),"Erreur PREFERENCE");
    $this->assertEquals(new \DateTime(static::$dateModification), $entity->getDateModification(), "Erreur DATEModification");
    $this->assertEquals(new \DateTime(static::$dateEnregistrement), $entity->getDateEnregistrement(), "Erreur DATEEnregistrement");
  }
}
