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

use App\Repository\OwaspRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description Owasp]
 *
 * MODIF 2026-05-07 : remplacement Assert\NotNull → PositiveOrZero
 *  sur les 61 champs INTEGER (effortTotal + a1..a10 + 50 sous-niveaux Blocker/Critical/Major/Minor/Info).
 * MODIF 27/10/2026 : les 60 colonnes a1..a10/aN_blocker..minor + source sont remplacées
 *  par une seule colonne JSONB `categories` (classification désormais faite localement
 *  par OwaspRuleClassifierService, cf. BatchCollecteOwaspController et
 *  mkDocs/docs/ma-moulinette/application/owasp.md) + ajout de module_frontend/backend/autre
 *  (répartition par module des vulnérabilités OWASP, remplace l'ancienne répartition
 *  basée sur les hotspots).
 */
#[ORM\Entity(repositoryClass: OwaspRepository::class)]
#[ORM\Table(name: "owasp", schema: "ma_moulinette")]
class Owasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'clé unique pour la table Owasp']
    )]
    private int $id;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Référentiel version (2017, 2021)']
    )]
    private int $referentialOwasp = 2017;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé maven du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères."
    )]
    private string $mavenKey;

    #[ORM\Column(
        name: 'version',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Version du projet']
    )]
    #[Assert\NotBlank]
    private string $version;

    #[ORM\Column(
        name: 'date_version',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date de publication du projet"]
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateVersion;

    #[ORM\Column(
        name: 'effort_total',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => "Score total d'effort pour les questions de sécurité"]
    )]
    #[Assert\PositiveOrZero]
    private int $effortTotal;

    /**
     * Compteurs par catégorie A1..A10 (sparse, uniquement les catégories touchées) :
     * `{"a1": {"total":int,"blocker":int,"critical":int,"major":int,"minor":int,
     * "info":int,"source":"core|findsecbugs|mixed"}, ...}` — forme produite par
     * `OwaspRuleClassifierService::classify()`.
     *
     * @var array<string, array{total: int, blocker: int, critical: int, major: int, minor: int, info: int, source: string}>
     */
    #[ORM\Column(
        name: 'categories',
        type: Types::JSON,
        nullable: false,
        options: ['comment' => 'Compteurs par catégorie A1..A10 (JSON, sparse) : total/blocker/critical/major/minor/info/source.']
    )]
    #[Assert\NotNull]
    private array $categories = [];

    #[ORM\Column(
        name: 'module_frontend',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de vulnérabilités OWASP classées module frontend.']
    )]
    #[Assert\PositiveOrZero]
    private ?int $moduleFrontend = null;

    #[ORM\Column(
        name: 'module_backend',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de vulnérabilités OWASP classées module backend.']
    )]
    #[Assert\PositiveOrZero]
    private ?int $moduleBackend = null;

    #[ORM\Column(
        name: 'module_autre',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de vulnérabilités OWASP classées module autre (ou non classées).']
    )]
    #[Assert\PositiveOrZero]
    private ?int $moduleAutre = null;

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
        options: ['comment' => "Nom de l'utilisateur qui a réalisé la collecte."]
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
        options: ['comment' => 'Date d’enregistrement des données']
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

    /* [version du référentiel]*/
    public function getReferentialOwasp(): ?int
    {
        return $this->referentialOwasp;
    }

    public function setReferentialOwasp(int $referentialOwasp): self
    {
        $this->referentialOwasp = $referentialOwasp;
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

    public function getEffortTotal(): ?int
    {
        return $this->effortTotal;
    }

    public function setEffortTotal(int $effortTotal): static
    {
        $this->effortTotal = $effortTotal;

        return $this;
    }

    /**
     * @return array<string, array{total: int, blocker: int, critical: int, major: int, minor: int, info: int, source: string}>
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param array<string, array{total: int, blocker: int, critical: int, major: int, minor: int, info: int, source: string}> $categories
     */
    public function setCategories(array $categories): static
    {
        $this->categories = $categories;

        return $this;
    }

    public function getModuleFrontend(): ?int
    {
        return $this->moduleFrontend;
    }

    public function setModuleFrontend(?int $moduleFrontend): static
    {
        $this->moduleFrontend = $moduleFrontend;

        return $this;
    }

    public function getModuleBackend(): ?int
    {
        return $this->moduleBackend;
    }

    public function setModuleBackend(?int $moduleBackend): static
    {
        $this->moduleBackend = $moduleBackend;

        return $this;
    }

    public function getModuleAutre(): ?int
    {
        return $this->moduleAutre;
    }

    public function setModuleAutre(?int $moduleAutre): static
    {
        $this->moduleAutre = $moduleAutre;

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
}
