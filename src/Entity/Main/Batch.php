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
    #[ORM\Column(type: 'string', length: 32)]
    private $nom;

    /** Description du traitement */
    #[ORM\Column(type: 'string', length: 128)]
    private $description;

    /** Nom de l'utilisateur */
    #[ORM\Column(type: 'string', length: 128)]
    private $utilisateur;

    /** Nom du portefeuille de projet */
    #[ORM\Column(type: 'string', length: 32)]
    private $portefeuille="Aucun";

    /** Nombre de projet */
    #[ORM\Column(type: 'integer')]
    private $nombre_projet=0;

    /** Date de modification */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $date_modification;

    /** Date d'enregistrement */
    #[ORM\Column(type: 'datetime')]
    private $date_enregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(?\DateTimeInterface $date_modification): self
    {
        $this->date_modification = $date_modification;

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

    public function isStatut(): ?bool
    {
        return $this->statut;
    }

    public function setStatut(bool $statut): self
    {
        $this->statut = $statut;

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

    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    public function setPortefeuille(string $portefeuille): self
    {
        $this->portefeuille = $portefeuille;

        return $this;
    }

    public function getUtilisateur(): ?string
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(string $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }



}
