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
 */
#[ORM\Entity(repositoryClass: OwaspRepository::class)]
#[ORM\Table(
    name: 'owasp',
    schema: "ma_moulinette",
    options: ['comment' => 'Tables des menaces OWASP 2017 | 2021'])]
class Owasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Clé unique pour la table Owasp'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'referential_owasp',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Référentiel version (2017, 2021)'])]
    #[Assert\PositiveOrZero]
    private int $referentialOwasp = 2017;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private string $mavenKey;

    #[ORM\Column(
        name: 'version',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Version du projet'])]
    #[Assert\NotBlank]
    private string $version;

    #[ORM\Column(
        name: 'date_version',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date de publication du projet"])]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateVersion;

    #[ORM\Column(
        name: 'effort_total',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => "Score total d'effort pour les questions de sécurité"])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $effortTotal;

    // OWASP Top 10
    #[ORM\Column(
        name: 'a1',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A1'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a1;

    #[ORM\Column(
        name: 'a2',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A2'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a2;

    #[ORM\Column(
        name: 'a3',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A3'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a3;

    #[ORM\Column(
        name: 'a4',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A4'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a4;

    #[ORM\Column(
        name: 'a5',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A5'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a5;

    #[ORM\Column(
        name: 'a6',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A6'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a6;

    #[ORM\Column(
        name: 'a7',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A7'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a7;

    #[ORM\Column(
        name: 'a8',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A8'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a8;

    #[ORM\Column(
        name: 'a9',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A9'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a9;

    #[ORM\Column(
        name: 'a10',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A10'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a10;

    // A1
    #[ORM\Column(
        name: 'a1_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloquantes pour A1'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a1Blocker;

    #[ORM\Column(
        name: 'a1_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A1'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a1Critical;

    #[ORM\Column(
        name: 'a1_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A1'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a1Major;

    #[ORM\Column(
        name: 'a1_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A1'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a1Info;

    #[ORM\Column(
        name: 'a1_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A1'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a1Minor;

    // A2
    #[ORM\Column(
        name: 'a2_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A2'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a2Blocker;

    #[ORM\Column(
        name: 'a2_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A2'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a2Critical;

    #[ORM\Column(
        name: 'a2_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A2'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a2Major;

    #[ORM\Column(
        name: 'a2_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A2'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a2Info;

    #[ORM\Column(
        name: 'a2_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A2'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a2Minor;

    // A3
    #[ORM\Column(
        name: 'a3_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A3'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a3Blocker;

    #[ORM\Column(
        name: 'a3_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A3'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a3Critical;

    #[ORM\Column(
        name: 'a3_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A3'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a3Major;

    #[ORM\Column(
        name: 'a3_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A3'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a3Info;

    #[ORM\Column(
        name: 'a3_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A3'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a3Minor;

    // A4
    #[ORM\Column(
        name: 'a4_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A4'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a4Blocker;

    #[ORM\Column(
        name: 'a4_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A4'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a4Critical;

    #[ORM\Column(
        name: 'a4_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A4'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a4Major;

    #[ORM\Column(
        name: 'a4_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A4'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a4Info;

    #[ORM\Column(
        name: 'a4_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A4'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a4Minor;

    // A5
    #[ORM\Column(
        name: 'a5_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A5'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a5Blocker;

    #[ORM\Column(
        name: 'a5_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A5'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a5Critical;

    #[ORM\Column(
        name: 'a5_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A5'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a5Major;

    #[ORM\Column(
        name: 'a5_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A5'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a5Info;

    #[ORM\Column(
        name: 'a5_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A5'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a5Minor;

    // A6
    #[ORM\Column(
        name: 'a6_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A6'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a6Blocker;

    #[ORM\Column(
        name: 'a6_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A6'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a6Critical;

    #[ORM\Column(
        name: 'a6_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A6'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a6Major;

    #[ORM\Column(
        name: 'a6_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A6'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a6Info;

    #[ORM\Column(
        name: 'a6_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A6'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a6Minor;

    // A7
    #[ORM\Column(
        name: 'a7_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A7'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a7Blocker;

    #[ORM\Column(
        name: 'a7_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A7'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a7Critical;

    #[ORM\Column(
        name: 'a7_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A7'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a7Major;

    #[ORM\Column(
        name: 'a7_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A7'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a7Info;

    #[ORM\Column(
        name: 'a7_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A7'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a7Minor;

    // A8
    #[ORM\Column(
        name: 'a8_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A8'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a8Blocker;

    #[ORM\Column(
        name: 'a8_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A8'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a8Critical;

    #[ORM\Column(
        name: 'a8_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A8'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a8Major;

    #[ORM\Column(
        name: 'a8_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A8'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a8Info;

    #[ORM\Column(
        name: 'a8_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A8'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a8Minor;

    // A9
    #[ORM\Column(
        name: 'a9_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A9'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a9Blocker;

    #[ORM\Column(
        name: 'a9_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A9'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a9Critical;

    #[ORM\Column(
        name: 'a9_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A9'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a9Major;

    #[ORM\Column(
        name: 'a9_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A9'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a9Info;

    #[ORM\Column(
        name: 'a9_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A9'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a9Minor;

    // A10
    #[ORM\Column(
        name: 'a10_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A10'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a10Blocker;

    #[ORM\Column(name: 'a10_critical', type: Types::INTEGER, nullable: false, options: ['comment' => 'Nombre de critiques pour A10'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a10Critical;

    #[ORM\Column(
        name: 'a10_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A10'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a10Major;

    #[ORM\Column(
        name: 'a10_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A10'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a10Info;

    #[ORM\Column(
        name: 'a10_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A10'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $a10Minor;

    // Collecte et utilisateur
    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : [COLLECTE] | [TRAITEMENT MANUEL] | [TRAITEMENT AUTOMATIQUE]'])]
    #[Assert\Length(max: 32, maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères.")]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => "Nom de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Length(
        max: 320,
        maxMessage: "Le nom de l'utilisateur ne peut pas dépasser 320 caractères.")]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement des données'])]
    #[Assert\NotNull]
    private \DateTimeInterface $dateEnregistrement;

    public function __construct()
    {
        $this->dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
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

    /* [version du référentiel]*/
    public function getReferentialOwasp(): ?string
    {
        return $this->referentialOwasp;
    }

    public function setReferentialOwasp(string $referentialOwasp): self
    {
        $this->referentialOwasp = $referentialOwasp;
        return $this;
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getDateVersion(): ?\DateTimeImmutable
    {
        return $this->dateVersion;
    }

    public function setDateVersion(\DateTimeImmutable $dateVersion): self
    {
        $this->dateVersion = $dateVersion;

        return $this;
    }

    public function getEffortTotal(): ?int
    {
        return $this->effortTotal;
    }

    public function setEffortTotal(int $effortTotal): self
    {
        $this->effortTotal = $effortTotal;

        return $this;
    }

    public function getA1(): ?int
    {
        return $this->a1;
    }

    public function setA1(int $a1): self
    {
        $this->a1 = $a1;

        return $this;
    }

    public function getA2(): ?int
    {
        return $this->a2;
    }

    public function setA2(int $a2): self
    {
        $this->a2 = $a2;

        return $this;
    }

    public function getA3(): ?int
    {
        return $this->a3;
    }

    public function setA3(int $a3): self
    {
        $this->a3 = $a3;

        return $this;
    }

    public function getA4(): ?int
    {
        return $this->a4;
    }

    public function setA4(int $a4): self
    {
        $this->a4 = $a4;

        return $this;
    }

    public function getA5(): ?int
    {
        return $this->a5;
    }

    public function setA5(int $a5): self
    {
        $this->a5 = $a5;

        return $this;
    }

    public function getA6(): ?int
    {
        return $this->a6;
    }

    public function setA6(int $a6): self
    {
        $this->a6 = $a6;

        return $this;
    }

    public function getA7(): ?int
    {
        return $this->a7;
    }

    public function setA7(int $a7): self
    {
        $this->a7 = $a7;

        return $this;
    }

    public function getA8(): ?int
    {
        return $this->a8;
    }

    public function setA8(int $a8): self
    {
        $this->a8 = $a8;

        return $this;
    }

    public function getA9(): ?int
    {
        return $this->a9;
    }

    public function setA9(int $a9): self
    {
        $this->a9 = $a9;

        return $this;
    }

    public function getA10(): ?int
    {
        return $this->a10;
    }

    public function setA10(int $a10): self
    {
        $this->a10 = $a10;

        return $this;
    }

    public function getA1Blocker(): ?int
    {
        return $this->a1Blocker;
    }

    public function setA1Blocker(int $a1Blocker): self
    {
        $this->a1Blocker = $a1Blocker;

        return $this;
    }

    public function getA1Critical(): ?int
    {
        return $this->a1Critical;
    }

    public function setA1Critical(int $a1Critical): self
    {
        $this->a1Critical = $a1Critical;

        return $this;
    }

    public function getA1Major(): ?int
    {
        return $this->a1Major;
    }

    public function setA1Major(int $a1Major): self
    {
        $this->a1Major = $a1Major;

        return $this;
    }

    public function getA1Info(): ?int
    {
        return $this->a1Info;
    }

    public function setA1Info(int $a1Info): self
    {
        $this->a1Info = $a1Info;

        return $this;
    }

    public function getA1Minor(): ?int
    {
        return $this->a1Minor;
    }

    public function setA1Minor(int $a1Minor): self
    {
        $this->a1Minor = $a1Minor;

        return $this;
    }

    public function getA2Blocker(): ?int
    {
        return $this->a2Blocker;
    }

    public function setA2Blocker(int $a2Blocker): self
    {
        $this->a2Blocker = $a2Blocker;

        return $this;
    }

    public function getA2Critical(): ?int
    {
        return $this->a2Critical;
    }

    public function setA2Critical(int $a2Critical): self
    {
        $this->a2Critical = $a2Critical;

        return $this;
    }

    public function getA2Major(): ?int
    {
        return $this->a2Major;
    }

    public function setA2Major(int $a2Major): self
    {
        $this->a2Major = $a2Major;

        return $this;
    }

    public function getA2Info(): ?int
    {
        return $this->a2Info;
    }

    public function setA2Info(int $a2Info): self
    {
        $this->a2Info = $a2Info;

        return $this;
    }

    public function getA2Minor(): ?int
    {
        return $this->a2Minor;
    }

    public function setA2Minor(int $a2Minor): self
    {
        $this->a2Minor = $a2Minor;

        return $this;
    }

    public function getA3Blocker(): ?int
    {
        return $this->a3Blocker;
    }

    public function setA3Blocker(int $a3Blocker): self
    {
        $this->a3Blocker = $a3Blocker;

        return $this;
    }

    public function getA3Critical(): ?int
    {
        return $this->a3Critical;
    }

    public function setA3Critical(int $a3Critical): self
    {
        $this->a3Critical = $a3Critical;

        return $this;
    }

    public function getA3Major(): ?int
    {
        return $this->a3Major;
    }

    public function setA3Major(int $a3Major): self
    {
        $this->a3Major = $a3Major;

        return $this;
    }

    public function getA3Info(): ?int
    {
        return $this->a3Info;
    }

    public function setA3Info(int $a3Info): self
    {
        $this->a3Info = $a3Info;

        return $this;
    }

    public function getA3Minor(): ?int
    {
        return $this->a3Minor;
    }

    public function setA3Minor(int $a3Minor): self
    {
        $this->a3Minor = $a3Minor;

        return $this;
    }

    public function getA4Blocker(): ?int
    {
        return $this->a4Blocker;
    }

    public function setA4Blocker(int $a4Blocker): self
    {
        $this->a4Blocker = $a4Blocker;

        return $this;
    }

    public function getA4Critical(): ?int
    {
        return $this->a4Critical;
    }

    public function setA4Critical(int $a4Critical): self
    {
        $this->a4Critical = $a4Critical;

        return $this;
    }

    public function getA4Major(): ?int
    {
        return $this->a4Major;
    }

    public function setA4Major(int $a4Major): self
    {
        $this->a4Major = $a4Major;

        return $this;
    }

    public function getA4Info(): ?int
    {
        return $this->a4Info;
    }

    public function setA4Info(int $a4Info): self
    {
        $this->a4Info = $a4Info;

        return $this;
    }

    public function getA4Minor(): ?int
    {
        return $this->a4Minor;
    }

    public function setA4Minor(int $a4Minor): self
    {
        $this->a4Minor = $a4Minor;

        return $this;
    }

    public function getA5Blocker(): ?int
    {
        return $this->a5Blocker;
    }

    public function setA5Blocker(int $a5Blocker): self
    {
        $this->a5Blocker = $a5Blocker;

        return $this;
    }

    public function getA5Critical(): ?int
    {
        return $this->a5Critical;
    }

    public function setA5Critical(int $a5Critical): self
    {
        $this->a5Critical = $a5Critical;

        return $this;
    }

    public function getA5Major(): ?int
    {
        return $this->a5Major;
    }

    public function setA5Major(int $a5Major): self
    {
        $this->a5Major = $a5Major;

        return $this;
    }

    public function getA5Info(): ?int
    {
        return $this->a5Info;
    }

    public function setA5Info(int $a5Info): self
    {
        $this->a5Info = $a5Info;

        return $this;
    }

    public function getA5Minor(): ?int
    {
        return $this->a5Minor;
    }

    public function setA5Minor(int $a5Minor): self
    {
        $this->a5Minor = $a5Minor;

        return $this;
    }

    public function getA6Blocker(): ?int
    {
        return $this->a6Blocker;
    }

    public function setA6Blocker(int $a6Blocker): self
    {
        $this->a6Blocker = $a6Blocker;

        return $this;
    }

    public function getA6Critical(): ?int
    {
        return $this->a6Critical;
    }

    public function setA6Critical(int $a6Critical): self
    {
        $this->a6Critical = $a6Critical;

        return $this;
    }

    public function getA6Major(): ?int
    {
        return $this->a6Major;
    }

    public function setA6Major(int $a6Major): self
    {
        $this->a6Major = $a6Major;

        return $this;
    }

    public function getA6Info(): ?int
    {
        return $this->a6Info;
    }

    public function setA6Info(int $a6Info): self
    {
        $this->a6Info = $a6Info;

        return $this;
    }

    public function getA6Minor(): ?int
    {
        return $this->a6Minor;
    }

    public function setA6Minor(int $a6Minor): self
    {
        $this->a6Minor = $a6Minor;

        return $this;
    }

    public function getA7Blocker(): ?int
    {
        return $this->a7Blocker;
    }

    public function setA7Blocker(int $a7Blocker): self
    {
        $this->a7Blocker = $a7Blocker;

        return $this;
    }

    public function getA7Critical(): ?int
    {
        return $this->a7Critical;
    }

    public function setA7Critical(int $a7Critical): self
    {
        $this->a7Critical = $a7Critical;

        return $this;
    }

    public function getA7Major(): ?int
    {
        return $this->a7Major;
    }

    public function setA7Major(int $a7Major): self
    {
        $this->a7Major = $a7Major;

        return $this;
    }

    public function getA7Info(): ?int
    {
        return $this->a7Info;
    }

    public function setA7Info(int $a7Info): self
    {
        $this->a7Info = $a7Info;

        return $this;
    }

    public function getA7Minor(): ?int
    {
        return $this->a7Minor;
    }

    public function setA7Minor(int $a7Minor): self
    {
        $this->a7Minor = $a7Minor;

        return $this;
    }

    public function getA8Blocker(): ?int
    {
        return $this->a8Blocker;
    }

    public function setA8Blocker(int $a8Blocker): self
    {
        $this->a8Blocker = $a8Blocker;

        return $this;
    }

    public function getA8Critical(): ?int
    {
        return $this->a8Critical;
    }

    public function setA8Critical(int $a8Critical): self
    {
        $this->a8Critical = $a8Critical;

        return $this;
    }

    public function getA8Major(): ?int
    {
        return $this->a8Major;
    }

    public function setA8Major(int $a8Major): self
    {
        $this->a8Major = $a8Major;

        return $this;
    }

    public function getA8Info(): ?int
    {
        return $this->a8Info;
    }

    public function setA8Info(int $a8Info): self
    {
        $this->a8Info = $a8Info;

        return $this;
    }

    public function getA8Minor(): ?int
    {
        return $this->a8Minor;
    }

    public function setA8Minor(int $a8Minor): self
    {
        $this->a8Minor = $a8Minor;

        return $this;
    }

    public function getA9Blocker(): ?int
    {
        return $this->a9Blocker;
    }

    public function setA9Blocker(int $a9Blocker): self
    {
        $this->a9Blocker = $a9Blocker;

        return $this;
    }

    public function getA9Critical(): ?int
    {
        return $this->a9Critical;
    }

    public function setA9Critical(int $a9Critical): self
    {
        $this->a9Critical = $a9Critical;

        return $this;
    }

    public function getA9Major(): ?int
    {
        return $this->a9Major;
    }

    public function setA9Major(int $a9Major): self
    {
        $this->a9Major = $a9Major;

        return $this;
    }

    public function getA9Info(): ?int
    {
        return $this->a9Info;
    }

    public function setA9Info(int $a9Info): self
    {
        $this->a9Info = $a9Info;

        return $this;
    }

    public function getA9Minor(): ?int
    {
        return $this->a9Minor;
    }

    public function setA9Minor(int $a9Minor): self
    {
        $this->a9Minor = $a9Minor;

        return $this;
    }

    public function getA10Blocker(): ?int
    {
        return $this->a10Blocker;
    }

    public function setA10Blocker(int $a10Blocker): self
    {
        $this->a10Blocker = $a10Blocker;

        return $this;
    }

    public function getA10Critical(): ?int
    {
        return $this->a10Critical;
    }

    public function setA10Critical(int $a10Critical): self
    {
        $this->a10Critical = $a10Critical;

        return $this;
    }

    public function getA10Major(): ?int
    {
        return $this->a10Major;
    }

    public function setA10Major(int $a10Major): self
    {
        $this->a10Major = $a10Major;

        return $this;
    }

    public function getA10Info(): ?int
    {
        return $this->a10Info;
    }

    public function setA10Info(int $a10Info): self
    {
        $this->a10Info = $a10Info;

        return $this;
    }

    public function getA10Minor(): ?int
    {
        return $this->a10Minor;
    }

    public function setA10Minor(int $a10Minor): self
    {
        $this->a10Minor = $a10Minor;

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

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }

    public function setModeCollecte(?string $modeCollecte): self
    {
        $this->modeCollecte = $modeCollecte;

        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }

    public function setUtilisateurCollecte(?string $utilisateurCollecte): self
    {
        $this->utilisateurCollecte = $utilisateurCollecte;

        return $this;
    }

}
