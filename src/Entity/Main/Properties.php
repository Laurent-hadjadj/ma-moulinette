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

use App\Repository\Main\PropertiesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertiesRepository::class)]
class Properties
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**  Type = property */
    #[ORM\Column(type: 'string')]
    private $type;

    #[ORM\Column(type: 'integer')]
    private $projetBd;

    #[ORM\Column(type: 'integer')]
    private $projetSonar;

    #[ORM\Column(type: 'integer')]
    private $profilBd;

    #[ORM\Column(type: 'integer')]
    private $profilSonar;

    #[ORM\Column(type: 'datetime')]
    private $dateCreation;

    #[ORM\Column(type: 'datetime', nullable:true)]
    private $dateModificationProjet;

    #[ORM\Column(type: 'datetime', nullable:true)]
    private $dateModificationProfil;

}
