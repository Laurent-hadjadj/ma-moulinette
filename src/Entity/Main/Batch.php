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

namespace App\Entity\Main;

use App\Repository\Main\BatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** gstion des containtes */
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BatchRepository::class)]
#[UniqueEntity(fields: ['titre'], message: 'Le {{ label }} : {{ value }} existe déjà.')]
#[UniqueEntity(fields: ['portefeuille'], message: 'Le {{ label }} : {{ value }} existe déjà.')]
class Batch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /** Statut d'activité du traitement */
    #[ORM\Column(type: 'boolean' )]
    private $statut=false;

    /** Nom du traitement */
    #[ORM\Column(type: 'string', length: 32, unique: true)]
    private $titre;

    /** Description du traitement */
    #[ORM\Column(type: 'string', length: 128)]
    private $description;

    /** Nom de l'utilisateur */
    #[ORM\Column(type: 'string', length: 128)]
    private $responsable;


    /** Nom du portefeuille de projet */
    #[ORM\Column(type: 'string', length: 32, unique: true)]
    private $portefeuille="Aucun";

    /** Nombre de projet */
    #[ORM\Column(type: 'integer')]
    private $nombreProjet=0;

    /** Date de modification */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateModification;

    /** Date d'enregistrement */
    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;

}
