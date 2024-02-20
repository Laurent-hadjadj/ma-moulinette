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

use App\Repository\Main\InformationProjetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InformationProjetRepository::class)]
class InformationProjet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 128)]
    private $mavenKey;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private $analyseKey;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $date;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private $projectVersion;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private $type;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:02:19 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:02:21 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:02:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    /**
     * [Description for getAnalyseKey]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:02:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAnalyseKey(): ?string
    {
        return $this->analyseKey;
    }

    /**
     * [Description for setAnalyseKey]
     *
     * @param string $analyseKey
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:02:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setAnalyseKey(string $analyseKey): self
    {
        $this->analyseKey = $analyseKey;

        return $this;
    }

    /**
     * [Description for getDate]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:02:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * [Description for setDate]
     *
     * @param \DateTimeInterface $date
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:02:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * [Description for getProjectVersion]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:02:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProjectVersion(): ?string
    {
        return $this->projectVersion;
    }

    /**
     * [Description for setProjectVersion]
     *
     * @param string $projectVersion
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:02:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setProjectVersion(string $projectVersion): self
    {
        $this->projectVersion = $projectVersion;

        return $this;
    }

    /**
     * [Description for getType]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:02:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * [Description for setType]
     *
     * @param string $type
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:02:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:02:36 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:02:38 (Europe/Paris)
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
