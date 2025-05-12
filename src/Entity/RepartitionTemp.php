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

use App\Repository\RepartitionTempRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description RepartitionTemp]
 */

#[ORM\Entity(repositoryClass: RepartitionTempRepository::class)]
#[ORM\Table(name: 'repartition_temp', schema: 'ma_moulinette')]
class RepartitionTemp
{
    /**
     * Clé d'identification de la répartition
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false,
                options: ['comment' => "Clé identification du projet"])]
    #[Assert\NotBlank(groups: ['default'])]
    private string $mavenKey;

    /**
     * Nom du composant
     */
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    #[Assert\NotBlank(groups: ['default'])]
    private string $component;

    /**
     * Type de la répartition
     */
    #[ORM\Column(type: Types::STRING, length: 16, nullable: false,
                options: ['comment' => "Catégorie : BUG, VULNERABILITY ou CODE_SMELL"])]
    #[Assert\NotBlank(groups: ['default'])]
    private string $type;

    /**
     * Gravité de la répartition
     */
    #[ORM\Column(type: Types::STRING, length: 8, nullable: false,
                options: ['comment' => "Niveau de sévérité de l’anomalie"])]
    #[Assert\NotBlank(groups: ['default'])]
    private string $severity;

    /**
     * Timestamp en milliseconde unique pour chaque analyse (utilisé comme partie de la clé composite)
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::BIGINT, nullable: false,
                options: ['comment' => "Timestamp en milliseconde unique pour chaque analyse"])]
    #[Assert\NotBlank(groups: ['default'])]
    private int $setup;

    // ----- Getters et Setters -----

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
