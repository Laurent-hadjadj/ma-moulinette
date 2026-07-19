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

use App\Repository\ProfilesHistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProfilesHistoriqueRepository::class)]
#[ORM\Table(name: "profiles_historique", schema: "ma_moulinette")]
#[ORM\UniqueConstraint(
    name: "uniq_profiles_historique_event",
    columns: ["language", "date", "rule", "action"]
)]
class ProfilesHistorique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque historique de profil']
    )]
    private int $id;

    #[ORM\Column(
        name: 'date_courte',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date courte associée à l’historique']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateCourte;

    #[ORM\Column(
        name: 'language',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'language de programmation associé']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le langage ne peut pas dépasser 16 caractères."
    )]
    private string $language;

    #[ORM\Column(
        name: 'date',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date complète de l’événement de l’historique']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $date;

    #[ORM\Column(
        name: 'action',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Action réalisée, par exemple modification ou création']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    private string $action;

    #[ORM\Column(
        name: 'auteur',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Auteur de l’action dans l’historique']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 64,
        maxMessage: "L'auteur ne peut pas dépasser 64 caractères."
    )]
    private string $auteur;

    #[ORM\Column(
        name: 'rule',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Règle ou norme concernée par l’historique']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "La règle ne peut pas dépasser 32 caractères."
    )]
    private string $rule;

    #[ORM\Column(
        name: 'description',
        type: Types::TEXT,
        nullable: false,
        options: ['comment' => 'Description détaillée de l’événement historique']
    )]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(
        name: 'detail',
        type: Types::BLOB,
        nullable: false,
        options: ['comment' => 'Détails supplémentaires ou données binaires associées à l’événement']
    )]
    /* MODIF 2026-05-07 : retrait du type `string` sur $detail.
     * La colonne SQL est `bytea` (Types::BLOB) — Doctrine retourne une `resource` a la
     * lecture. Avec un type `string`, l'hydratation jetait une TypeError. Sans type,
     * la propriété accepte `string` (écriture) ou `resource` (lecture).
     */
    #[Assert\NotNull]
    private $detail;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement de l’entrée historique']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDateCourte(): ?\DateTimeImmutable
    {
        return $this->dateCourte;
    }

    public function setDateCourte(\DateTimeImmutable $dateCourte): static
    {
        $this->dateCourte = $dateCourte;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(string $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getRule(): ?string
    {
        return $this->rule;
    }

    public function setRule(string $rule): static
    {
        $this->rule = $rule;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDetail()
    {
        return $this->detail;
    }

    public function setDetail($detail): static
    {
        $this->detail = $detail;

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
