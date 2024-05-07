<?php

namespace App\DataFixtures;

use App\Entity\Main\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description UtilisateurFixtures]
 */
class UtilisateurFixtures extends Fixture
{
  public static $preference = ['{
    "statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
    "projet":[],"favori":[],"version":[],"bookmark":[]}'];
  public static $dateEnregistrement = '1980-01-01 00:00:00';

  public function load(ObjectManager $manager): void
    {
     /** création de l'utilisateur  ADMIN */
      $admin=(new Utilisateur())
        ->setInit(1)
        ->setAvatar('chiffre/01.png')
        ->setPrenom('admin')
        ->setNom('@ma-moulinette')
        ->setCourriel('admin@ma-moulinette.fr')
        ->setPassword('$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K')
        ->setActif(1)
        ->setRoles(["ROLE_GESTIONNAIRE"])
        ->setEquipe([])
        ->setPreference(static::$preference)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
        $manager->persist($admin);

        /** Création de l'utilisateur AURELIE */
        $aurelie=(new Utilisateur())
        ->setInit(0)
        ->setAvatar('fille-1/05.png')
        ->setPrenom('Aurélie')
        ->setNom('PETIT COEUR')
        ->setCourriel('aurelie.petit-coeur@ma-moulinette.fr')
        ->setPassword('$2y$13$HMk1rgFp5OiveduUd.dNXeaxq1y/HiActAv3hiMpAFCNsCjNHIFya')
        ->setActif(0)
        ->setRoles(["ROLE_GESTIONNAIRE"])
        ->setEquipe([])
        ->setPreference(static::$preference)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
        $manager->persist($aurelie);

        /** Création de l'utilisateur EMMA */
        $emma=(new Utilisateur())
        ->setInit(0)
        ->setAvatar('fille-2/03.png')
        ->setPrenom('Emma')
        ->setNom('VAN DE BERG')
        ->setCourriel('emma.van-de-berg@ma-moulinette.fr')
        ->setPassword('$2y$13$BrmmLZ3WiFwZcOllwh9zNOrjBRH9RSLEdLCW2y8by5CFX5zS.b1MG')
        ->setActif(0)
        ->setRoles(["ROLE_BATCH"])
        ->setEquipe([])
        ->setPreference(static::$preference)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
        $manager->persist($emma);

        /** Création de l'utilisateur NATHAN */
        $nathan=(new Utilisateur())
        ->setInit(0)
        ->setAvatar('garcon-1/05.png')
        ->setPrenom('Nathan')
        ->setNom('JONES')
        ->setCourriel('nathan.jones@ma-moulinette.fr')
        ->setPassword('$2y$13$hwX0QJOw8fSgjiBq1CL/FuJsf4miOeLJRBw8jzt1WrsV/qLR.DxN.')
        ->setActif(0)
        ->setRoles(["ROLE_COLLECTE"])
        ->setEquipe([])
        ->setPreference(static::$preference)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
        $manager->persist($nathan);

        /** Création de l'utilisateur JOSH */
        $josh=(new Utilisateur())
        ->setInit(0)
        ->setAvatar('garcon-1/10.png')
        ->setPrenom('Josh')
        ->setNom('LIBERMAN')
        ->setCourriel('josh.liberman@ma-moulinette.fr')
        ->setPassword('$2y$13$ON.wYv3nmwkB9N3eOSubt.HFA46NjBHgyvOo6PBs3PVcCPtRb5MSa')
        ->setActif(0)
        ->setRoles(["ROLE_UTILISATEUR"])
        ->setEquipe([])
        ->setPreference(static::$preference)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
        $manager->persist($josh);

        /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
