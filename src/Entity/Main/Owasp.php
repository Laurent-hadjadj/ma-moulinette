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

use App\Repository\Main\OwaspRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OwaspRepository::class)]
class Owasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    private $mavenKey;

    #[ORM\Column(type: 'integer')]
    private $effortTotal;

    #[ORM\Column(type: 'integer')]
    private $a1;

    #[ORM\Column(type: 'integer')]
    private $a2;

    #[ORM\Column(type: 'integer')]
    private $a3;

    #[ORM\Column(type: 'integer')]
    private $a4;

    #[ORM\Column(type: 'integer')]
    private $a5;

    #[ORM\Column(type: 'integer')]
    private $a6;

    #[ORM\Column(type: 'integer')]
    private $a7;

    #[ORM\Column(type: 'integer')]
    private $a8;

    #[ORM\Column(type: 'integer')]
    private $a9;

    #[ORM\Column(type: 'integer')]
    private $a10;

    #[ORM\Column(type: 'integer')]
    private $a1Blocker;

    #[ORM\Column(type: 'integer')]
    private $a1Critical;

    #[ORM\Column(type: 'integer')]
    private $a1Major;

    #[ORM\Column(type: 'integer')]
    private $a1Info;

    #[ORM\Column(type: 'integer')]
    private $a1Minor;

    #[ORM\Column(type: 'integer')]
    private $a2Blocker;

    #[ORM\Column(type: 'integer')]
    private $a2Critical;

    #[ORM\Column(type: 'integer')]
    private $a2Major;

    #[ORM\Column(type: 'integer')]
    private $a2Info;

    #[ORM\Column(type: 'integer')]
    private $a2Minor;

    #[ORM\Column(type: 'integer')]
    private $a3Blocker;

    #[ORM\Column(type: 'integer')]
    private $a3Critical;

    #[ORM\Column(type: 'integer')]
    private $a3Major;

    #[ORM\Column(type: 'integer')]
    private $a3Info;

    #[ORM\Column(type: 'integer')]
    private $a3Minor;

    #[ORM\Column(type: 'integer')]
    private $a4Blocker;

    #[ORM\Column(type: 'integer')]
    private $a4Critical;

    #[ORM\Column(type: 'integer')]
    private $a4Major;

    #[ORM\Column(type: 'integer')]
    private $a4Info;

    #[ORM\Column(type: 'integer')]
    private $a4Minor;

    #[ORM\Column(type: 'integer')]
    private $a5Blocker;

    #[ORM\Column(type: 'integer')]
    private $a5Critical;

    #[ORM\Column(type: 'integer')]
    private $a5Major;

    #[ORM\Column(type: 'integer')]
    private $a5Info;

    #[ORM\Column(type: 'integer')]
    private $a5Minor;

    #[ORM\Column(type: 'integer')]
    private $a6Blocker;

    #[ORM\Column(type: 'integer')]
    private $a6Critical;

    #[ORM\Column(type: 'integer')]
    private $a6Major;

    #[ORM\Column(type: 'integer')]
    private $a6Info;

    #[ORM\Column(type: 'integer')]
    private $a6Minor;

    #[ORM\Column(type: 'integer')]
    private $a7Blocker;

    #[ORM\Column(type: 'integer')]
    private $a7Critical;

    #[ORM\Column(type: 'integer')]
    private $a7Major;

    #[ORM\Column(type: 'integer')]
    private $a7Info;

    #[ORM\Column(type: 'integer')]
    private $a7Minor;

    #[ORM\Column(type: 'integer')]
    private $a8Blocker;

    #[ORM\Column(type: 'integer')]
    private $a8Critical;

    #[ORM\Column(type: 'integer')]
    private $a8Major;

    #[ORM\Column(type: 'integer')]
    private $a8Info;

    #[ORM\Column(type: 'integer')]
    private $a8Minor;

    #[ORM\Column(type: 'integer')]
    private $a9Blocker;

    #[ORM\Column(type: 'integer')]
    private $a9Critical;

    #[ORM\Column(type: 'integer')]
    private $a9Major;

    #[ORM\Column(type: 'integer')]
    private $a9Info;

    #[ORM\Column(type: 'integer')]
    private $a9Minor;

    #[ORM\Column(type: 'integer')]
    private $a10Blocker;

    #[ORM\Column(type: 'integer')]
    private $a10Critical;

    #[ORM\Column(type: 'integer')]
    private $a10Major;

    #[ORM\Column(type: 'integer')]
    private $a10Info;

    #[ORM\Column(type: 'integer')]
    private $a10Minor;

    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;


}
