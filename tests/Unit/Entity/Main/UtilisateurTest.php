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

namespace App\Tests\unit\Entity\Main;

use PHPUnit\Framework\TestCase;
use App\Entity\Main\Utilisateur;
use App\Repository\Main\UtilisateurRepository;
use DateTime;

class UtilisateurTest extends TestCase
{

  /**
   * [Description for dataset]
   * Jeu de données
   * @return array
   *
   * Created at: 14/02/2023, 09:13:24 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return ['id' =>1, 'prenom'=> 'Blue', 'nom'=>'Tooth', 'courriel'=>'a@b.fr',
    'avatar'=>'chiffre/01.png', 'roles'=>[], 'equipe'=>["Aucune"],
    'password'=>'password1', 'actif'=>0, 'dateModification'=> new DateTime(),
    'dateEnregistrement'=> new DateTime()];
  }

  /**
   * [Description for testUtilisateurPersonne]
   *  On récupère une personne
   * @return void
   *
   * Created at: 13/02/2023, 15:18:34 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testUtilisateurPersonne(): void
  {
    $utilisateur = new Utilisateur();
    $utilisateur->setNom("@ma-moulinette");
    $utilisateur->setPrenom("admin");
    $this->assertEquals('@ma-moulinette admin', $utilisateur->getPersonne());
  }

  /**
   * [Description for testUtilisateurPersonneIsEmptyByDefault]
   * On test si la personne est vide par défau.
   * @return void
   *
   * Created at: 14/02/2023, 09:24:11 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testUtilisateurPersonneIsEmptyByDefault(): void
  {
    $utilisateur = new Utilisateur();
    $this->assertEquals('', $utilisateur->getPersonne());
  }

  /**
   * [Description for testUtilisateurAvatarUrlIsNull]
   * On test l'url de retour si l'avatar n'est pas défini
   * @return void
   *
   * Created at: 14/02/2023, 09:24:47 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testUtilisateurAvatarUrlIsNull(): void
  {
    $utilisateur = new Utilisateur();
    $this->assertNull($utilisateur->getAvatarUrl());
  }

  /**
   * [Description for testUtilisateurAvatarUrlIsNotNull]
   * On test l'url de l'avatar si l'avatar existe.
   * @return void
   *
   * Created at: 14/02/2023, 09:25:26 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testUtilisateurAvatarUrlIsNotNull(): void
  {
    $utilisateur = new Utilisateur();
    $utilisateur->setAvatar('chiffre/01.png');
    $this->assertSame('build/avatar/chiffre/01.png', $utilisateur->getAvatarUrl());
  }

  /**
   * [Description for testUtilisateurFindAll]
   * On récupère l'ensemble des données, on fait un getPrenom().
   * @return void
   *
   * Created at: 14/02/2023, 07:55:49 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testUtilisateurFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(UtilisateurRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('Blue', $u['prenom']);
  }


  /**
   * [Description for testUtilisateurCount]
   * On compte le nombre d'enregsitrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 07:56:11 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testUtilisateurCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(UtilisateurRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, [$u]);
  }

  /**
   * [Description for testUtilisateurCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 14/02/2023, 15:19:00 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testUtilisateurCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(Utilisateur::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d), $nb);
  }

  /**
   * [Description for testUtilisateur]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 07:56:27 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testUtilisateur(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $p = new Utilisateur();
    $p->setId($d['id']);
    $p->setPrenom($d['prenom']);
    $p->setNom($d['nom']);
    $p->setCourriel($d['courriel']);
    $p->setAvatar($d['avatar']);
    $p->setRoles($d['roles']);
    $p->setEquipe($d['equipe']);
    $p->setPassword($d['password']);
    $p->setActif($d['actif']);
    $p->setDateModification($d['dateModification']);
    $p->setDateEnregistrement($d['dateEnregistrement']);

    $this->assertEquals(1, $p->getId());
    $this->assertSame($d['prenom'], $p->getPrenom());
    $this->assertSame($d['nom'], $p->getNom());
    $this->assertSame($d['courriel'], $p->getCourriel());
    $this->assertSame($d['avatar'], $p->getAvatar());
    $this->assertSame($d['roles'], $p->getRoles());
    $this->assertSame($d['equipe'], $p->getEquipe());
    $this->assertSame($d['password'], $p->getPassword());
    $this->assertFalse(false, $p->isActif());
    $this->assertNull($p->getSalt());
    $this->assertSame($d['dateModification'], $p->getDateModification());
    $this->assertSame($d['dateEnregistrement'], $p->getDateEnregistrement());
  }

}
