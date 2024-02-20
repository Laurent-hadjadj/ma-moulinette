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

namespace App\Entity\Main;

use App\Repository\Main\MesuresRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MesuresRepository::class)]
class Mesures
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private $mavenKey;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private $projectName;

    #[ORM\Column(type: Types::INTEGER)]
    private $lines;

    #[ORM\Column(type: Types::INTEGER)]
    private $ncloc;

    #[ORM\Column(type: Types::FLOAT)]
    private $coverage;

    #[ORM\Column(type: Types::FLOAT)]
    private $duplicationDensity;

    #[ORM\Column(type: Types::INTEGER)]
    private $tests;

    #[ORM\Column(type: Types::INTEGER)]
    private $issues;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:03:41 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:03:43 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:03:44 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:03:46 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:03:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setProjectName(string $projectName): self
    {
        $this->projectName = $projectName;

        return $this;
    }

    /**
     * [Description for getLines]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:03:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getLines(): ?int
    {
        return $this->lines;
    }


    public function setLines(int $lines): self
    {
        $this->lines = $lines;

        return $this;
    }

    /**
     * [Description for getNcloc]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:03:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNcloc(): ?int
    {
        return $this->ncloc;
    }

    /**
     * [Description for setNcloc]
     *
     * @param int $ncloc
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:03:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNcloc(int $ncloc): self
    {
        $this->ncloc = $ncloc;

        return $this;
    }

    /**
     * [Description for getCoverage]
     *
     * @return float|null
     *
     * Created at: 02/01/2023, 18:03:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCoverage(): ?float
    {
        return $this->coverage;
    }

    /**
     * [Description for setCoverage]
     *
     * @param float $coverage
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:03:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCoverage(float $coverage): self
    {
        $this->coverage = $coverage;

        return $this;
    }

    /**
     * [Description for getDuplicationDensity]
     *
     * @return float|null
     *
     * Created at: 02/01/2023, 18:03:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDuplicationDensity(): ?float
    {
        return $this->duplicationDensity;
    }

    /**
     * [Description for setDuplicationDensity]
     *
     * @param float $duplicationDensity
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:04:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDuplicationDensity(float $duplicationDensity): self
    {
        $this->duplicationDensity = $duplicationDensity;

        return $this;
    }

    /**
     * [Description for getTests]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:04:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getTests(): ?int
    {
        return $this->tests;
    }

    /**
     * [Description for setTests]
     *
     * @param int $tests
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:04:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setTests(int $tests): self
    {
        $this->tests = $tests;

        return $this;
    }

    /**
     * [Description for getIssues]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:04:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getIssues(): ?int
    {
        return $this->issues;
    }

    /**
     * [Description for setIssues]
     *
     * @param int $issues
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:04:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setIssues(int $issues): self
    {
        $this->issues = $issues;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:04:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:04:11 (Europe/Paris)
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
