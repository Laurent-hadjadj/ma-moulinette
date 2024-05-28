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

use App\Repository\HotspotDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HotspotDetailsRepository::class)]
#[ORM\Table(name: "hotspot_details", schema: "ma_moulinette")]
class HotspotDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque détail de hotspot']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du détail de hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 128 caractères."
    )]
    private $mavenKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Version du détail de hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "La version ne doit pas dépasser 32 caractères."
    )]
    private $version;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de la version du détail de hotspot']
    )]
    #[Assert\NotNull]
    private $dateVersion;

    #[ORM\Column(
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => 'Sévérité du hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 8,
        maxMessage: "La sévérité ne doit pas dépasser 8 caractères."
    )]
    private $severity;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Niveau de risque du hotspot']
    )]
    #[Assert\NotNull]
    private $niveau;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Statut du hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le statut ne doit pas dépasser 16 caractères."
    )]
    private $status;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Implémentation frontend associée au hotspot']
    )]
    #[Assert\NotNull]
    private $frontend;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Implémentation backend associée au hotspot']
    )]
    #[Assert\NotNull]
    private $backend;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Autres implémentations associées au hotspot']
    )]
    #[Assert\NotNull]
    private $autre;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Fichier associé au hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom de fichier ne doit pas dépasser 255 caractères."
    )]
    private $file;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Ligne du fichier où se situe le hotspot']
    )]
    #[Assert\NotNull]
    private $line;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Règle associée au hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La règle ne doit pas dépasser 255 caractères."
    )]
    private $rule;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Message descriptif du hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le message ne doit pas dépasser 255 caractères."
    )]
    private $message;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Clé unique du hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "La clé ne doit pas dépasser 32 caractères."
    )]
    private $key;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d\'enregistrement du détail de hotspot']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:00:10 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:00:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:00:13 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateVersion(\DateTimeInterface $dateVersion): self
    {
        $this->dateVersion = $dateVersion;

        return $this;
    }

    /**
     * [Description for getSeverity]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:00:15 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    /**
     * [Description for setSeverity]
     *
     * @param string $severity
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:00:17 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;

        return $this;
    }

    /**
     * [Description for getNiveau]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:00:18 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:00:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNiveau(int $niveau): self
    {
        $this->niveau = $niveau;

        return $this;
    }

    /**
     * [Description for getStatus]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:00:22 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:00:24 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * [Description for getFrontend]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:00:25 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:00:27 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:00:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:00:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:00:35 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:00:37 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setAutre(int $autre): self
    {
        $this->autre = $autre;

        return $this;
    }

    /**
     * [Description for getFile]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:00:38 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * [Description for setFile]
     *
     * @param string $file
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:00:40 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * [Description for getLine]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:00:42 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getLine(): ?int
    {
        return $this->line;
    }

    /**
     * [Description for setLine]
     *
     * @param int $line
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:00:44 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setLine(int $line): self
    {
        $this->line = $line;

        return $this;
    }

    /**
     * [Description for getRule]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:00:46 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getRule(): ?string
    {
        return $this->rule;
    }

    /**
     * [Description for setRule]
     *
     * @param string $rule
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:00:48 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setRule(string $rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * [Description for getMessage]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:00:50 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * [Description for setMessage]
     *
     * @param string $message
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:00:52 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * [Description for getKey]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:00:54 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * [Description for setKey]
     *
     * @param string $key
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:00:56 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:00:58 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:01:00 (Europe/Paris)
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
