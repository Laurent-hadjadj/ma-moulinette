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

use App\Repository\Main\ActiviteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private $mavenKey = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private $projectName = null;

    #[ORM\Column(type: Types::STRING,length: 26)]
    private  $analyseId = null;

    #[ORM\Column(type: Types::STRING,length: 16)]
    private $status = null;

    #[ORM\Column(type: Types::STRING,length: 32)]
    private $submitterLogin = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $executedAt = null;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 20/02/2024 17:10:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * [Description for setId]
     *
     * @param int $id
     *
     * @return static
     *
     * Created at: 20/02/2024 17:10:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * [Description for getMavenKey]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:10:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * @return static
     *
     * Created at: 20/02/2024 17:10:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMavenKey(string $mavenKey): static
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    /**
     * [Description for getProjectName]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:10:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * @return static
     *
     * Created at: 20/02/2024 17:10:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setProjectName(string $projectName): static
    {
        $this->projectName = $projectName;

        return $this;
    }

    /**
     * [Description for getAnalyseId]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:10:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAnalyseId(): ?string
    {
        return $this->analyseId;
    }

    /**
     * [Description for setString]
     *
     * @param string $analyseId
     *
     * @return static
     *
     * Created at: 20/02/2024 17:10:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setString(string $analyseId): static
    {
        $this->analyseId = $analyseId;

        return $this;
    }

    /**
     * [Description for getStatus]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:10:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * [Description for setStatus]
     *
     * @param string $status
     *
     * @return static
     *
     * Created at: 20/02/2024 17:10:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * [Description for getSubmitterLogin]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:10:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getSubmitterLogin(): ?string
    {
        return $this->submitterLogin;
    }

    /**
     * [Description for setSubmitterLogin]
     *
     * @param string $submitterLogin
     *
     * @return static
     *
     * Created at: 20/02/2024 17:10:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setSubmitterLogin(string $submitterLogin): static
    {
        $this->submitterLogin = $submitterLogin;

        return $this;
    }

    /**
     * [Description for getExecutedAt]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 20/02/2024 17:10:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getExecutedAt(): ?\DateTimeInterface
    {
        return $this->executedAt;
    }

    /**
     * [Description for setExecutedAt]
     *
     * @param \DateTimeInterface $executedAt
     *
     * @return static
     *
     * Created at: 20/02/2024 17:10:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setExecutedAt(\DateTimeInterface $executedAt): static
    {
        $this->executedAt = $executedAt;

        return $this;
    }
}
