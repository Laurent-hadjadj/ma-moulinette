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

use App\Repository\OwaspRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description Owasp]
 */
#[ORM\Entity(repositoryClass: OwaspRepository::class)]
#[ORM\Table(name: "owasp", schema: "ma_moulinette")]
class Owasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'clé unique pour la table Owasp']
    )]
    private int $id;

    #[ORM\Column(
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
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Version of the project']
    )]
    #[Assert\NotBlank]
    private string $version;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date d'enregistrement de la version"]
    )]
    #[Assert\NotNull]
    private \DateTimeInterface $dateVersion;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => "Score total d'effort pour les questions de sécurité"]
    )]
    #[Assert\NotNull]
    private int $effortTotal;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A1']
    )]
    #[Assert\NotNull]
    private int $a1;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A2']
    )]
    #[Assert\NotNull]
    private int $a2;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A3']
    )]
    #[Assert\NotNull]
    private int $a3;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A4']
    )]
    #[Assert\NotNull]
    private $a4;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A5']
    )]
    #[Assert\NotNull]
    private $a5;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A6']
    )]
    #[Assert\NotNull]
    private $a6;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A7']
    )]
    #[Assert\NotNull]
    private $a7;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A8']
    )]
    #[Assert\NotNull]
    private $a8;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A9']
    )]
    #[Assert\NotNull]
    private $a9;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A10']
    )]
    #[Assert\NotNull]
    private $a10;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'OWASP Top 10 - A1']
    )]
    #[Assert\NotNull]
    private $a1Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A1']
    )]
    #[Assert\NotNull]
    private $a1Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A1']
    )]
    #[Assert\NotNull]
    private $a1Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A1']
    )]
    #[Assert\NotNull]
    private $a1Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A1']
    )]
    #[Assert\NotNull]
    private $a1Minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A2']
    )]
    #[Assert\NotNull]
    private $a2Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A2']
    )]
    #[Assert\NotNull]
    private $a2Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A2']
    )]
    #[Assert\NotNull]
    private $a2Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A2']
    )]
    #[Assert\NotNull]
    private $a2Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A2']
    )]
    #[Assert\NotNull]
    private $a2Minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A3']
    )]
    #[Assert\NotNull]
    private $a3Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A3']
    )]
    #[Assert\NotNull]
    private $a3Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A3']
    )]
    #[Assert\NotNull]
    private $a3Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A3']
    )]
    #[Assert\NotNull]
    private $a3Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A3']
    )]
    #[Assert\NotNull]
    private $a3Minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A4']
    )]
    #[Assert\NotNull]
    private $a4Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A4']
    )]
    #[Assert\NotNull]
    private $a4Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A4']
    )]
    #[Assert\NotNull]
    private $a4Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A4']
    )]
    #[Assert\NotNull]
    private $a4Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A4']
    )]
    #[Assert\NotNull]
    private $a4Minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A5']
    )]
    #[Assert\NotNull]
    private $a5Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A5']
    )]
    #[Assert\NotNull]
    private $a5Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A5']
    )]
    #[Assert\NotNull]
    private $a5Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A5']
    )]
    #[Assert\NotNull]
    private $a5Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A5']
    )]
    #[Assert\NotNull]
    private $a5Minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A6']
    )]
    #[Assert\NotNull]
    private $a6Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A6']
    )]
    #[Assert\NotNull]
    private $a6Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A6']
    )]
    #[Assert\NotNull]
    private $a6Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A6']
    )]
    #[Assert\NotNull]
    private $a6Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A6']
    )]
    #[Assert\NotNull]
    private $a6Minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A7']
    )]
    #[Assert\NotNull]
    private $a7Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A7']
    )]
    #[Assert\NotNull]
    private $a7Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A7']
    )]
    #[Assert\NotNull]
    private $a7Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A7']
    )]
    #[Assert\NotNull]
    private $a7Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A7']
    )]#[Assert\NotNull]
    private $a7Minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A8']
    )]
    #[Assert\NotNull]
    private $a8Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A8']
    )]
    #[Assert\NotNull]
    private $a8Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A8']
    )]
    #[Assert\NotNull]
    private $a8Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A8']
    )]
    #[Assert\NotNull]
    private $a8Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A8']
    )]
    #[Assert\NotNull]
    private $a8Minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A9']
    )]
    #[Assert\NotNull]
    private $a9Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A9']
    )]
    #[Assert\NotNull]
    private $a9Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A9']
    )]
    #[Assert\NotNull]
    private $a9Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A9']
    )]
    #[Assert\NotNull]
    private $a9Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A9']
    )]
    #[Assert\NotNull]
    private $a9Minor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bloqueurs pour A10']
    )]
    #[Assert\NotNull]
    private $a10Blocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de critiques pour A10']
    )]
    #[Assert\NotNull]
    private $a10Critical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de majeurs pour A10']
    )]
    #[Assert\NotNull]
    private $a10Major;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d’informations pour A10']
    )]
    #[Assert\NotNull]
    private $a10Info;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mineurs pour A10']
    )]
    #[Assert\NotNull]
    private $a10Minor;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement des données']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:03 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:05:04 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:05:06 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    /**
     * [Description for getVersion]
     *
     * @return string|null
     *
     * Created at: 04/03/2024 10:37:35 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * [Description for setVersion]
     *
     * @param string $version
     *
     * @return self
     *
     * Created at: 04/03/2024 10:37:40 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * [Description for getDateVersion]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 04/03/2024 10:40:50 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateVersion(): ?\DateTimeInterface
    {
        return $this->dateVersion;
    }

    /**
     * [Description for setDateVersion]
     *
     * @param \DateTimeInterface $dateVersion
     *
     * @return self
     *
     * Created at: 04/03/2024 10:40:47 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateVersion(\DateTimeInterface $dateVersion): self
    {
        $this->dateVersion = $dateVersion;

        return $this;
    }

    /**
     * [Description for getEffortTotal]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:08 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getEffortTotal(): ?int
    {
        return $this->effortTotal;
    }

    /**
     * [Description for setEffortTotal]
     *
     * @param int $effortTotal
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setEffortTotal(int $effortTotal): self
    {
        $this->effortTotal = $effortTotal;

        return $this;
    }

    /**
     * [Description for getA1]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA1(): ?int
    {
        return $this->a1;
    }

    /**
     * [Description for setA1]
     *
     * @param int $a1
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    /**
     * [Description for setA1]
     *
     * @param int $a1
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:15 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA1(int $a1): self
    {
        $this->a1 = $a1;

        return $this;
    }

    /**
     * [Description for getA2]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:18 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA2(): ?int
    {
        return $this->a2;
    }

    /**
     * [Description for setA2]
     *
     * @param int $a2
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA2(int $a2): self
    {
        $this->a2 = $a2;

        return $this;
    }

    /**
     * [Description for getA3]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:21 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA3(): ?int
    {
        return $this->a3;
    }

    /**
     * [Description for setA3]
     *
     * @param int $a3
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA3(int $a3): self
    {
        $this->a3 = $a3;

        return $this;
    }

    /**
     * [Description for getA4]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:24 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA4(): ?int
    {
        return $this->a4;
    }

    /**
     * [Description for setA4]
     *
     * @param int $a4
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA4(int $a4): self
    {
        $this->a4 = $a4;

        return $this;
    }


    public function getA5(): ?int
    {
        return $this->a5;
    }

    /**
     * [Description for setA5]
     *
     * @param int $a5
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:29 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA5(int $a5): self
    {
        $this->a5 = $a5;

        return $this;
    }

    /**
     * [Description for getA6]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA6(): ?int
    {
        return $this->a6;
    }

    /**
     * [Description for setA6]
     *
     * @param int $a6
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA6(int $a6): self
    {
        $this->a6 = $a6;

        return $this;
    }


    public function getA7(): ?int
    {
        return $this->a7;
    }

    /**
     * [Description for setA7]
     *
     * @param int $a7
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:36 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA7(int $a7): self
    {
        $this->a7 = $a7;

        return $this;
    }


    /**
     * [Description for getA8]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:39 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA8(): ?int
    {
        return $this->a8;
    }

    /**
     * [Description for setA8]
     *
     * @param int $a8
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:41 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA8(int $a8): self
    {
        $this->a8 = $a8;

        return $this;
    }

    /**
     * [Description for getA9]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:42 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA9(): ?int
    {
        return $this->a9;
    }

    /**
     * [Description for setA9]
     *
     * @param int $a9
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:44 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA9(int $a9): self
    {
        $this->a9 = $a9;

        return $this;
    }

    /**
     * [Description for getA10]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:45 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA10(): ?int
    {
        return $this->a10;
    }

    /**
     * [Description for setA10]
     *
     * @param int $a10
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:47 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA10(int $a10): self
    {
        $this->a10 = $a10;

        return $this;
    }

    /**
     * [Description for getA1Blocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:50 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA1Blocker(): ?int
    {
        return $this->a1Blocker;
    }

    /**
     * [Description for setA1Blocker]
     *
     * @param int $a1Blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:52 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA1Blocker(int $a1Blocker): self
    {
        $this->a1Blocker = $a1Blocker;

        return $this;
    }

    /**
     * [Description for getA1Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:54 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA1Critical(): ?int
    {
        return $this->a1Critical;
    }

    /**
     * [Description for setA1Critical]
     *
     * @param int $a1Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:55 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA1Critical(int $a1Critical): self
    {
        $this->a1Critical = $a1Critical;

        return $this;
    }

    /**
     * [Description for getA1Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:05:57 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA1Major(): ?int
    {
        return $this->a1Major;
    }

    /**
     * [Description for setA1Major]
     *
     * @param int $a1Major
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:05:58 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA1Major(int $a1Major): self
    {
        $this->a1Major = $a1Major;

        return $this;
    }

    /**
     * [Description for getA1Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:00 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA1Info(): ?int
    {
        return $this->a1Info;
    }

    /**
     * [Description for setA1Info]
     *
     * @param int $a1Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:02 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA1Info(int $a1Info): self
    {
        $this->a1Info = $a1Info;

        return $this;
    }

    /**
     * [Description for getA1Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:03 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA1Minor(): ?int
    {
        return $this->a1Minor;
    }

    /**
     * [Description for setA1Minor]
     *
     * @param int $a1Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:05 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA1Minor(int $a1Minor): self
    {
        $this->a1Minor = $a1Minor;

        return $this;
    }

    /**
     * [Description for getA2Blocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:07 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA2Blocker(): ?int
    {
        return $this->a2Blocker;
    }

    /**
     * [Description for setA2Blocker]
     *
     * @param int $a2Blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:09 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA2Blocker(int $a2Blocker): self
    {
        $this->a2Blocker = $a2Blocker;

        return $this;
    }

    /**
     * [Description for getA2Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:11 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA2Critical(): ?int
    {
        return $this->a2Critical;
    }

    /**
     * [Description for setA2Critical]
     *
     * @param int $a2Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:12 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA2Critical(int $a2Critical): self
    {
        $this->a2Critical = $a2Critical;

        return $this;
    }


    /**
     * [Description for getA2Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:15 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA2Major(): ?int
    {
        return $this->a2Major;
    }

    public function setA2Major(int $a2Major): self
    {
        $this->a2Major = $a2Major;

        return $this;
    }

    /**
     * [Description for getA2Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:18 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA2Info(): ?int
    {
        return $this->a2Info;
    }

    /**
     * [Description for setA2Info]
     *
     * @param int $a2Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:20 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA2Info(int $a2Info): self
    {
        $this->a2Info = $a2Info;

        return $this;
    }

    /**
     * [Description for getA2Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:22 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA2Minor(): ?int
    {
        return $this->a2Minor;
    }

    /**
     * [Description for setA2Minor]
     *
     * @param int $a2Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:24 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA2Minor(int $a2Minor): self
    {
        $this->a2Minor = $a2Minor;

        return $this;
    }

    /**
     * [Description for getA3Blocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:27 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA3Blocker(): ?int
    {
        return $this->a3Blocker;
    }

    /**
     * [Description for setA3Blocker]
     *
     * @param int $a3Blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:29 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA3Blocker(int $a3Blocker): self
    {
        $this->a3Blocker = $a3Blocker;

        return $this;
    }

    /**
     * [Description for getA3Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:32 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA3Critical(): ?int
    {
        return $this->a3Critical;
    }

    /**
     * [Description for setA3Critical]
     *
     * @param int $a3Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:33 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA3Critical(int $a3Critical): self
    {
        $this->a3Critical = $a3Critical;

        return $this;
    }

    /**
     * [Description for getA3Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:35 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA3Major(): ?int
    {
        return $this->a3Major;
    }

    /**
     * [Description for setA3Major]
     *
     * @param int $a3Major
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:37 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA3Major(int $a3Major): self
    {
        $this->a3Major = $a3Major;

        return $this;
    }

    /**
     * [Description for getA3Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:39 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA3Info(): ?int
    {
        return $this->a3Info;
    }

    /**
     * [Description for setA3Info]
     *
     * @param int $a3Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:40 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA3Info(int $a3Info): self
    {
        $this->a3Info = $a3Info;

        return $this;
    }

    /**
     * [Description for getA3Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:42 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA3Minor(): ?int
    {
        return $this->a3Minor;
    }

    /**
     * [Description for setA3Minor]
     *
     * @param int $a3Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:43 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA3Minor(int $a3Minor): self
    {
        $this->a3Minor = $a3Minor;

        return $this;
    }

    /**
     * [Description for getA4Blocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:46 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA4Blocker(): ?int
    {
        return $this->a4Blocker;
    }


    public function setA4Blocker(int $a4Blocker): self
    {
        $this->a4Blocker = $a4Blocker;

        return $this;
    }


    /**
     * [Description for getA4Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:51 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA4Critical(): ?int
    {
        return $this->a4Critical;
    }

    /**
     * [Description for setA4Critical]
     *
     * @param int $a4Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:56 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA4Critical(int $a4Critical): self
    {
        $this->a4Critical = $a4Critical;

        return $this;
    }

    /**
     * [Description for getA4Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:06:58 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA4Major(): ?int
    {
        return $this->a4Major;
    }

    /**
     * [Description for setA4Major]
     *
     * @param int $a4Major
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:06:59 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA4Major(int $a4Major): self
    {
        $this->a4Major = $a4Major;

        return $this;
    }

    /**
     * [Description for getA4Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:01 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA4Info(): ?int
    {
        return $this->a4Info;
    }

    /**
     * [Description for setA4Info]
     *
     * @param int $a4Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:03 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA4Info(int $a4Info): self
    {
        $this->a4Info = $a4Info;

        return $this;
    }

    /**
     * [Description for getA4Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:04 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA4Minor(): ?int
    {
        return $this->a4Minor;
    }

    /**
     * [Description for setA4Minor]
     *
     * @param int $a4Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:06 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA4Minor(int $a4Minor): self
    {
        $this->a4Minor = $a4Minor;

        return $this;
    }


    public function getA5Blocker(): ?int
    {
        return $this->a5Blocker;
    }

    /**
     * [Description for setA5Blocker]
     *
     * @param int $a5Blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:10 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA5Blocker(int $a5Blocker): self
    {
        $this->a5Blocker = $a5Blocker;

        return $this;
    }

    /**
     * [Description for getA5Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:12 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA5Critical(): ?int
    {
        return $this->a5Critical;
    }

    /**
     * [Description for setA5Critical]
     *
     * @param int $a5Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:13 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA5Critical(int $a5Critical): self
    {
        $this->a5Critical = $a5Critical;

        return $this;
    }

    /**
     * [Description for getA5Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:15 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA5Major(): ?int
    {
        return $this->a5Major;
    }

    /**
     * [Description for setA5Major]
     *
     * @param int $a5Major
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:16 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA5Major(int $a5Major): self
    {
        $this->a5Major = $a5Major;

        return $this;
    }

    /**
     * [Description for getA5Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:18 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA5Info(): ?int
    {
        return $this->a5Info;
    }

    /**
     * [Description for setA5Info]
     *
     * @param int $a5Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:20 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA5Info(int $a5Info): self
    {
        $this->a5Info = $a5Info;

        return $this;
    }

    /**
     * [Description for getA5Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:22 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA5Minor(): ?int
    {
        return $this->a5Minor;
    }

    /**
     * [Description for setA5Minor]
     *
     * @param int $a5Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:24 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA5Minor(int $a5Minor): self
    {
        $this->a5Minor = $a5Minor;

        return $this;
    }

    /**
     * [Description for getA6Blocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:26 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA6Blocker(): ?int
    {
        return $this->a6Blocker;
    }

    /**
     * [Description for setA6Blocker]
     *
     * @param int $a6Blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:27 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA6Blocker(int $a6Blocker): self
    {
        $this->a6Blocker = $a6Blocker;

        return $this;
    }

    /**
     * [Description for getA6Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:29 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA6Critical(): ?int
    {
        return $this->a6Critical;
    }

    /**
     * [Description for setA6Critical]
     *
     * @param int $a6Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:31 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA6Critical(int $a6Critical): self
    {
        $this->a6Critical = $a6Critical;

        return $this;
    }

    /**
     * [Description for getA6Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:32 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA6Major(): ?int
    {
        return $this->a6Major;
    }

    /**
     * [Description for setA6Major]
     *
     * @param int $a6Major
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:34 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA6Major(int $a6Major): self
    {
        $this->a6Major = $a6Major;

        return $this;
    }

    /**
     * [Description for getA6Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:37 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA6Info(): ?int
    {
        return $this->a6Info;
    }

    /**
     * [Description for setA6Info]
     *
     * @param int $a6Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:38 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA6Info(int $a6Info): self
    {
        $this->a6Info = $a6Info;

        return $this;
    }

    /**
     * [Description for getA6Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:40 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA6Minor(): ?int
    {
        return $this->a6Minor;
    }

    /**
     * [Description for setA6Minor]
     *
     * @param int $a6Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:41 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA6Minor(int $a6Minor): self
    {
        $this->a6Minor = $a6Minor;

        return $this;
    }

    /**
     * [Description for getA7Blocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:43 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA7Blocker(): ?int
    {
        return $this->a7Blocker;
    }

    /**
     * [Description for setA7Blocker]
     *
     * @param int $a7Blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:45 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA7Blocker(int $a7Blocker): self
    {
        $this->a7Blocker = $a7Blocker;

        return $this;
    }

    /**
     * [Description for getA7Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:46 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA7Critical(): ?int
    {
        return $this->a7Critical;
    }

    /**
     * [Description for setA7Critical]
     *
     * @param int $a7Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:48 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA7Critical(int $a7Critical): self
    {
        $this->a7Critical = $a7Critical;

        return $this;
    }

    /**
     * [Description for getA7Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:50 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA7Major(): ?int
    {
        return $this->a7Major;
    }

    /**
     * [Description for setA7Major]
     *
     * @param int $a7Major
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:51 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA7Major(int $a7Major): self
    {
        $this->a7Major = $a7Major;

        return $this;
    }

    /**
     * [Description for getA7Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:53 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA7Info(): ?int
    {
        return $this->a7Info;
    }

    /**
     * [Description for setA7Info]
     *
     * @param int $a7Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:55 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA7Info(int $a7Info): self
    {
        $this->a7Info = $a7Info;

        return $this;
    }

    /**
     * [Description for getA7Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:07:56 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA7Minor(): ?int
    {
        return $this->a7Minor;
    }

    /**
     * [Description for setA7Minor]
     *
     * @param int $a7Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:07:58 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA7Minor(int $a7Minor): self
    {
        $this->a7Minor = $a7Minor;

        return $this;
    }

    /**
     * [Description for getA8Blocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:03 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA8Blocker(): ?int
    {
        return $this->a8Blocker;
    }

    /**
     * [Description for setA8Blocker]
     *
     * @param int $a8Blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:04 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA8Blocker(int $a8Blocker): self
    {
        $this->a8Blocker = $a8Blocker;

        return $this;
    }

    /**
     * [Description for getA8Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:08 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA8Critical(): ?int
    {
        return $this->a8Critical;
    }

    /**
     * [Description for setA8Critical]
     *
     * @param int $a8Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:09 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA8Critical(int $a8Critical): self
    {
        $this->a8Critical = $a8Critical;

        return $this;
    }

    /**
     * [Description for getA8Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:12 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA8Major(): ?int
    {
        return $this->a8Major;
    }

    /**
     * [Description for setA8Major]
     *
     * @param int $a8Major
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:13 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA8Major(int $a8Major): self
    {
        $this->a8Major = $a8Major;

        return $this;
    }

    /**
     * [Description for getA8Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:15 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA8Info(): ?int
    {
        return $this->a8Info;
    }

    /**
     * [Description for setA8Info]
     *
     * @param int $a8Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:16 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA8Info(int $a8Info): self
    {
        $this->a8Info = $a8Info;

        return $this;
    }

    /**
     * [Description for getA8Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:19 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA8Minor(): ?int
    {
        return $this->a8Minor;
    }

    /**
     * [Description for setA8Minor]
     *
     * @param int $a8Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:20 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA8Minor(int $a8Minor): self
    {
        $this->a8Minor = $a8Minor;

        return $this;
    }

    /**
     * [Description for getA9Blocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:23 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA9Blocker(): ?int
    {
        return $this->a9Blocker;
    }

    /**
     * [Description for setA9Blocker]
     *
     * @param int $a9Blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:26 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA9Blocker(int $a9Blocker): self
    {
        $this->a9Blocker = $a9Blocker;

        return $this;
    }


    /**
     * [Description for getA9Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:35 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA9Critical(): ?int
    {
        return $this->a9Critical;
    }

    /**
     * [Description for setA9Critical]
     *
     * @param int $a9Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:31 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA9Critical(int $a9Critical): self
    {
        $this->a9Critical = $a9Critical;

        return $this;
    }

    /**
     * [Description for getA9Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:40 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA9Major(): ?int
    {
        return $this->a9Major;
    }

    /**
     * [Description for setA9Major]
     *
     * @param int $a9Major
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:42 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA9Major(int $a9Major): self
    {
        $this->a9Major = $a9Major;

        return $this;
    }

    /**
     * [Description for getA9Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:44 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA9Info(): ?int
    {
        return $this->a9Info;
    }

    /**
     * [Description for setA9Info]
     *
     * @param int $a9Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:46 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA9Info(int $a9Info): self
    {
        $this->a9Info = $a9Info;

        return $this;
    }

    /**
     * [Description for getA9Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:48 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA9Minor(): ?int
    {
        return $this->a9Minor;
    }

    /**
     * [Description for setA9Minor]
     *
     * @param int $a9Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:50 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA9Minor(int $a9Minor): self
    {
        $this->a9Minor = $a9Minor;

        return $this;
    }

    /**
     * [Description for getA10Blocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:52 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA10Blocker(): ?int
    {
        return $this->a10Blocker;
    }

    /**
     * [Description for setA10Blocker]
     *
     * @param int $a10Blocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:54 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA10Blocker(int $a10Blocker): self
    {
        $this->a10Blocker = $a10Blocker;

        return $this;
    }

    /**
     * [Description for getA10Critical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:08:56 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA10Critical(): ?int
    {
        return $this->a10Critical;
    }

    /**
     * [Description for setA10Critical]
     *
     * @param int $a10Critical
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:08:58 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA10Critical(int $a10Critical): self
    {
        $this->a10Critical = $a10Critical;

        return $this;
    }

    /**
     * [Description for getA10Major]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:09:02 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA10Major(): ?int
    {
        return $this->a10Major;
    }

    /**
     * [Description for setA10Major]
     *
     * @param int $a10Major
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:09:06 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA10Major(int $a10Major): self
    {
        $this->a10Major = $a10Major;

        return $this;
    }

    /**
     * [Description for getA10Info]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:09:09 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA10Info(): ?int
    {
        return $this->a10Info;
    }

    /**
     * [Description for setA10Info]
     *
     * @param int $a10Info
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:09:11 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA10Info(int $a10Info): self
    {
        $this->a10Info = $a10Info;

        return $this;
    }

    /**
     * [Description for getA10Minor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:09:13 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getA10Minor(): ?int
    {
        return $this->a10Minor;
    }

    /**
     * [Description for setA10Minor]
     *
     * @param int $a10Minor
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:09:14 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setA10Minor(int $a10Minor): self
    {
        $this->a10Minor = $a10Minor;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:09:16 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:09:17 (Europe/Paris)
     * @author    Laurent HADJADJ HADJADJ <laurent_h@me.com>
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
