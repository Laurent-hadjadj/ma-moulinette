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

use App\Repository\BatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as AcmeAssert;

#[ORM\Entity(repositoryClass: BatchRepository::class)]
#[ORM\Table(name: "batch", schema: "ma_moulinette")]
class Batch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique du batch']
    )]
    private $id;

    #[ORM\Column(
        type: Types::BOOLEAN,
        nullable: false,
        options: ['comment' => 'Statut d’activité du batch']
    )]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    private $statut = false;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        unique: true,
        nullable: false,
        options: ['comment' => 'Titre du batch, unique']
    )]
    #[AcmeAssert\ContainsBatchUnique()]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le titre ne doit pas dépasser 32 caractères."
    )]
    private $titre;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Description du batch']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "La description ne doit pas dépasser 128 caractères."
    )]
    private $description;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom de l’utilisateur responsable']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom de l'utilisateur ne doit pas dépasser 128 caractères."
    )]
    private $responsable;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        unique: true,
        nullable: false,
        options: ['comment' => 'Portefeuille de projet, unique']
    )]
    #[AcmeAssert\ContainsBatchUnique()]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le portefeuille ne doit pas dépasser 32 caractères."
    )]
    private $portefeuille = "Aucun";

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de projets dans le batch']
    )]
    #[Assert\Type(type: 'integer')]
    #[Assert\NotNull]
    private $nombreProjet = 0;

    #[ORM\Column(
        type: Types::STRING,
        length: 8,
        nullable: true,
        options: ['comment' => 'État d’exécution du batch']
    )]
    #[Assert\NotBlank]
    private $execution;

    #[ORM\Column(
        type: Types::DATETIMETZ_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification du batch']
    )]
    #[Assert\NotNull]
    private $dateModification;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du batch']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:50:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * [Description for isStatut]
     *
     * @return bool|null
     *
     * Created at: 02/01/2023, 17:51:01 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isStatut(): ?bool
    {
        return $this->statut;
    }

    /**
     * [Description for setStatut]
     *
     * @param bool $statut
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:51:03 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setStatut(bool $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    /**
     * [Description for getTitre]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:51:05 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getTitre(): ?string
    {
        return $this->titre;
    }

    /**
     * [Description for setTitre]
     *
     * @param string $titre
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:51:06 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * [Description for getDescription]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:51:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * [Description for setDescription]
     *
     * @param string $description
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:51:10 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * [Description for getResponsable]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:51:11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    /**
     * [Description for setResponsable]
     *
     * @param string $responsable
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:51:13 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setResponsable(string $responsable): self
    {
        $this->responsable = $responsable;

        return $this;
    }

    /**
     * [Description for getPortefeuille]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:51:15 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    /**
     * [Description for setPortefeuille]
     *
     * @param string $portefeuille
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:51:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setPortefeuille(string $portefeuille): self
    {
        $this->portefeuille = $portefeuille;

        return $this;
    }

    /**
     * [Description for getNombreProjet]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:51:18 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreProjet(): ?int
    {
        return $this->nombreProjet;
    }

    /**
     * [Description for setNombreProjet]
     *
     * @param int $nombreProjet
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:51:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreProjet(int $nombreProjet): self
    {
        $this->nombreProjet = $nombreProjet;

        return $this;
    }

    /**
     * [Description for getExecution]
     *
     * @return string|null
     *
     * Created at: 07/02/2023, 07:43:07 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getExecution(): ?string
    {
        return $this->execution;
    }

    /**
     * [Description for setExecution]
     *
     * @param string $execution
     *
     * @return self
     *
     * Created at: 07/02/2023, 07:42:51 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setExecution(string $execution): self
    {
        $this->portefeuille = $execution;

        return $this;
    }

    /**
     * [Description for getDateModification]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:51:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    /**
     * [Description for setDateModification]
     *
     * @param \DateTimeInterface|null $dateModification
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:51:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateModification(?\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:51:25 (Europe/Paris)
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
     * Created at: 02/01/2023, 17:51:27 (Europe/Paris)
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
