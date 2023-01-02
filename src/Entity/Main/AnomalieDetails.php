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

use App\Repository\Main\AnomalieDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnomalieDetailsRepository::class)]
class AnomalieDetails
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    private $mavenKey;

    #[ORM\Column(type: 'string', length: 128)]
    private $name;

    #[ORM\Column(type: 'integer')]
    private $bugBlocker;

    #[ORM\Column(type: 'integer')]
    private $bugCritical;

    #[ORM\Column(type: 'integer')]
    private $bugInfo;

    #[ORM\Column(type: 'integer')]
    private $bugMajor;

    #[ORM\Column(type: 'integer')]
    private $bugMinor;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityBlocker;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityCritical;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityInfo;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityMajor;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityMinor;

    #[ORM\Column(type: 'integer')]
    private $codeSmellBlocker;

    #[ORM\Column(type: 'integer')]
    private $codeSmellCritical;

    #[ORM\Column(type: 'integer')]
    private $codeSmellInfo;

    #[ORM\Column(type: 'integer')]
    private $codeSmellMajor;

    #[ORM\Column(type: 'integer')]
    private $codeSmellMinor;

    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;
}
