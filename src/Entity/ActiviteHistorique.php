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

use App\Repository\ActiviteHistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActiviteHistoriqueRepository::class)]
#[ORM\Table(name: "historique_activite", schema: "ma_moulinette")]
class ActiviteHistorique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique de la table historique activité']
    )]
    private int $id;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Année']
    )]
    #[Assert\NotNull]
    private int $annee;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de jours']
    )]
    #[Assert\NotNull]
    private int $nbJour;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d\'analyses']
    )]
    #[Assert\NotNull]
    private int $nbAnalyse;

    #[ORM\Column(
        type: Types::FLOAT,
        nullable: false,
        options: ['comment' => 'Moyenne des analyses']
    )]
    #[Assert\NotNull]
    private int $moyenneAnalyse;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de réussites']
    )]
    #[Assert\NotNull]
    private int $nbReussi;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre d\'échecs']
    )]
    #[Assert\NotNull]
    private int $nbEchec;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Taux de réussite']
    )]
    #[Assert\NotNull]
    private float $tauxReussite;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Temps maximal']
    )]
    #[Assert\NotNull]
    private int $maxTemps;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date et heure d\'enregistrement']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnnee(): ?int
    {
        return $this->annee;
    }

    public function setAnnee(int $annee): static
    {
        $this->annee = $annee;
        return $this;
    }

    public function getNbJour(): ?int
    {
        return $this->nbJour;
    }

    public function setNbJour(int $nbJour): static
    {
        $this->nbJour = $nbJour;
        return $this;
    }

    public function getNbAnalyse(): ?int
    {
        return $this->nbAnalyse;
    }

    public function setNbAnalyse(int $nbAnalyse): static
    {
        $this->nbAnalyse = $nbAnalyse;
        return $this;
    }

    public function getMoyenneAnalyse(): ?int
    {
        return $this->moyenneAnalyse;
    }

    public function setMoyenneAnalyse(int $moyenneAnalyse): static
    {
        $this->moyenneAnalyse = $moyenneAnalyse;
        return $this;
    }

    public function getNbReussi(): ?int
    {
        return $this->nbReussi;
    }

    public function setNbReussi(int $nbReussi): static
    {
        $this->nbReussi = $nbReussi;
        return $this;
    }

    public function getNbEchec(): ?int
    {
        return $this->nbEchec;
    }

    public function setNbEchec(int $nbEchec): static
    {
        $this->nbEchec = $nbEchec;
        return $this;
    }

    public function getTauxReussite(): ?float
    {
        return $this->tauxReussite;
    }

    public function setTauxReussite(float $tauxReussite): static
    {
        $this->tauxReussite = $tauxReussite;
        return $this;
    }

    public function getMaxTemps(): ?int
    {
        return $this->maxTemps;
    }

    public function setMaxTemps(int $maxTemps): static
    {
        $this->maxTemps = $maxTemps;
        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): static
    {
        $this->dateEnregistrement = $dateEnregistrement;
        return $this;
    }
}
