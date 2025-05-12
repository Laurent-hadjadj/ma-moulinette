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

use App\Repository\ProfilesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description Profiles]
 */
#[ORM\Entity(repositoryClass: ProfilesRepository::class)]
#[ORM\Table(name: "profiles", schema: "ma_moulinette")]
class Profiles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque profil'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Clé unique du profil'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255,
        maxMessage: "La clé du profil ne doit pas dépasser 32 caractères.")]
    private $key;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => 'Nom du profil'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom ne peut pas dépasser 128 caractères.")]
    private $name;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: false,
        options: ['comment' => 'Nom du langage de programmation'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64,
        maxMessage: "Le langage ne peut pas dépasser 64 caractères.")]
    private $languageName;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Nombre de règles actives associées au profil'])]
    #[Assert\NotNull]
    private $activeRuleCount;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date de la dernière mise à jour des règles'])]
    #[Assert\NotNull]
    private $rulesUpdatedAt;

    #[ORM\Column(type: TYPES::BOOLEAN, nullable: false,
        options: ['comment' => 'Indique si le profil est le profil par défaut'])]
    #[Assert\NotNull]
    private $referentialDefault;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement du profil'])]
    #[Assert\NotNull]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLanguageName(): ?string
    {
        return $this->languageName;
    }

    public function setLanguageName(string $languageName): static
    {
        $this->languageName = $languageName;

        return $this;
    }

    public function getActiveRuleCount(): ?int
    {
        return $this->activeRuleCount;
    }

    public function setActiveRuleCount(int $activeRuleCount): static
    {
        $this->activeRuleCount = $activeRuleCount;

        return $this;
    }

    public function getRulesUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->rulesUpdatedAt;
    }

    public function setRulesUpdatedAt(\DateTimeImmutable $rulesUpdatedAt): static
    {
        $this->rulesUpdatedAt = $rulesUpdatedAt;

        return $this;
    }

    public function isReferentialDefault(): ?bool
    {
        return $this->referentialDefault;
    }

    public function setReferentialDefault(bool $referentialDefault): static
    {
        $this->referentialDefault = $referentialDefault;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): static
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }

}
