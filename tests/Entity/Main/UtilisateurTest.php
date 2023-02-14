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

namespace App\Tests\Entity\Main;

use PHPUnit\Framework\TestCase;
use App\Entity\Main\Utilisateur;
use App\Repository\Main\UtilisateurRepository;
use DateTime;

class UtilisateurTest extends TestCase
{

  public function dataset(): array
  {
    return ['id' =>1, 'prenom'=> 'Blue', 'nom'=>'Tooth', 'courriel'=>'a@b.fr',
    'avatar'=>'chiffre/01.png', 'roles'=>[], 'equipe'=>["Aucune"],
    'password'=>'password1', 'actif'=>0, 'dateModification'=> new DateTime(),
    'dateEnregistrement'=> new DateTime()];
  }


  /**
   * [Description for testReturnPersonne]
   *  On récupère une personne
   * @return void
   *
   * Created at: 13/02/2023, 15:18:34 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testReturnPersonne(): void
  {
    $utilisateur = new Utilisateur();
    $utilisateur->setNom("@ma-moulinette");
    $utilisateur->setPrenom("admin");
    $this->assertEquals('@ma-moulinette admin', $utilisateur->getPersonne());
  }

  public function testPersonneIsEmptyByDefault(): void
  {
    $utilisateur = new Utilisateur();
    $this->assertEquals('', $utilisateur->getPersonne());
  }

  public function testAvatarUrlIsNull(): void
  {
    $utilisateur = new Utilisateur();
    $this->assertNull($utilisateur->getAvatarUrl());
  }

  public function testAvatarUrlIsNotNull(): void
  {
    $utilisateur = new Utilisateur();
    $utilisateur->setAvatar('chiffre/01.png');
    $this->assertSame('build/avatar/chiffre/01.png', $utilisateur->getAvatarUrl());
  }

  use PHPUnit\Framework\TestCase;

  /**
   * [Description for testFindAll]
   * On récupère l'ensemble des données, on fait un getPrenom().
   * @return void
   *
   * Created at: 14/02/2023, 07:55:49 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(UtilisateurRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('Blue', $u['prenom']);
  }


  /**
   * [Description for testCount]
   * On compte le nombre d'enregsitrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 07:56:11 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(UtilisateurRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, [$u]);
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
    $this->assertFalse(false, $p->getActif());
    $this->assertNull($p->getSalt());
    $this->assertFalse($p->isActif(), $p->getActif());
    $this->assertSame($d['dateModification'], $p->getDateModification());
    $this->assertSame($d['dateEnregistrement'], $p->getDateEnregistrement());
  }

}
