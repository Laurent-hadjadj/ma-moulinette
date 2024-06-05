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

namespace App\ApiRessource\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity]
#[ApiResource(description: 'Lance la collecte.',
operations: [new Post()])]

class Collecte
{
  #[ORM\Column(nullable: false)]
  #[Assert\NotBlank]
  public string $maven_key;

  #[ORM\Column(nullable: false)]
  #[Assert\NotBlank]
  public ?string $mode_collecte = 'MANUEL';

}
