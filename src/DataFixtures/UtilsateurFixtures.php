<?php

namespace App\DataFixtures;

use App\Entity\Main\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UtilisateurFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
     /** création des utilisateurs de tests */
      $utilisateur=(new Utilisateur())
        ->setInit(1)
        ->setCourriel('admin@ma-moulinette.fr')
        ->setRoles(["ROLE_GESTIONNAIRE"])
        ->setPassword('$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K')
        ->setPrenom('admin')
        ->setNom('@ma-moulinette')
        ->setDateEnregistrement(new \DateTime('1980-01-01 00:00:00'))
        ->setActif(1)
        ->setAvatar('chiffre/01.png')
        ->setEquipe([])
        ->setPreference(['{
          "statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
          "projet":[],"favori":[],"version":[],"bookmark":[]}']);

        $manager->persist(($utilisateur));
        $manager->flush();
    }
  }
