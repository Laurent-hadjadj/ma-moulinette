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

use App\Repository\ProfilesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProfilesRepository::class)]
class Profiles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque profil']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé unique du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères."
    )]
    private $mavenKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du profil']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private $name;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Nom du langage de programmation']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private $languageName;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de règles actives associées au profil']
    )]
    #[Assert\NotNull]
    private $activeRuleCount;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de la dernière mise à jour des règles']
    )]
    #[Assert\NotNull]
    private $rulesUpdateAt;

    #[ORM\Column(
        type: TYPES::BOOLEAN,
        nullable: false,
        options: ['comment' => 'Indique si le profil est le profil par défaut']
    )]
    #[Assert\NotNull]
    private $referentielDefault;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du profil']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): static
    {
        $this->mavenKey = $mavenKey;

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

    public function getRulesUpdateAt(): ?\DateTimeImmutable
    {
        return $this->rulesUpdateAt;
    }

    public function setRulesUpdateAt(\DateTimeImmutable $rulesUpdateAt): static
    {
        $this->rulesUpdateAt = $rulesUpdateAt;

        return $this;
    }

    public function isReferentielDefault(): ?bool
    {
        return $this->referentielDefault;
    }

    public function setReferentielDefault(bool $referentielDefault): static
    {
        $this->referentielDefault = $referentielDefault;

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
