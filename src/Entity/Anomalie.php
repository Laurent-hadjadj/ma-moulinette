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

use App\Repository\AnomalieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnomalieRepository::class)]
#[ORM\Table(name: "anomalie", schema: "ma_moulinette")]
class Anomalie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique de l’anomalie']
    )]
    private $id;

    #[ORM\Column(
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
    private $mavenKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du projet associé à l’anomalie']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom du projet ne doit pas dépasser 128 caractères."
    )]
    private $projectName;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total d’anomalies']
    )]
    #[Assert\NotNull]
    private $anomalieTotal;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes totales de la dette technique']
    )]
    #[Assert\NotNull]
    private $detteMinute;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes de la dette de fiabilité']
    )]
    #[Assert\NotNull]
    private $detteReliabilityMinute;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes de la dette de vulnérabilité']
    )]
    #[Assert\NotNull]
    private $detteVulnerabilityMinute;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes de la dette de mauvaises pratiques']
    )]
    #[Assert\NotNull]
    private $detteCodeSmellMinute;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette de fiabilité']
    )]
    #[Assert\NotBlank]
    private $detteReliability;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette de vulnérabilité']
    )]
    #[Assert\NotBlank]
    private $detteVulnerability;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette générale']
    )]
    #[Assert\NotBlank]
    private $dette;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette des mauvaises pratiques']
    )]
    #[Assert\NotBlank]
    private $detteCodeSmell;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes liés au frontend']
    )]
    #[Assert\NotNull]
    private $frontend;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes liés au backend']
    )]
    #[Assert\NotNull]
    private $backend;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Autres problèmes techniques']
    )]
    #[Assert\NotNull]
    private $autre;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes bloquants']
    )]
    #[Assert\NotNull]
    private $blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes critiques']
    )]
    #[Assert\NotNull]
    private $critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes majeurs']
    )]
    #[Assert\NotNull]
    private $major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Informations sur les problèmes mineurs']
    )]
    #[Assert\NotNull]
    private $info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes mineurs']
    )]
    #[Assert\NotNull]
    private $minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de bugs']
    )]
    #[Assert\NotNull]
    private $bug;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de vulnérabilités']
    )]
    #[Assert\NotNull]
    private $vulnerability;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total d’mauvaises pratiques']
    )]
    #[Assert\NotNull]
    private $codeSmell;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement de l’anomalie']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:47:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * [Description for getMavenKey]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:47:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    /**
     * [Description for setMavenKey]
     *
     * @param string $mavenKey
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    /**
     * [Description for getProjectName]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:47:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    /**
     * [Description for setProjectName]
     *
     * @param string $projectName
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setProjectName(string $projectName): self
    {
        $this->projectName = $projectName;

        return $this;
    }

    /**
     * [Description for getAnomalieTotal]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:47:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAnomalieTotal(): ?int
    {
        return $this->anomalieTotal;
    }

    /**
     * [Description for setAnomalieTotal]
     *
     * @param int $anomalieTotal
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setAnomalieTotal(int $anomalieTotal): self
    {
        $this->anomalieTotal = $anomalieTotal;

        return $this;
    }

    /**
     * [Description for getDetteMinute]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:47:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDetteMinute(): ?int
    {
        return $this->detteMinute;
    }

    /**
     * [Description for setDetteMinute]
     *
     * @param int $detteMinute
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDetteMinute(int $detteMinute): self
    {
        $this->detteMinute = $detteMinute;

        return $this;
    }

    /**
     * [Description for getDetteReliabilityMinute]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:47:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDetteReliabilityMinute(): ?int
    {
        return $this->detteReliabilityMinute;
    }

    /**
     * [Description for setDetteReliabilityMinute]
     *
     * @param int $detteReliabilityMinute
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDetteReliabilityMinute(int $detteReliabilityMinute): self
    {
        $this->detteReliabilityMinute = $detteReliabilityMinute;

        return $this;
    }

    /**
     * [Description for getDetteVulnerabilityMinute]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:47:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDetteVulnerabilityMinute(): ?int
    {
        return $this->detteVulnerabilityMinute;
    }

    /**
     * [Description for setDetteVulnerabilityMinute]
     *
     * @param int $detteVulnerabilityMinute
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDetteVulnerabilityMinute(int $detteVulnerabilityMinute): self
    {
        $this->detteVulnerabilityMinute = $detteVulnerabilityMinute;

        return $this;
    }

    /**
     * [Description for getDetteCodeSmellMinute]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:47:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDetteCodeSmellMinute(): ?int
    {
        return $this->detteCodeSmellMinute;
    }

    /**
     * [Description for setDetteCodeSmellMinute]
     *
     * @param int $detteCodeSmellMinute
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDetteCodeSmellMinute(int $detteCodeSmellMinute): self
    {
        $this->detteCodeSmellMinute = $detteCodeSmellMinute;

        return $this;
    }

    /**
     * [Description for getDetteReliability]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:47:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDetteReliability(): ?string
    {
        return $this->detteReliability;
    }

    /**
     * [Description for setDetteReliability]
     *
     * @param string $detteReliability
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDetteReliability(string $detteReliability): self
    {
        $this->detteReliability = $detteReliability;

        return $this;
    }

    /**
     * [Description for getDetteVulnerability]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:47:48 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDetteVulnerability(): ?string
    {
        return $this->detteVulnerability;
    }

    /**
     * [Description for setDetteVulnerability]
     *
     * @param string $detteVulnerability
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDetteVulnerability(string $detteVulnerability): self
    {
        $this->detteVulnerability = $detteVulnerability;

        return $this;
    }

    /**
     * [Description for getDette]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:47:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDette(): ?string
    {
        return $this->dette;
    }

    /**
     * [Description for setDette]
     *
     * @param string $dette
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDette(string $dette): self
    {
        $this->dette = $dette;

        return $this;
    }

    /**
     * [Description for getDetteCodeSmell]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:47:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDetteCodeSmell(): ?string
    {
        return $this->detteCodeSmell;
    }

    /**
     * [Description for setDetteCodeSmell]
     *
     * @param string $detteCodeSmell
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:47:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDetteCodeSmell(string $detteCodeSmell): self
    {
        $this->detteCodeSmell = $detteCodeSmell;

        return $this;
    }

    /**
     * [Description for getFrontend]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getFrontend(): ?int
    {
        return $this->frontend;
    }

    /**
     * [Description for setFrontend]
     *
     * @param int $frontend
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setFrontend(int $frontend): self
    {
        $this->frontend = $frontend;

        return $this;
    }

    /**
     * [Description for getBackend]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBackend(): ?int
    {
        return $this->backend;
    }

    /**
     * [Description for setBackend]
     *
     * @param int $backend
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBackend(int $backend): self
    {
        $this->backend = $backend;

        return $this;
    }

    /**
     * [Description for getAutre]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAutre(): ?int
    {
        return $this->autre;
    }

    /**
     * [Description for setAutre]
     *
     * @param int $autre
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setAutre(int $autre): self
    {
        $this->autre = $autre;

        return $this;
    }

    /**
     * [Description for getBlocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBlocker(): ?int
    {
        return $this->blocker;
    }

    /**
     * [Description for setBlocker]
     *
     * @param int $blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBlocker(int $blocker): self
    {
        $this->blocker = $blocker;

        return $this;
    }

    /**
     * [Description for getCritical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCritical(): ?int
    {
        return $this->critical;
    }

    /**
     * [Description for setCritical]
     *
     * @param int $critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCritical(int $critical): self
    {
        $this->critical = $critical;

        return $this;
    }

    /**
     * [Description for getMajor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getMajor(): ?int
    {
        return $this->major;
    }

    /**
     * [Description for setMajor]
     *
     * @param int $major
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMajor(int $major): self
    {
        $this->major = $major;

        return $this;
    }

    /**
     * [Description for getInfo]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getInfo(): ?int
    {
        return $this->info;
    }

    /**
     * [Description for setInfo]
     *
     * @param int $info
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setInfo(int $info): self
    {
        $this->info = $info;

        return $this;
    }

    /**
     * [Description for getMinor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getMinor(): ?int
    {
        return $this->minor;
    }

    /**
     * [Description for setMinor]
     *
     * @param int $minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMinor(int $minor): self
    {
        $this->minor = $minor;

        return $this;
    }

    /**
     * [Description for getBug]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBug(): ?int
    {
        return $this->bug;
    }

    /**
     * [Description for setBug]
     *
     * @param int $bug
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBug(int $bug): self
    {
        $this->bug = $bug;

        return $this;
    }

    /**
     * [Description for getVulnerability]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerability(): ?int
    {
        return $this->vulnerability;
    }

    /**
     * [Description for setVulnerability]
     *
     * @param int $vulnerability
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerability(int $vulnerability): self
    {
        $this->vulnerability = $vulnerability;

        return $this;
    }

    /**
     * [Description for getCodeSmell]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:48:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmell(): ?int
    {
        return $this->codeSmell;
    }

    /**
     * [Description for setCodeSmell]
     *
     * @param int $codeSmell
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmell(int $codeSmell): self
    {
        $this->codeSmell = $codeSmell;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:48:51 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateEnregistrement(): ?\DateTimeInterface
    {
        return $this->dateEnregistrement;
    }

    /**
     * [Description for setDateEnregistrement]
     *
     * @param \DateTimeInterface $dateEnregistrement
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:48:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateEnregistrement(\DateTimeInterface $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }


    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
