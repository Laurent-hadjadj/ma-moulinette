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

use App\Repository\HotspotOwaspRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HotspotOwaspRepository::class)]
class HotspotOwasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque hotspot OWASP']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du hotspot OWASP']
    )]
    #[Assert\NotBlank(message: "La clé Maven ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 128 caractères."
    )]
    private $mavenKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Version du hotspot OWASP']
    )]
    #[Assert\NotBlank(message: "La version ne peut pas être vide.")]
    #[Assert\Length(
        max: 32,
        maxMessage: "La version ne doit pas dépasser 32 caractères."
    )]
    private $version;

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: false,
        options: ['comment' => 'Date de la version du hotspot OWASP']
    )]
    #[Assert\NotNull(message: "La date de la version ne peut pas être nulle.")]
    private $dateVersion;

    #[ORM\Column(
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => 'Menace évaluée du hotspot OWASP']
    )]
    #[Assert\NotBlank(message: "La menace ne peut pas être vide.")]
    #[Assert\Length(
        max: 8,
        maxMessage: "La menace ne doit pas dépasser 8 caractères."
    )]
    private $menace;

    #[ORM\Column(
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => 'Probabilité du hotspot OWASP']
    )]
    #[Assert\NotBlank(message: "La probabilité ne peut pas être vide.")]
    #[Assert\Length(
        max: 8,
        maxMessage: "La probabilité ne doit pas dépasser 8 caractères."
    )]
    private $probability;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Statut du hotspot OWASP']
    )]
    #[Assert\NotBlank(message: "Le statut ne peut pas être vide.")]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le statut ne doit pas dépasser 16 caractères."
    )]
    private $status;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Niveau de risque du hotspot OWASP']
    )]
    #[Assert\NotNull(message: "Le niveau ne peut pas être nul.")]
    private $niveau;

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: false,
        options: ['comment' => 'Date d\'enregistrement du hotspot OWASP']
    )]
    #[Assert\NotNull(message: "La date d'enregistrement ne peut pas être nulle.")]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:01:11 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:01:13 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:01:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateVersion(\DateTimeInterface $dateVersion): self
    {
        $this->dateVersion = $dateVersion;

        return $this;
    }

    /**
     * [Description for getMenace]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:01:16 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getMenace(): ?string
    {
        return $this->menace;
    }

    /**
     * [Description for setMenace]
     *
     * @param string $menace
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:01:18 (Europe/Paris)
     * @author   Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMenace(string $menace): self
    {
        $this->menace = $menace;

        return $this;
    }

    /**
     * [Description for getProbability]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:01:19 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProbability(): ?string
    {
        return $this->probability;
    }

    /**
     * [Description for setProbability]
     *
     * @param string $probability
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:01:21 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setProbability(string $probability): self
    {
        $this->probability = $probability;

        return $this;
    }

    /**
     * [Description for getStatus]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:01:24 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * @return self
     *
     * Created at: 02/01/2023, 18:01:25 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * [Description for getNiveau]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:01:27 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    /**
     * [Description for setNiveau]
     *
     * @param int $niveau
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:01:29 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNiveau(int $niveau): self
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:01:32 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:01:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
