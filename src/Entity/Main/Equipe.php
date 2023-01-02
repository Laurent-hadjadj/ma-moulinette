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

use App\Repository\Main\EquipeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/** gstion des containtes */
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipeRepository::class)]
#[UniqueEntity(fields: ['titre'], message: 'Le {{ label }} : {{ value }} existe déjà.')]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 32, unique: true)]
    private $titre;

    #[ORM\Column(type: 'string', length: 128)]
    private $description;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateModification;

    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;

}
