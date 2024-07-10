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

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ActuatorInfoRepository;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ActuatorInfoRepository::class)]
#[ORM\Table(name: "actuator_info", schema: "ma_moulinette")]
class ActuatorInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique de la table'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true,
    options: ['comment' => "Description courte."])]
    #[Assert\NotBlank]
    private $actuatorInfoDescription;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true,
    options: ['comment' => "Valeur de la clé actuator."])]
    #[Assert\NotBlank]
    private $actuatorInfoValue;

    #[ORM\ManyToOne(targetEntity: Actuator::class, inversedBy: "actuatorInfo")]
    #[ORM\JoinColumn(name: "actuator_id", referencedColumnName: "id", nullable: false)]
    private $actuator;

    public function getActuator(): ?Actuator
    {
        return $this->actuator;
    }

    public function setActuator(?Actuator $actuator): self
    {
        $this->actuator = $actuator;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActuatorInfoDescription(): ?string
    {
        return $this->actuatorInfoDescription;
    }

    public function setActuatorInfoDescription(?string $actuatorInfoDescription): static
    {
        $this->actuatorInfoDescription = $actuatorInfoDescription;

        return $this;
    }

    public function getActuatorInfoValue(): ?string
    {
        return $this->actuatorInfoValue;
    }

    public function setActuatorInfoValue(?string $actuatorInfoValue): static
    {
        $this->actuatorInfoValue = $actuatorInfoValue;

        return $this;
    }
}
