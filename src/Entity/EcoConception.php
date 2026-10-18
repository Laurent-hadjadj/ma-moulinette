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

use App\Repository\EcoConceptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/* MODIF 2026-10-08 : entité pour l'Empreinte Écologique (plugins SonarQube
 * Creedengo creedengo-java/javascript/php/python, activable via la propriété
 * CREEDENGO_ENABLED). Snapshot courant, une ligne par maven_key.
 * Ajout de la note A-E (seuils SQALE 5/10/20/50%, identiques au RCI)
 * et du détail par langage.
 */

/**
 * [Description EcoConception]
 */
#[ORM\Entity(repositoryClass: EcoConceptionRepository::class)]
#[ORM\Table(name: "eco_conception", schema: "ma_moulinette")]
class EcoConception
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

    /* ── Global ──────────────────────────────────────────────────────── */

    #[ORM\Column(name: 'eco_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => "Nombre d'anomalies éco-conception (OPEN), tous langages confondus. Null si le plugin est désactivé ou jamais collecté."])]
    #[Assert\PositiveOrZero]
    private ?int $ecoAnomalies = null;

    #[ORM\Column(name: 'eco_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes (effortTotal SonarQube), tous langages confondus. Null dans les mêmes conditions.'])]
    #[Assert\PositiveOrZero]
    private ?int $ecoDetteMinute = null;

    #[ORM\Column(name: 'eco_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E dérivée du ratio eco_dette_minute / (ncloc * 30), seuils SQALE 5/10/20/50%.'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $ecoRating = null;

    /* ── Java ────────────────────────────────────────────────────────── */

    #[ORM\Column(name: 'eco_java_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => 'Nombre d\'anomalies éco-conception (OPEN) pour le langage Java.'])]
    #[Assert\PositiveOrZero]
    private ?int $ecoJavaAnomalies = null;

    #[ORM\Column(name: 'eco_java_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes pour le langage Java.'])]
    #[Assert\PositiveOrZero]
    private ?int $ecoJavaDetteMinute = null;

    #[ORM\Column(name: 'eco_java_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E pour le langage Java.'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $ecoJavaRating = null;

    /* ── JavaScript ──────────────────────────────────────────────────── */

    #[ORM\Column(name: 'eco_javascript_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => 'Nombre d\'anomalies éco-conception (OPEN) pour le langage JavaScript.'])]
    #[Assert\PositiveOrZero]
    private ?int $ecoJavascriptAnomalies = null;

    #[ORM\Column(name: 'eco_javascript_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes pour le langage JavaScript.'])]
    #[Assert\PositiveOrZero]
    private ?int $ecoJavascriptDetteMinute = null;

    #[ORM\Column(name: 'eco_javascript_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E pour le langage JavaScript.'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $ecoJavascriptRating = null;

    /* ── PHP ─────────────────────────────────────────────────────────── */

    #[ORM\Column(name: 'eco_php_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => 'Nombre d\'anomalies éco-conception (OPEN) pour le langage PHP.'])]
    #[Assert\PositiveOrZero]
    private ?int $ecoPhpAnomalies = null;

    #[ORM\Column(name: 'eco_php_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes pour le langage PHP.'])]
    #[Assert\PositiveOrZero]
    private ?int $ecoPhpDetteMinute = null;

    #[ORM\Column(name: 'eco_php_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E pour le langage PHP.'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $ecoPhpRating = null;

    /* ── Python ──────────────────────────────────────────────────────── */

    #[ORM\Column(name: 'eco_python_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => 'Nombre d\'anomalies éco-conception (OPEN) pour le langage Python.'])]
    #[Assert\PositiveOrZero]
    private ?int $ecoPythonAnomalies = null;

    #[ORM\Column(name: 'eco_python_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes pour le langage Python.'])]
    #[Assert\PositiveOrZero]
    private ?int $ecoPythonDetteMinute = null;

    #[ORM\Column(name: 'eco_python_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E pour le langage Python.'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $ecoPythonRating = null;

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

    public function getEcoAnomalies(): ?int { return $this->ecoAnomalies; }
    public function setEcoAnomalies(?int $v): static { $this->ecoAnomalies = $v; return $this; }
    public function getEcoDetteMinute(): ?int { return $this->ecoDetteMinute; }
    public function setEcoDetteMinute(?int $v): static { $this->ecoDetteMinute = $v; return $this; }
    public function getEcoRating(): ?string { return $this->ecoRating; }
    public function setEcoRating(?string $v): static { $this->ecoRating = $v; return $this; }

    public function getEcoJavaAnomalies(): ?int { return $this->ecoJavaAnomalies; }
    public function setEcoJavaAnomalies(?int $v): static { $this->ecoJavaAnomalies = $v; return $this; }
    public function getEcoJavaDetteMinute(): ?int { return $this->ecoJavaDetteMinute; }
    public function setEcoJavaDetteMinute(?int $v): static { $this->ecoJavaDetteMinute = $v; return $this; }
    public function getEcoJavaRating(): ?string { return $this->ecoJavaRating; }
    public function setEcoJavaRating(?string $v): static { $this->ecoJavaRating = $v; return $this; }

    public function getEcoJavascriptAnomalies(): ?int { return $this->ecoJavascriptAnomalies; }
    public function setEcoJavascriptAnomalies(?int $v): static { $this->ecoJavascriptAnomalies = $v; return $this; }
    public function getEcoJavascriptDetteMinute(): ?int { return $this->ecoJavascriptDetteMinute; }
    public function setEcoJavascriptDetteMinute(?int $v): static { $this->ecoJavascriptDetteMinute = $v; return $this; }
    public function getEcoJavascriptRating(): ?string { return $this->ecoJavascriptRating; }
    public function setEcoJavascriptRating(?string $v): static { $this->ecoJavascriptRating = $v; return $this; }

    public function getEcoPhpAnomalies(): ?int { return $this->ecoPhpAnomalies; }
    public function setEcoPhpAnomalies(?int $v): static { $this->ecoPhpAnomalies = $v; return $this; }
    public function getEcoPhpDetteMinute(): ?int { return $this->ecoPhpDetteMinute; }
    public function setEcoPhpDetteMinute(?int $v): static { $this->ecoPhpDetteMinute = $v; return $this; }
    public function getEcoPhpRating(): ?string { return $this->ecoPhpRating; }
    public function setEcoPhpRating(?string $v): static { $this->ecoPhpRating = $v; return $this; }

    public function getEcoPythonAnomalies(): ?int { return $this->ecoPythonAnomalies; }
    public function setEcoPythonAnomalies(?int $v): static { $this->ecoPythonAnomalies = $v; return $this; }
    public function getEcoPythonDetteMinute(): ?int { return $this->ecoPythonDetteMinute; }
    public function setEcoPythonDetteMinute(?int $v): static { $this->ecoPythonDetteMinute = $v; return $this; }
    public function getEcoPythonRating(): ?string { return $this->ecoPythonRating; }
    public function setEcoPythonRating(?string $v): static { $this->ecoPythonRating = $v; return $this; }

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
