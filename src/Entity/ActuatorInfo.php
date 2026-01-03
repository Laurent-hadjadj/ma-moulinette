<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
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
#[ORM\Table(
    name: 'actuator_info',
    schema: 'ma_moulinette',
    options: ['comment' => 'Informations complémentaires associées à un actuator']
)]
#[ORM\Index(name: 'idx_actuator_key', columns: ['actuator_id', 'description'])]
class ActuatorInfo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique de la table actuator_info']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'description',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Description courte de la clé actuator']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $actuatorInfoDescription;

    #[ORM\Column(
        name: 'value',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Valeur associée à la clé actuator']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $actuatorInfoValue;

    #[ORM\ManyToOne(
        targetEntity: Actuator::class,
        inversedBy: 'actuatorInfo'
    )]
    #[ORM\JoinColumn(
        name: 'actuator_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private Actuator $actuator;

    // ===============================
    // Getters / setters
    // ===============================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActuatorInfoDescription(): string
    {
        return $this->actuatorInfoDescription;
    }

    public function setActuatorInfoDescription(string $description): self
    {
        $this->actuatorInfoDescription = $description;
        return $this;
    }

    public function getActuatorInfoValue(): string
    {
        return $this->actuatorInfoValue;
    }

    public function setActuatorInfoValue(string $value): self
    {
        $this->actuatorInfoValue = $value;
        return $this;
    }

    public function getActuator(): Actuator
    {
        return $this->actuator;
    }

    public function setActuator(Actuator $actuator): self
    {
        $this->actuator = $actuator;
        return $this;
    }
}
