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
    private $resultat=false;

    /** Nom du traitement */
    #[ORM\Column(type: 'string', length: 32)]
    private $titre;

    /** Nom du portefeuille de projet */
    #[ORM\Column(type: 'string', length: 32)]
    private $portefeuille="Aucun";

    /** Nombre de projet */
    #[ORM\Column(type: 'integer')]
    private $nombre_projet=0;

    #[ORM\Column(type: 'string', length: 128)]
    private $responsable;

    /** Durée */
    #[ORM\Column(type: 'integer')]
    private $temp_execution;

    /** Date d'enregistrement */
    #[ORM\Column(type: 'datetime')]
    private $date_enregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemarrage(): ?string
    {
        return $this->demarrage;
    }

    public function setDemarrage(string $demarrage): self
    {
        $this->demarrage = $demarrage;

        return $this;
    }

    public function isResultat(): ?bool
    {
        return $this->resultat;
    }

    public function setResultat(bool $resultat): self
    {
        $this->resultat = $resultat;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    public function setPortefeuille(string $portefeuille): self
    {
        $this->portefeuille = $portefeuille;

        return $this;
    }

    public function getNombreProjet(): ?int
    {
        return $this->nombre_projet;
    }

    public function setNombreProjet(int $nombre_projet): self
    {
        $this->nombre_projet = $nombre_projet;

        return $this;
    }

    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    public function setResponsable(string $responsable): self
    {
        $this->responsable = $responsable;

        return $this;
    }

    public function getTempExecution(): ?int
    {
        return $this->temp_execution;
    }

    public function setTempExecution(int $temp_execution): self
    {
        $this->temp_execution = $temp_execution;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeInterface
    {
        return $this->date_enregistrement;
    }

    public function setDateEnregistrement(\DateTimeInterface $date_enregistrement): self
    {
        $this->date_enregistrement = $date_enregistrement;

        return $this;
    }

}
