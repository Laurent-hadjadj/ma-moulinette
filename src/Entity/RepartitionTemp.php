<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  https://www.gnu.org/licenses/agpl-3.0.html
*/

namespace App\Entity;

use App\Repository\RepartitionTempRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description RepartitionTemp]
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
#[ORM\Entity(repositoryClass: RepartitionTempRepository::class)]
#[ORM\Table(name: 'repartition_temp', schema: 'ma_moulinette')]
class RepartitionTemp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER
    )]
    private int $id;

    /**
     * Clé d'identification de la répartition
     */
    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => "Clé identification du projet"]
    )]
    #[Assert\NotBlank(groups: ['default'])]
    private string $mavenKey;

    /**
     * Nom du composant
     */
    #[ORM\Column(
        name: 'component',
        type: Types::TEXT,
        nullable: false
    )]
    #[Assert\NotBlank(groups: ['default'])]
    private string $component;

    /**
     * Type de la répartition
     */
    #[ORM\Column(
        name: 'type',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => "Catégorie : BUG, VULNERABILITY ou CODE_SMELL"]
    )]
    #[Assert\NotBlank(groups: ['default'])]
    private string $type;

    /**
     * Gravité de la répartition
     */
    #[ORM\Column(
        name: 'severity',
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => "Niveau de sévérité de l’anomalie"]
    )]
    #[Assert\NotBlank(groups: ['default'])]
    private string $severity;

    /**
     * Timestamp en milliseconde unique pour chaque analyse (utilisé comme partie de la clé composite)
     */
    /* MODIF 2026-05-07 : ajout Assert\PositiveOrZero (rejette -1). */
    #[ORM\Column(
        name: 'setup',
        type: Types::BIGINT,
        nullable: false,
        options: ['comment' => "Timestamp en milliseconde unique pour chaque analyse"]
    )]
    #[Assert\NotBlank(groups: ['default'])]
    #[Assert\PositiveOrZero]
    private int $setup;

    // ----- Getters et Setters -----

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getMavenKey(): string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;
        return $this;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function setComponent(string $component): self
    {
        $this->component = $component;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;
        return $this;
    }

    public function getSetup(): int
    {
        return $this->setup;
    }

    public function setSetup(int $setup): self
    {
        $this->setup = $setup;
        return $this;
    }
}
