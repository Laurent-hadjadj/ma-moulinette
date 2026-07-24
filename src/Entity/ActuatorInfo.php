<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
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
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique de la table']
    )]
    private int $id;

    #[ORM\Column(
        name: 'actuator_info_description',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => "Description courte."]
    )]
    #[Assert\NotBlank]
    private ?string $actuatorInfoDescription = null;

    #[ORM\Column(
        name: 'actuator_info_cle',
        type: Types::STRING,
        length: 128,
        nullable: true,
        options: ['comment' => "Nœud JSON à extraire de la réponse actuator/info (ex. app.version)."]
    )]
    #[Assert\NotBlank(message: "Entrez le nom de la clé (nœud JSON, ex. app.version).")]
    private ?string $actuatorInfoCle = null;

    #[ORM\ManyToOne(targetEntity: Actuator::class, inversedBy: "actuatorInfo")]
    #[ORM\JoinColumn(name: "actuator_id", referencedColumnName: "id", nullable: false)]
    private ?Actuator $actuator = null;

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
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
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

    public function getActuatorInfoCle(): ?string
    {
        return $this->actuatorInfoCle;
    }

    public function setActuatorInfoCle(?string $actuatorInfoCle): static
    {
        $this->actuatorInfoCle = $actuatorInfoCle;

        return $this;
    }
}
