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

use App\Repository\Main\BatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use App\Validator as AcmeAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BatchRepository::class)]
class Batch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /** Statut d'activité du traitement */
    #[ORM\Column(type: 'boolean' )]
    private $statut=false;

    /** Nom du traitement */
    #[ORM\Column(type: 'string', length: 32, unique: true)]
    #[AcmeAssert\ContainsBatchUnique()]
    private $titre;

    /** Description du traitement */
    #[ORM\Column(type: 'string', length: 128)]
    private $description;

    /** Nom de l'utilisateur */
    #[ORM\Column(type: 'string', length: 128)]
    private $responsable;


    /** Nom du portefeuille de projet */
    #[ORM\Column(type: 'string', length: 32, unique: true)]
    #[AcmeAssert\ContainsBatchUnique()]
    private $portefeuille="Aucun";

    /** Nombre de projet */
    #[ORM\Column(type: 'integer')]
    private $nombreProjet=0;

    /** Etat d'éxection (start, pending, error, end) */
    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    private $execution;

    /** Date de modification */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateModification;

    /** Date d'enregistrement */
    #[ORM\Column(type: 'datetime')]
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
