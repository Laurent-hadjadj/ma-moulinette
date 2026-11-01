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

use App\Repository\OwaspDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description OwaspDetails]
 *
 * Détail individuel de chaque vulnérabilité OWASP classée (table snapshot,
 * réécrite en entier à chaque collecte comme `owasp`/`hotspot_details`) —
 * une ligne par (maven_key, referential_owasp, issue). Alimentée par
 * OwaspRuleClassifierService::classify() (clé `details`), construite à
 * partir du même appel paginé /api/issues/search que la table `owasp`
 * agrégée, sans appel SonarQube supplémentaire.
 *
 * Created at: 28/10/2026 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
#[ORM\Entity(repositoryClass: OwaspDetailsRepository::class)]
#[ORM\Table(name: "owasp_details", schema: "ma_moulinette")]
class OwaspDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour la table']
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
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères."
    )]
    private string $mavenKey;

    #[ORM\Column(
        name: 'referential_owasp',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Référentiel version (2017, 2021)']
    )]
    private int $referentialOwasp = 2017;

    #[ORM\Column(
        name: 'version',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Version du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "La version ne doit pas dépasser 32 caractères."
    )]
    private string $version;

    #[ORM\Column(
        name: 'date_version',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de publication du projet']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateVersion;

    #[ORM\Column(
        name: 'rule_key',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Règle SonarQube ou FindSecBugs']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "La clé de la règle ne doit pas dépasser 128 caractères."
    )]
    private string $ruleKey;

    #[ORM\Column(
        name: 'categories',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Catégories A1..A10 du référentiel actif concernées par cette règle, concaténées (ex. "A4, A5")']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 64,
        maxMessage: "La liste des catégories ne doit pas dépasser 64 caractères."
    )]
    private string $categories;

    #[ORM\Column(
        name: 'severity',
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => 'Sévérité SonarQube de la vulnérabilité (BLOCKER, CRITICAL, MAJOR, MINOR, INFO)']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 8,
        maxMessage: "La sévérité ne doit pas dépasser 8 caractères."
    )]
    private string $severity;

    #[ORM\Column(
        name: 'source',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Origine de la règle : core (SonarSource) ou findsecbugs']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 16,
        maxMessage: "La source ne doit pas dépasser 16 caractères."
    )]
    private string $source;

    #[ORM\Column(
        name: 'module',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Module du fichier concerné : frontend, backend, autre ou inconnu']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le module ne doit pas dépasser 16 caractères."
    )]
    private string $module;

    #[ORM\Column(
        name: 'file_path',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Chemin du fichier concerné (clé Maven du projet retirée)']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le chemin du fichier ne doit pas dépasser 255 caractères."
    )]
    private string $filePath;

    #[ORM\Column(
        name: 'line',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Ligne du fichier où se situe la vulnérabilité (absente pour certaines règles au niveau fichier/architecture)']
    )]
    #[Assert\PositiveOrZero]
    private ?int $line = null;

    #[ORM\Column(
        name: 'message',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Message descriptif de la vulnérabilité']
    )]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le message ne doit pas dépasser 255 caractères."
    )]
    private string $message = '';

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : [COLLECTE] | [TRAITEMENT MANUEL] | [TRAITEMENT AUTOMATIQUE]']
    )]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères."
    )]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte."]
    )]
    #[Assert\Length(
        max: 320,
        maxMessage: "Le nom de l'utilisateur ne peut pas dépasser 320 caractères."
    )]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du détail de la vulnérabilité']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getReferentialOwasp(): ?int
    {
        return $this->referentialOwasp;
    }

    public function setReferentialOwasp(int $referentialOwasp): static
    {
        $this->referentialOwasp = $referentialOwasp;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getDateVersion(): ?\DateTimeImmutable
    {
        return $this->dateVersion;
    }

    public function setDateVersion(\DateTimeImmutable $dateVersion): static
    {
        $this->dateVersion = $dateVersion;

        return $this;
    }

    public function getRuleKey(): ?string
    {
        return $this->ruleKey;
    }

    public function setRuleKey(string $ruleKey): static
    {
        $this->ruleKey = $ruleKey;

        return $this;
    }

    public function getCategories(): ?string
    {
        return $this->categories;
    }

    public function setCategories(string $categories): static
    {
        $this->categories = $categories;

        return $this;
    }

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): static
    {
        $this->severity = $severity;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function setModule(string $module): static
    {
        $this->module = $module;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function setLine(?int $line): static
    {
        $this->line = $line;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

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
