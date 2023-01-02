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

use App\Repository\Main\BatchTraitementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BatchTraitementRepository::class)]
class BatchTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /** Démarage ? Manuel ou automatique */
    #[ORM\Column(type: 'string', length: 16, )]
    private $demarrage="Manuel";

    /** Résultat  */
    #[ORM\Column(type: 'boolean' )]
    private $resultat=0;

    /** Nom du traitement */
    #[ORM\Column(type: 'string', length: 32)]
    private $titre;

    /** Nom du portefeuille de projet */
    #[ORM\Column(type: 'string', length: 32)]
    private $portefeuille="Aucun";

    /** Nombre de projet */
    #[ORM\Column(type: 'integer')]
    private $nombreProjet=0;

    #[ORM\Column(type: 'string', length: 128)]
    private $responsable;

    /** Debut du traitement */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $debutTraitement;

    /** Fin du traitement */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $finTraitement;

    /** Date d'enregistrement */
    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;

    }
