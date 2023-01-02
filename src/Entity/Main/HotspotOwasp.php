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

use App\Repository\Main\HotspotOwaspRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HotspotOwaspRepository::class)]
class HotspotOwasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    private $mavenKey;

    #[ORM\Column(type: 'string', length: 8)]
    private $menace;

    #[ORM\Column(type: 'string', length: 8)]
    private $probability;

    #[ORM\Column(type: 'string', length: 16)]
    private $status;

    #[ORM\Column(type: 'integer')]
    private $niveau;

    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;

}
