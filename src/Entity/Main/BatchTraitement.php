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

use App\Repository\Main\BatchTraitementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BatchTraitementRepository::class)]
class BatchTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /** Démarage ? Manuel ou automatique */
    #[ORM\Column(type: 'string', length: 16, )]
    private $demarrage="Manuel";

    /** Résultat  */
    #[ORM\Column(type: 'boolean' )]
    private $resultat=0;

    /** Nom du traitement */
    #[ORM\Column(type: 'string', length: 32)]
    private $titre;

    /** Nom du portefeuille de projet */
    #[ORM\Column(type: 'string', length: 32)]
    private $portefeuille="Aucun";

    /** Nombre de projet */
    #[ORM\Column(type: 'integer')]
    private $nombreProjet=0;

    #[ORM\Column(type: 'string', length: 128)]
    private $responsable;

    /** Debut du traitement */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $debutTraitement;

    /** Fin du traitement */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $finTraitement;

    /** Date d'enregistrement */
    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:51:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * [Description for getDemarrage]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:51:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDemarrage(): ?string
    {
        return $this->demarrage;
    }

    /**
     * [Description for setDemarrage]
     *
     * @param string $demarrage
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:51:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDemarrage(string $demarrage): self
    {
        $this->demarrage = $demarrage;

        return $this;
    }

    /**
     * [Description for isResultat]
     *
     * @return bool|null
     *
     * Created at: 02/01/2023, 17:51:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isResultat(): ?bool
    {
        return $this->resultat;
    }

    /**
     * [Description for setResultat]
     *
     * @param bool $resultat
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setResultat(bool $resultat): self
    {
        $this->resultat = $resultat;

        return $this;
    }

    /**
     * [Description for getTitre]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:52:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 17:52:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * [Description for getPortefeuille]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:52:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 17:52:21 (Europe/Paris)
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
     * Created at: 02/01/2023, 17:52:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 17:52:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreProjet(int $nombreProjet): self
    {
        $this->nombreProjet = $nombreProjet;

        return $this;
    }

    /**
     * [Description for getResponsable]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:52:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 17:52:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setResponsable(string $responsable): self
    {
        $this->responsable = $responsable;

        return $this;
    }

    /**
     * [Description for getDebutTraitement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:52:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDebutTraitement(): ?\DateTimeInterface
    {
        return $this->debutTraitement;
    }

    /**
     * [Description for setDebutTraitement]
     *
     * @param \DateTimeInterface|null $debutTraitement
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDebutTraitement(?\DateTimeInterface $debutTraitement): self
    {
        $this->debutTraitement = $debutTraitement;

        return $this;
    }

    /**
     * [Description for getFinTraitement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:52:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getFinTraitement(): ?\DateTimeInterface
    {
        return $this->finTraitement;
    }

    /**
     * [Description for setFinTraitement]
     *
     * @param \DateTimeInterface|null $finTraitement
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setFinTraitement(?\DateTimeInterface $finTraitement): self
    {
        $this->finTraitement = $finTraitement;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:52:38 (Europe/Paris)
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
     * Created at: 02/01/2023, 17:52:39 (Europe/Paris)
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
