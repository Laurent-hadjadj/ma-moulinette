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

use App\Repository\AccessibilityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/* MODIF 26/10/2026 : entité pour l'indicateur Accessibilité, basé sur le tag
 * SonarQube `accessibility` (css/web/javascript/typescript — pas de facette
 * officielle WCAG, cf. mkDocs/docs/ma-moulinette/application/projet.md).
 * Snapshot courant, une ligne par maven_key. Note A-E (seuils SQALE
 * 5/10/20/50%, identiques au RCI et à l'Empreinte Écologique) et détail par
 * langage. Les règles accessibilité sont intégrées aux plugins de
 * langage standards de SonarQube.
 */

/**
 * [Description accessibility]
 */
#[ORM\Entity(repositoryClass: AccessibilityRepository::class)]
#[ORM\Table(name: "accessibility", schema: "ma_moulinette")]
class Accessibility
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

    #[ORM\Column(name: 'accessibility_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => "Nombre d'anomalies accessibilité (OPEN, tag SonarQube 'accessibility'), tous langages confondus."])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityAnomalies = null;

    #[ORM\Column(name: 'accessibility_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes (effortTotal SonarQube), tous langages confondus.'])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityDetteMinute = null;

    #[ORM\Column(name: 'accessibility_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E dérivée du ratio accessibility_dette_minute / (ncloc * 30), seuils SQALE 5/10/20/50%.'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $accessibilityRating = null;

    /* ── CSS ─────────────────────────────────────────────────────────── */

    #[ORM\Column(name: 'accessibility_css_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => 'Nombre d\'anomalies accessibilité (OPEN) pour le langage CSS.'])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityCssAnomalies = null;

    #[ORM\Column(name: 'accessibility_css_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes pour le langage CSS.'])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityCssDetteMinute = null;

    #[ORM\Column(name: 'accessibility_css_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E pour le langage CSS.'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $accessibilityCssRating = null;

    /* ── Web (HTML) ──────────────────────────────────────────────────── */

    #[ORM\Column(name: 'accessibility_web_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => 'Nombre d\'anomalies accessibilité (OPEN) pour le langage Web (HTML).'])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityWebAnomalies = null;

    #[ORM\Column(name: 'accessibility_web_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes pour le langage Web (HTML).'])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityWebDetteMinute = null;

    #[ORM\Column(name: 'accessibility_web_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E pour le langage Web (HTML).'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $accessibilityWebRating = null;

    /* ── JavaScript ──────────────────────────────────────────────────── */

    #[ORM\Column(name: 'accessibility_javascript_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => 'Nombre d\'anomalies accessibilité (OPEN) pour le langage JavaScript.'])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityJavascriptAnomalies = null;

    #[ORM\Column(name: 'accessibility_javascript_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes pour le langage JavaScript.'])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityJavascriptDetteMinute = null;

    #[ORM\Column(name: 'accessibility_javascript_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E pour le langage JavaScript.'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $accessibilityJavascriptRating = null;

    /* ── TypeScript ──────────────────────────────────────────────────── */

    #[ORM\Column(name: 'accessibility_typescript_anomalies', type: Types::INTEGER, nullable: true, options: ['comment' => 'Nombre d\'anomalies accessibilité (OPEN) pour le langage TypeScript.'])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityTypescriptAnomalies = null;

    #[ORM\Column(name: 'accessibility_typescript_dette_minute', type: Types::INTEGER, nullable: true, options: ['comment' => 'Dette estimée en minutes pour le langage TypeScript.'])]
    #[Assert\PositiveOrZero]
    private ?int $accessibilityTypescriptDetteMinute = null;

    #[ORM\Column(name: 'accessibility_typescript_rating', type: Types::STRING, length: 4, nullable: true, options: ['comment' => 'Note A-E pour le langage TypeScript.'])]
    #[Assert\Choice(choices: ['A', 'B', 'C', 'D', 'E'])]
    private ?string $accessibilityTypescriptRating = null;

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

    public function getAccessibilityAnomalies(): ?int
    {
        return $this->accessibilityAnomalies;
    }
    public function setAccessibilityAnomalies(?int $v): static
    {
        $this->accessibilityAnomalies = $v;
        return $this;
    }
    public function getAccessibilityDetteMinute(): ?int
    {
        return $this->accessibilityDetteMinute;
    }
    public function setAccessibilityDetteMinute(?int $v): static
    {
        $this->accessibilityDetteMinute = $v;
        return $this;
    }
    public function getAccessibilityRating(): ?string
    {
        return $this->accessibilityRating;
    }
    public function setAccessibilityRating(?string $v): static
    {
        $this->accessibilityRating = $v;
        return $this;
    }

    public function getAccessibilityCssAnomalies(): ?int
    {
        return $this->accessibilityCssAnomalies;
    }
    public function setAccessibilityCssAnomalies(?int $v): static
    {
        $this->accessibilityCssAnomalies = $v;
        return $this;
    }
    public function getAccessibilityCssDetteMinute(): ?int
    {
        return $this->accessibilityCssDetteMinute;
    }
    public function setAccessibilityCssDetteMinute(?int $v): static
    {
        $this->accessibilityCssDetteMinute = $v;
        return $this;
    }
    public function getAccessibilityCssRating(): ?string
    {
        return $this->accessibilityCssRating;
    }
    public function setAccessibilityCssRating(?string $v): static
    {
        $this->accessibilityCssRating = $v;
        return $this;
    }

    public function getAccessibilityWebAnomalies(): ?int
    {
        return $this->accessibilityWebAnomalies;
    }
    public function setAccessibilityWebAnomalies(?int $v): static
    {
        $this->accessibilityWebAnomalies = $v;
        return $this;
    }
    public function getAccessibilityWebDetteMinute(): ?int
    {
        return $this->accessibilityWebDetteMinute;
    }
    public function setAccessibilityWebDetteMinute(?int $v): static
    {
        $this->accessibilityWebDetteMinute = $v;
        return $this;
    }
    public function getAccessibilityWebRating(): ?string
    {
        return $this->accessibilityWebRating;
    }
    public function setAccessibilityWebRating(?string $v): static
    {
        $this->accessibilityWebRating = $v;
        return $this;
    }

    public function getAccessibilityJavascriptAnomalies(): ?int
    {
        return $this->accessibilityJavascriptAnomalies;
    }
    public function setAccessibilityJavascriptAnomalies(?int $v): static
    {
        $this->accessibilityJavascriptAnomalies = $v;
        return $this;
    }
    public function getAccessibilityJavascriptDetteMinute(): ?int
    {
        return $this->accessibilityJavascriptDetteMinute;
    }
    public function setAccessibilityJavascriptDetteMinute(?int $v): static
    {
        $this->accessibilityJavascriptDetteMinute = $v;
        return $this;
    }
    public function getAccessibilityJavascriptRating(): ?string
    {
        return $this->accessibilityJavascriptRating;
    }
    public function setAccessibilityJavascriptRating(?string $v): static
    {
        $this->accessibilityJavascriptRating = $v;
        return $this;
    }

    public function getAccessibilityTypescriptAnomalies(): ?int
    {
        return $this->accessibilityTypescriptAnomalies;
    }
    public function setAccessibilityTypescriptAnomalies(?int $v): static
    {
        $this->accessibilityTypescriptAnomalies = $v;
        return $this;
    }
    public function getAccessibilityTypescriptDetteMinute(): ?int
    {
        return $this->accessibilityTypescriptDetteMinute;
    }
    public function setAccessibilityTypescriptDetteMinute(?int $v): static
    {
        $this->accessibilityTypescriptDetteMinute = $v;
        return $this;
    }
    public function getAccessibilityTypescriptRating(): ?string
    {
        return $this->accessibilityTypescriptRating;
    }
    public function setAccessibilityTypescriptRating(?string $v): static
    {
        $this->accessibilityTypescriptRating = $v;
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
