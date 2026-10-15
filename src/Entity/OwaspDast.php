<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\OwaspDastRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description OwaspDast]
 * MODIF 2026-10-13 : agrégation OWASP Top 10 (2017/2021) issue d'un scan
 * DAST ZAP — complément de la table `owasp` (SAST SonarQube), même forme
 * (a1..a10 + répartition par niveau), mais échelle de risque ZAP
 * (Informational/Low/Medium/High) au lieu de l'échelle SonarQube
 * (Bloquant/Critique/Majeur/Mineur/Info). Une ligne par (scan, référentiel) —
 * contrairement à `owasp` qui écrase à chaque collecte, ici chaque scan
 * ingéré ajoute ses lignes (historisation native par scan, cf. ZapScan).
 */
#[ORM\Entity(repositoryClass: OwaspDastRepository::class)]
#[ORM\Table(name: 'owasp_dast', schema: 'ma_moulinette')]
class OwaspDast
{
    public const REFERENTIAL_2017 = 2017;
    public const REFERENTIAL_2021 = 2021;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\ManyToOne(targetEntity: ZapScan::class)]
    #[ORM\JoinColumn(
        name: 'scan_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ZapScan $scan;

    #[ORM\Column(
        name: 'referential_owasp',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\Choice(choices: [self::REFERENTIAL_2017, self::REFERENTIAL_2021])]
    private int $referentialOwasp;

    #[ORM\Column(name: 'maven_key', type: Types::STRING, length: 511, nullable: false)]
    #[Assert\NotBlank]
    private string $mavenKey;

    #[ORM\Column(name: 'project_version', type: Types::STRING, length: 64, nullable: false)]
    #[Assert\NotBlank]
    private string $projectVersion;

    #[ORM\Column(name: 'a1', type: Types::INTEGER, nullable: false)] private int $a1 = 0;
    #[ORM\Column(name: 'a2', type: Types::INTEGER, nullable: false)] private int $a2 = 0;
    #[ORM\Column(name: 'a3', type: Types::INTEGER, nullable: false)] private int $a3 = 0;
    #[ORM\Column(name: 'a4', type: Types::INTEGER, nullable: false)] private int $a4 = 0;
    #[ORM\Column(name: 'a5', type: Types::INTEGER, nullable: false)] private int $a5 = 0;
    #[ORM\Column(name: 'a6', type: Types::INTEGER, nullable: false)] private int $a6 = 0;
    #[ORM\Column(name: 'a7', type: Types::INTEGER, nullable: false)] private int $a7 = 0;
    #[ORM\Column(name: 'a8', type: Types::INTEGER, nullable: false)] private int $a8 = 0;
    #[ORM\Column(name: 'a9', type: Types::INTEGER, nullable: false)] private int $a9 = 0;
    #[ORM\Column(name: 'a10', type: Types::INTEGER, nullable: false)] private int $a10 = 0;

    #[ORM\Column(name: 'a1_informational', type: Types::INTEGER, nullable: false)] private int $a1Informational = 0;
    #[ORM\Column(name: 'a1_low', type: Types::INTEGER, nullable: false)] private int $a1Low = 0;
    #[ORM\Column(name: 'a1_medium', type: Types::INTEGER, nullable: false)] private int $a1Medium = 0;
    #[ORM\Column(name: 'a1_high', type: Types::INTEGER, nullable: false)] private int $a1High = 0;

    #[ORM\Column(name: 'a2_informational', type: Types::INTEGER, nullable: false)] private int $a2Informational = 0;
    #[ORM\Column(name: 'a2_low', type: Types::INTEGER, nullable: false)] private int $a2Low = 0;
    #[ORM\Column(name: 'a2_medium', type: Types::INTEGER, nullable: false)] private int $a2Medium = 0;
    #[ORM\Column(name: 'a2_high', type: Types::INTEGER, nullable: false)] private int $a2High = 0;

    #[ORM\Column(name: 'a3_informational', type: Types::INTEGER, nullable: false)] private int $a3Informational = 0;
    #[ORM\Column(name: 'a3_low', type: Types::INTEGER, nullable: false)] private int $a3Low = 0;
    #[ORM\Column(name: 'a3_medium', type: Types::INTEGER, nullable: false)] private int $a3Medium = 0;
    #[ORM\Column(name: 'a3_high', type: Types::INTEGER, nullable: false)] private int $a3High = 0;

    #[ORM\Column(name: 'a4_informational', type: Types::INTEGER, nullable: false)] private int $a4Informational = 0;
    #[ORM\Column(name: 'a4_low', type: Types::INTEGER, nullable: false)] private int $a4Low = 0;
    #[ORM\Column(name: 'a4_medium', type: Types::INTEGER, nullable: false)] private int $a4Medium = 0;
    #[ORM\Column(name: 'a4_high', type: Types::INTEGER, nullable: false)] private int $a4High = 0;

    #[ORM\Column(name: 'a5_informational', type: Types::INTEGER, nullable: false)] private int $a5Informational = 0;
    #[ORM\Column(name: 'a5_low', type: Types::INTEGER, nullable: false)] private int $a5Low = 0;
    #[ORM\Column(name: 'a5_medium', type: Types::INTEGER, nullable: false)] private int $a5Medium = 0;
    #[ORM\Column(name: 'a5_high', type: Types::INTEGER, nullable: false)] private int $a5High = 0;

    #[ORM\Column(name: 'a6_informational', type: Types::INTEGER, nullable: false)] private int $a6Informational = 0;
    #[ORM\Column(name: 'a6_low', type: Types::INTEGER, nullable: false)] private int $a6Low = 0;
    #[ORM\Column(name: 'a6_medium', type: Types::INTEGER, nullable: false)] private int $a6Medium = 0;
    #[ORM\Column(name: 'a6_high', type: Types::INTEGER, nullable: false)] private int $a6High = 0;

    #[ORM\Column(name: 'a7_informational', type: Types::INTEGER, nullable: false)] private int $a7Informational = 0;
    #[ORM\Column(name: 'a7_low', type: Types::INTEGER, nullable: false)] private int $a7Low = 0;
    #[ORM\Column(name: 'a7_medium', type: Types::INTEGER, nullable: false)] private int $a7Medium = 0;
    #[ORM\Column(name: 'a7_high', type: Types::INTEGER, nullable: false)] private int $a7High = 0;

    #[ORM\Column(name: 'a8_informational', type: Types::INTEGER, nullable: false)] private int $a8Informational = 0;
    #[ORM\Column(name: 'a8_low', type: Types::INTEGER, nullable: false)] private int $a8Low = 0;
    #[ORM\Column(name: 'a8_medium', type: Types::INTEGER, nullable: false)] private int $a8Medium = 0;
    #[ORM\Column(name: 'a8_high', type: Types::INTEGER, nullable: false)] private int $a8High = 0;

    #[ORM\Column(name: 'a9_informational', type: Types::INTEGER, nullable: false)] private int $a9Informational = 0;
    #[ORM\Column(name: 'a9_low', type: Types::INTEGER, nullable: false)] private int $a9Low = 0;
    #[ORM\Column(name: 'a9_medium', type: Types::INTEGER, nullable: false)] private int $a9Medium = 0;
    #[ORM\Column(name: 'a9_high', type: Types::INTEGER, nullable: false)] private int $a9High = 0;

    #[ORM\Column(name: 'a10_informational', type: Types::INTEGER, nullable: false)] private int $a10Informational = 0;
    #[ORM\Column(name: 'a10_low', type: Types::INTEGER, nullable: false)] private int $a10Low = 0;
    #[ORM\Column(name: 'a10_medium', type: Types::INTEGER, nullable: false)] private int $a10Medium = 0;
    #[ORM\Column(name: 'a10_high', type: Types::INTEGER, nullable: false)] private int $a10High = 0;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false
    )]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->dateEnregistrement = new \DateTimeImmutable();
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getScan(): ZapScan
    {
        return $this->scan;
    }
    public function setScan(ZapScan $s): self
    {
        $this->scan = $s;
        return $this;
    }

    public function getReferentialOwasp(): int
    {
        return $this->referentialOwasp;
    }
    public function setReferentialOwasp(int $v): self
    {
        $this->referentialOwasp = $v;
        return $this;
    }

    public function getMavenKey(): string
    {
        return $this->mavenKey;
    }
    public function setMavenKey(string $v): self
    {
        $this->mavenKey = $v;
        return $this;
    }

    public function getProjectVersion(): string
    {
        return $this->projectVersion;
    }
    public function setProjectVersion(string $v): self
    {
        $this->projectVersion = $v;
        return $this;
    }

    /**
     * [Description for getCategoryCount]
     * Lecture générique du compteur d'une catégorie (1-10).
     *
     * @param int $category
     *
     * @return int
     *
     * Created at: 13/10/2026 21:12:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCategoryCount(int $category): int
    {
        return match ($category) {
            1 => $this->a1, 2 => $this->a2, 3 => $this->a3, 4 => $this->a4, 5 => $this->a5,
            6 => $this->a6, 7 => $this->a7, 8 => $this->a8, 9 => $this->a9, 10 => $this->a10,
            default => throw new \InvalidArgumentException("Catégorie OWASP invalide : {$category}."),
        };
    }

    /**
     * [Description for incrementCategory]
     * Écriture générique : incrémente le compteur global de la catégorie
     * ainsi que son compteur par niveau de risque ZAP.
     *
     * Volontairement écrit en 40 branches explicites (pas d'accès dynamique
     * `$this->{$property}`) : PHPStan niveau 6 ne peut pas vérifier l'existence
     * d'une propriété nommée dynamiquement, et ce projet n'utilise ni baseline
     * ni d'annotation d'exclusion PHPStan (cf. AGENTS.md).
     *
     * @param int $category 1 à 10
     * @param string $riskLabel 'informational' | 'low' | 'medium' | 'high'
     *
     * @return void
     * Created at: 13/10/2026 21:11:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function incrementCategory(int $category, string $riskLabel): void
    {
        $key = sprintf('%d:%s', $category, $riskLabel);
        match ($key) {
            '1:informational' => [$this->a1++, $this->a1Informational++],
            '1:low'           => [$this->a1++, $this->a1Low++],
            '1:medium'        => [$this->a1++, $this->a1Medium++],
            '1:high'          => [$this->a1++, $this->a1High++],

            '2:informational' => [$this->a2++, $this->a2Informational++],
            '2:low'           => [$this->a2++, $this->a2Low++],
            '2:medium'        => [$this->a2++, $this->a2Medium++],
            '2:high'          => [$this->a2++, $this->a2High++],

            '3:informational' => [$this->a3++, $this->a3Informational++],
            '3:low'           => [$this->a3++, $this->a3Low++],
            '3:medium'        => [$this->a3++, $this->a3Medium++],
            '3:high'          => [$this->a3++, $this->a3High++],

            '4:informational' => [$this->a4++, $this->a4Informational++],
            '4:low'           => [$this->a4++, $this->a4Low++],
            '4:medium'        => [$this->a4++, $this->a4Medium++],
            '4:high'          => [$this->a4++, $this->a4High++],

            '5:informational' => [$this->a5++, $this->a5Informational++],
            '5:low'           => [$this->a5++, $this->a5Low++],
            '5:medium'        => [$this->a5++, $this->a5Medium++],
            '5:high'          => [$this->a5++, $this->a5High++],

            '6:informational' => [$this->a6++, $this->a6Informational++],
            '6:low'           => [$this->a6++, $this->a6Low++],
            '6:medium'        => [$this->a6++, $this->a6Medium++],
            '6:high'          => [$this->a6++, $this->a6High++],

            '7:informational' => [$this->a7++, $this->a7Informational++],
            '7:low'           => [$this->a7++, $this->a7Low++],
            '7:medium'        => [$this->a7++, $this->a7Medium++],
            '7:high'          => [$this->a7++, $this->a7High++],

            '8:informational' => [$this->a8++, $this->a8Informational++],
            '8:low'           => [$this->a8++, $this->a8Low++],
            '8:medium'        => [$this->a8++, $this->a8Medium++],
            '8:high'          => [$this->a8++, $this->a8High++],

            '9:informational' => [$this->a9++, $this->a9Informational++],
            '9:low'           => [$this->a9++, $this->a9Low++],
            '9:medium'        => [$this->a9++, $this->a9Medium++],
            '9:high'          => [$this->a9++, $this->a9High++],

            '10:informational' => [$this->a10++, $this->a10Informational++],
            '10:low'           => [$this->a10++, $this->a10Low++],
            '10:medium'        => [$this->a10++, $this->a10Medium++],
            '10:high'          => [$this->a10++, $this->a10High++],

            default => throw new \InvalidArgumentException("Catégorie/niveau de risque OWASP DAST invalide : {$key}."),
        };
    }

    public function getDateEnregistrement(): \DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }
    public function setDateEnregistrement(\DateTimeImmutable $d): self
    {
        $this->dateEnregistrement = $d;
        return $this;
    }
}
