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

use App\Repository\Main\AnomalieDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnomalieDetailsRepository::class)]
class AnomalieDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private $mavenKey;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private $name;

    #[ORM\Column(type: Types::INTEGER)]
    private $bugBlocker;

    #[ORM\Column(type: Types::INTEGER)]
    private $bugCritical;

    #[ORM\Column(type: Types::INTEGER)]
    private $bugInfo;

    #[ORM\Column(type: Types::INTEGER)]
    private $bugMajor;

    #[ORM\Column(type: Types::INTEGER)]
    private $bugMinor;

    #[ORM\Column(type: Types::INTEGER)]
    private $vulnerabilityBlocker;

    #[ORM\Column(type: Types::INTEGER)]
    private $vulnerabilityCritical;

    #[ORM\Column(type: Types::INTEGER)]
    private $vulnerabilityInfo;

    #[ORM\Column(type: Types::INTEGER)]
    private $vulnerabilityMajor;

    #[ORM\Column(type: Types::INTEGER)]
    private $vulnerabilityMinor;

    #[ORM\Column(type: Types::INTEGER)]
    private $codeSmellBlocker;

    #[ORM\Column(type: Types::INTEGER)]
    private $codeSmellCritical;

    #[ORM\Column(type: Types::INTEGER)]
    private $codeSmellInfo;

    #[ORM\Column(type: Types::INTEGER)]
    private $codeSmellMajor;

    #[ORM\Column(type: Types::INTEGER)]
    private $codeSmellMinor;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:49:29 (Europe/Paris)
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
     * Created at: 02/01/2023, 17:49:32 (Europe/Paris)
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
     * Created at: 02/01/2023, 17:49:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    /**
     * [Description for getName]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:49:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * [Description for setName]
     *
     * @param string $name
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:49:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * [Description for getBugBlocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:49:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugBlocker(): ?int
    {
        return $this->bugBlocker;
    }

    /**
     * [Description for setBugBlocker]
     *
     * @param int $bugBlocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:49:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugBlocker(int $bugBlocker): self
    {
        $this->bugBlocker = $bugBlocker;

        return $this;
    }

    /**
     * [Description for getBugCritical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:49:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugCritical(): ?int
    {
        return $this->bugCritical;
    }

    /**
     * [Description for setBugCritical]
     *
     * @param int $bugCritical
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:49:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugCritical(int $bugCritical): self
    {
        $this->bugCritical = $bugCritical;

        return $this;
    }

    /**
     * [Description for getBugInfo]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:49:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugInfo(): ?int
    {
        return $this->bugInfo;
    }

    /**
     * [Description for setBugInfo]
     *
     * @param int $bugInfo
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:49:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugInfo(int $bugInfo): self
    {
        $this->bugInfo = $bugInfo;

        return $this;
    }

    /**
     * [Description for getBugMajor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:49:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugMajor(): ?int
    {
        return $this->bugMajor;
    }

    /**
     * [Description for setBugMajor]
     *
     * @param int $bugMajor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:49:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugMajor(int $bugMajor): self
    {
        $this->bugMajor = $bugMajor;

        return $this;
    }

    /**
     * [Description for getBugMinor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:49:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugMinor(): ?int
    {
        return $this->bugMinor;
    }

    /**
     * [Description for setBugMinor]
     *
     * @param int $bugMinor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugMinor(int $bugMinor): self
    {
        $this->bugMinor = $bugMinor;

        return $this;
    }

    /**
     * [Description for getVulnerabilityBlocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityBlocker(): ?int
    {
        return $this->vulnerabilityBlocker;
    }

    /**
     * [Description for setVulnerabilityBlocker]
     *
     * @param int $vulnerabilityBlocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityBlocker(int $vulnerabilityBlocker): self
    {
        $this->vulnerabilityBlocker = $vulnerabilityBlocker;

        return $this;
    }

    /**
     * [Description for getVulnerabilityCritical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityCritical(): ?int
    {
        return $this->vulnerabilityCritical;
    }

    /**
     * [Description for setVulnerabilityCritical]
     *
     * @param int $vulnerabilityCritical
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityCritical(int $vulnerabilityCritical): self
    {
        $this->vulnerabilityCritical = $vulnerabilityCritical;

        return $this;
    }

    /**
     * [Description for getVulnerabilityInfo]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityInfo(): ?int
    {
        return $this->vulnerabilityInfo;
    }

    /**
     * [Description for setVulnerabilityInfo]
     *
     * @param int $vulnerabilityInfo
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityInfo(int $vulnerabilityInfo): self
    {
        $this->vulnerabilityInfo = $vulnerabilityInfo;

        return $this;
    }

    /**
     * [Description for getVulnerabilityMajor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityMajor(): ?int
    {
        return $this->vulnerabilityMajor;
    }

    /**
     * [Description for setVulnerabilityMajor]
     *
     * @param int $vulnerabilityMajor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityMajor(int $vulnerabilityMajor): self
    {
        $this->vulnerabilityMajor = $vulnerabilityMajor;

        return $this;
    }

    /**
     * [Description for getVulnerabilityMinor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityMinor(): ?int
    {
        return $this->vulnerabilityMinor;
    }

    /**
     * [Description for setVulnerabilityMinor]
     *
     * @param int $vulnerabilityMinor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityMinor(int $vulnerabilityMinor): self
    {
        $this->vulnerabilityMinor = $vulnerabilityMinor;

        return $this;
    }

    /**
     * [Description for getCodeSmellBlocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellBlocker(): ?int
    {
        return $this->codeSmellBlocker;
    }

    /**
     * [Description for setCodeSmellBlocker]
     *
     * @param int $codeSmellBlocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellBlocker(int $codeSmellBlocker): self
    {
        $this->codeSmellBlocker = $codeSmellBlocker;

        return $this;
    }

    /**
     * [Description for getCodeSmellCritical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellCritical(): ?int
    {
        return $this->codeSmellCritical;
    }

    /**
     * [Description for setCodeSmellCritical]
     *
     * @param int $codeSmellCritical
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellCritical(int $codeSmellCritical): self
    {
        $this->codeSmellCritical = $codeSmellCritical;

        return $this;
    }

    /**
     * [Description for getCodeSmellInfo]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellInfo(): ?int
    {
        return $this->codeSmellInfo;
    }

    /**
     * [Description for setCodeSmellInfo]
     *
     * @param int $codeSmellInfo
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellInfo(int $codeSmellInfo): self
    {
        $this->codeSmellInfo = $codeSmellInfo;

        return $this;
    }

    /**
     * [Description for getCodeSmellMajor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellMajor(): ?int
    {
        return $this->codeSmellMajor;
    }

    /**
     * [Description for setCodeSmellMajor]
     *
     * @param int $codeSmellMajor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellMajor(int $codeSmellMajor): self
    {
        $this->codeSmellMajor = $codeSmellMajor;

        return $this;
    }

    /**
     * [Description for getCodeSmellMinor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellMinor(): ?int
    {
        return $this->codeSmellMinor;
    }

    /**
     * [Description for setCodeSmellMinor]
     *
     * @param int $codeSmellMinor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:50:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellMinor(int $codeSmellMinor): self
    {
        $this->codeSmellMinor = $codeSmellMinor;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:50:43 (Europe/Paris)
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
     * Created at: 02/01/2023, 17:50:51 (Europe/Paris)
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
