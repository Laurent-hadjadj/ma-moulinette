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

use App\Repository\RciRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/* MODIF 2026-09-25 : entité pour le détail par module (frontend/backend/
 * autre/inconnu) du RCI (Risk Criticality Index, plugin sonar-rci porté en
 * PHP dans BatchCollecteRciController). Snapshot courant, une ligne par
 * maven_key. */

/**
 * [Description Rci]
 */
#[ORM\Entity(repositoryClass: RciRepository::class)]
#[ORM\Table(name: "rci", schema: "ma_moulinette")]
class Rci
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique']
    )]
    private int $id;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255, maxMessage: 'La clé Maven ne doit pas dépasser 255 caractères.')]
    private string $mavenKey;

    /* ── Données globales ────────────────────────────────────────────── */
    #[ORM\Column(name: 'ncloc', type: Types::INTEGER, nullable: false, options: ['comment' => "Ncloc de l'aplication"])]
    #[Assert\PositiveOrZero]
    private int $ncloc = 0;

    #[ORM\Column(name: 'weight', type: Types::INTEGER, nullable: false, options: ['comment' => "Poids pondéré des anomalies de l'application"])]
    #[Assert\PositiveOrZero]
    private int $weight = 0;

    #[ORM\Column(name: 'indicator', type: Types::FLOAT, nullable: true, options: ['comment' => "RCI de l'application. Null si pas disponible"])]
    private ?float $indicator = null;

    #[ORM\Column(name: 'rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => "Note A-E dérivée d'indicator"])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $rating = null;

    /* ── Module frontend ────────────────────────────────────────────── */

    #[ORM\Column(name: 'frontend_ncloc', type: Types::INTEGER, nullable: false, options: ['comment' => 'Ncloc du module frontend'])]
    #[Assert\PositiveOrZero]
    private int $frontendNcloc = 0;

    #[ORM\Column(name: 'frontend_weight', type: Types::INTEGER, nullable: false, options: ['comment' => 'Poids pondéré des anomalies du module frontend'])]
    #[Assert\PositiveOrZero]
    private int $frontendWeight = 0;

    #[ORM\Column(name: 'frontend_indicator', type: Types::FLOAT, nullable: true, options: ['comment' => 'RCI du module frontend. Null si module vide'])]
    private ?float $frontendIndicator = null;

    #[ORM\Column(name: 'frontend_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E dérivée de frontend_indicator'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $frontendRating = null;

    /* ── Module backend ─────────────────────────────────────────────── */

    #[ORM\Column(name: 'backend_ncloc', type: Types::INTEGER, nullable: false, options: ['comment' => 'Ncloc du module backend'])]
    #[Assert\PositiveOrZero]
    private int $backendNcloc = 0;

    #[ORM\Column(name: 'backend_weight', type: Types::INTEGER, nullable: false, options: ['comment' => 'Poids pondéré des anomalies du module backend'])]
    #[Assert\PositiveOrZero]
    private int $backendWeight = 0;

    #[ORM\Column(name: 'backend_indicator', type: Types::FLOAT, nullable: true, options: ['comment' => 'RCI du module backend. Null si module vide'])]
    private ?float $backendIndicator = null;

    #[ORM\Column(name: 'backend_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E dérivée de backend_indicator'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $backendRating = null;

    /* ── Module autre ───────────────────────────────────────────────── */

    #[ORM\Column(name: 'autre_ncloc', type: Types::INTEGER, nullable: false, options: ['comment' => 'Ncloc du module autre'])]
    #[Assert\PositiveOrZero]
    private int $autreNcloc = 0;

    #[ORM\Column(name: 'autre_weight', type: Types::INTEGER, nullable: false, options: ['comment' => 'Poids pondéré des anomalies du module autre'])]
    #[Assert\PositiveOrZero]
    private int $autreWeight = 0;

    #[ORM\Column(name: 'autre_indicator', type: Types::FLOAT, nullable: true, options: ['comment' => 'RCI du module autre. Null si module vide'])]
    private ?float $autreIndicator = null;

    #[ORM\Column(name: 'autre_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E dérivée de autre_indicator'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $autreRating = null;

    /* ── Fichiers non classés ───────────────────────────────────────── */

    #[ORM\Column(name: 'inconnu_ncloc', type: Types::INTEGER, nullable: false, options: ['comment' => 'Ncloc des fichiers non classés'])]
    #[Assert\PositiveOrZero]
    private int $inconnuNcloc = 0;

    #[ORM\Column(name: 'inconnu_weight', type: Types::INTEGER, nullable: false, options: ['comment' => 'Poids pondéré des anomalies des fichiers non classés'])]
    #[Assert\PositiveOrZero]
    private int $inconnuWeight = 0;

    #[ORM\Column(name: 'inconnu_indicator', type: Types::FLOAT, nullable: true, options: ['comment' => 'RCI des fichiers non classés. Null si aucun fichier non classé'])]
    private ?float $inconnuIndicator = null;

    #[ORM\Column(name: 'inconnu_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E dérivée de inconnu_indicator'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $inconnuRating = null;

    /* ── Méta ────────────────────────────────────────────────────────── */

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : [COLLECTE], [TRAITEMENT MANUEL], [TRAITEMENT AUTOMATIQUE]']
    )]
    #[Assert\Length(max: 32)]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => 'Compte utilisateur ayant réalisé la collecte']
    )]
    #[Assert\Length(max: 320)]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d\'enregistrement']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    /* ── Getters / Setters ─────────────────────────────────────────── */

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

    public function getNcloc(): int
    {
        return $this->ncloc;
    }
    public function setNcloc(int $v): static
    {
        $this->ncloc = $v;
        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }
    public function setWeight(int $v): static
    {
        $this->weight = $v;
        return $this;
    }

    public function getIndicator(): ?float
    {
        return $this->indicator;
    }
    public function setIndicator(?float $v): static
    {
        $this->indicator = $v;
        return $this;
    }

    public function getRating(): ?string
    {
        return $this->rating;
    }
    public function setRating(?string $v): static
    {
        $this->rating = $v;
        return $this;
    }

    public function getFrontendNcloc(): int
    {
        return $this->frontendNcloc;
    }
    public function setFrontendNcloc(int $v): static
    {
        $this->frontendNcloc = $v;
        return $this;
    }

    public function getFrontendWeight(): int
    {
        return $this->frontendWeight;
    }
    public function setFrontendWeight(int $v): static
    {
        $this->frontendWeight = $v;
        return $this;
    }

    public function getFrontendIndicator(): ?float
    {
        return $this->frontendIndicator;
    }
    public function setFrontendIndicator(?float $v): static
    {
        $this->frontendIndicator = $v;
        return $this;
    }

    public function getFrontendRating(): ?string
    {
        return $this->frontendRating;
    }
    public function setFrontendRating(?string $v): static
    {
        $this->frontendRating = $v;
        return $this;
    }

    public function getBackendNcloc(): int
    {
        return $this->backendNcloc;
    }
    public function setBackendNcloc(int $v): static
    {
        $this->backendNcloc = $v;
        return $this;
    }

    public function getBackendWeight(): int
    {
        return $this->backendWeight;
    }
    public function setBackendWeight(int $v): static
    {
        $this->backendWeight = $v;
        return $this;
    }

    public function getBackendIndicator(): ?float
    {
        return $this->backendIndicator;
    }
    public function setBackendIndicator(?float $v): static
    {
        $this->backendIndicator = $v;
        return $this;
    }

    public function getBackendRating(): ?string
    {
        return $this->backendRating;
    }
    public function setBackendRating(?string $v): static
    {
        $this->backendRating = $v;
        return $this;
    }

    public function getAutreNcloc(): int
    {
        return $this->autreNcloc;
    }
    public function setAutreNcloc(int $v): static
    {
        $this->autreNcloc = $v;
        return $this;
    }

    public function getAutreWeight(): int
    {
        return $this->autreWeight;
    }
    public function setAutreWeight(int $v): static
    {
        $this->autreWeight = $v;
        return $this;
    }

    public function getAutreIndicator(): ?float
    {
        return $this->autreIndicator;
    }
    public function setAutreIndicator(?float $v): static
    {
        $this->autreIndicator = $v;
        return $this;
    }

    public function getAutreRating(): ?string
    {
        return $this->autreRating;
    }
    public function setAutreRating(?string $v): static
    {
        $this->autreRating = $v;
        return $this;
    }

    public function getInconnuNcloc(): int
    {
        return $this->inconnuNcloc;
    }
    public function setInconnuNcloc(int $v): static
    {
        $this->inconnuNcloc = $v;
        return $this;
    }

    public function getInconnuWeight(): int
    {
        return $this->inconnuWeight;
    }
    public function setInconnuWeight(int $v): static
    {
        $this->inconnuWeight = $v;
        return $this;
    }

    public function getInconnuIndicator(): ?float
    {
        return $this->inconnuIndicator;
    }
    public function setInconnuIndicator(?float $v): static
    {
        $this->inconnuIndicator = $v;
        return $this;
    }

    public function getInconnuRating(): ?string
    {
        return $this->inconnuRating;
    }
    public function setInconnuRating(?string $v): static
    {
        $this->inconnuRating = $v;
        return $this;
    }

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }
    public function setModeCollecte(?string $modeCollecte): static
    {
        $this->modeCollecte = $modeCollecte;
        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }
    public function setUtilisateurCollecte(?string $utilisateurCollecte): static
    {
        $this->utilisateurCollecte = $utilisateurCollecte;
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
