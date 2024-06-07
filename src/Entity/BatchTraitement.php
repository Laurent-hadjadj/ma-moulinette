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

use App\Repository\BatchTraitementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BatchTraitementRepository::class)]
#[ORM\Table(name: "batch_traitement", schema: "ma_moulinette")]
class BatchTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique pour la table Batch Traitemeny'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: false,
        options: ['comment' => 'Mode de démarrage du traitement'])]
    ##[Assert\Choice(choices: ["Manuel", "Automatique"], message: "Le démarrage doit être 'Manuel' ou 'Automatique'")]
    #[Assert\NotBlank]
    private $demarrage = "Manuel";

    #[ORM\Column(type: 'boolean', nullable: false,
        options: ['comment' => 'Indique si le traitement a réussi ou échoué'])]
    #[Assert\Type(type: 'bool',
        message: "Le résultat doit être un booléen.")]
    #[Assert\NotNull]
    private $resultat = false;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Titre du traitement'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32,
        maxMessage: "Le titre ne doit pas dépasser 32 caractères.")]
    private $titre;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Nom du portefeuille de projets associé'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32,
        maxMessage: "Le nom du portefeuille ne doit pas dépasser 32 caractères.")]
    private $portefeuille = "Aucun";

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Nombre de projets traités'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'integer',
        message: "Le nombre de projets doit être un entier.")]
    private $nombreProjet = 0;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => 'Responsable du traitement'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom du responsable ne doit pas dépasser 128 caractères.")]
    private $responsable;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true,
        options: ['comment' => 'Date et heure de début du traitement'])]
    #[Assert\NotNull]
    private $debutTraitement;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true,
        options: ['comment' => 'Date et heure de fin du traitement'])]
    #[Assert\NotNull]
    private $finTraitement;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement du traitement dans le système'])]
    #[Assert\NotNull]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemarrage(): ?string
    {
        return $this->demarrage;
    }

    public function setDemarrage(string $demarrage): static
    {
        $this->demarrage = $demarrage;

        return $this;
    }

    public function isResultat(): ?bool
    {
        return $this->resultat;
    }

    public function setResultat(bool $resultat): static
    {
        $this->resultat = $resultat;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    public function setPortefeuille(string $portefeuille): static
    {
        $this->portefeuille = $portefeuille;

        return $this;
    }

    public function getNombreProjet(): ?int
    {
        return $this->nombreProjet;
    }

    public function setNombreProjet(int $nombreProjet): static
    {
        $this->nombreProjet = $nombreProjet;

        return $this;
    }

    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    public function setResponsable(string $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

    public function getDebutTraitement(): ?\DateTimeImmutable
    {
        return $this->debutTraitement;
    }

    public function setDebutTraitement(?\DateTimeImmutable $debutTraitement): static
    {
        $this->debutTraitement = $debutTraitement;

        return $this;
    }

    public function getFinTraitement(): ?\DateTimeImmutable
    {
        return $this->finTraitement;
    }

    public function setFinTraitement(?\DateTimeImmutable $finTraitement): static
    {
        $this->finTraitement = $finTraitement;

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
