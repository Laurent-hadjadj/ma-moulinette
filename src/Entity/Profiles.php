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
#[ORM\Table(
    name: 'profiles',
    schema: "ma_moulinette",
    options: ['comment' => 'Table des profiles de règles SonarQube.'])]
class Profiles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique pour chaque profil'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'key',
        type: Types::STRING,
        length: 56,
        nullable: false,
        options: ['comment' => 'Clé unique du profil'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 56,
        maxMessage: "La clé du profil ne doit pas dépasser 56 caractères.")]
    private string $key;

    #[ORM\Column(
        name: 'name',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du profil'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom ne peut pas dépasser 128 caractères.")]
    private string $name;

    #[ORM\Column(
        name: 'language_name',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Nom du langage de programmation'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 64,
        maxMessage: "Le langage ne peut pas dépasser 64 caractères.")]
    private string $languageName;

    #[ORM\Column(
        name: 'active_rule_count',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de règles actives associées au profil'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $activeRuleCount;

    #[ORM\Column(
        name: 'rules_updated_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de la dernière mise à jour des règles'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $rulesUpdatedAt;

    #[ORM\Column(
        'referential_default',
        type: Types::BOOLEAN,
        nullable: false,
        options: ['comment' => 'Indique si le profil est le profil par défaut'])]
    #[Assert\NotNull]
    private bool $referentialDefault;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du profil'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->dateEnregistrement = new \DateTimeImmutable();
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

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLanguageName(): ?string
    {
        return $this->languageName;
    }

    public function setLanguageName(string $languageName): self
    {
        $this->languageName = $languageName;

        return $this;
    }

    public function getActiveRuleCount(): ?int
    {
        return $this->activeRuleCount;
    }

    public function setActiveRuleCount(int $activeRuleCount): self
    {
        $this->activeRuleCount = $activeRuleCount;

        return $this;
    }

    public function getRulesUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->rulesUpdatedAt;
    }

    public function setRulesUpdatedAt(\DateTimeImmutable $rulesUpdatedAt): self
    {
        $this->rulesUpdatedAt = $rulesUpdatedAt;

        return $this;
    }

    public function isReferentialDefault(): ?bool
    {
        return $this->referentialDefault;
    }

    public function setReferentialDefault(bool $referentialDefault): self
    {
        $this->referentialDefault = $referentialDefault;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }

}
