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

use App\Repository\Main\AnomalieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnomalieRepository::class)]
class Anomalie
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    private $mavenKey;

    #[ORM\Column(type: 'string', length: 128)]
    private $projectName;

    #[ORM\Column(type: 'integer')]
    private $anomalieTotal;

    #[ORM\Column(type: 'integer')]
    private $detteMinute;

    #[ORM\Column(type: 'integer')]
    private $detteReliabilityMinute;

    #[ORM\Column(type: 'integer')]
    private $detteVulnerabilityMinute;

    #[ORM\Column(type: 'integer')]
    private $detteCodeSmellMinute;

    #[ORM\Column(type: 'string', length: 32)]
    private $detteReliability;

    #[ORM\Column(type: 'string', length: 32)]
    private $detteVulnerability;

    #[ORM\Column(type: 'string', length: 32)]
    private $dette;

    #[ORM\Column(type: 'string', length: 32)]
    private $detteCodeSmell;

    #[ORM\Column(type: 'integer')]
    private $frontend;

    #[ORM\Column(type: 'integer')]
    private $backend;

    #[ORM\Column(type: 'integer')]
    private $autre;

    #[ORM\Column(type: 'integer')]
    private $blocker;

    #[ORM\Column(type: 'integer')]
    private $critical;

    #[ORM\Column(type: 'integer')]
    private $major;

    #[ORM\Column(type: 'integer')]
    private $info;

    #[ORM\Column(type: 'integer')]
    private $minor;

    #[ORM\Column(type: 'integer')]
    private $bug;

    #[ORM\Column(type: 'integer')]
    private $vulnerability;

    #[ORM\Column(type: 'integer')]
    private $codeSmell;

    #[ORM\Column(type: 'boolean')]
    private $liste;

    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;
}
