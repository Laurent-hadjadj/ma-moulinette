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

use App\Repository\ActiviteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique de l\'activité']
    )]
    private ?int $id = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven de l\'activité']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 128 caractères."
    )]
    private ?string $mavenKey = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Nom du projet associé à l\'activité']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 64,
        maxMessage: "Le nom du projet ne doit pas dépasser 64 caractères."
    )]
    private ?string $projectName = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 26,
        nullable: false,
        options: ['comment' => 'Identifiant de l\'analyse']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 26,
        maxMessage: "L'identifiant de l'analyse ne doit pas dépasser 26 caractères."
    )]
    private ?string $analyseId = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Statut de l\'activité']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le statut ne doit pas dépasser 16 caractères."
    )]
    private ?string $status = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Login de l\'utilisateur soumettant l\'activité']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le login ne doit pas dépasser 32 caractères."
    )]
    private ?string $submitterLogin = null;

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: false,
        options: ['comment' => 'Date et heure d\'exécution de l\'activité']
    )]
    #[Assert\NotNull]
    private ?\DateTimeInterface $executedAt = null;

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
