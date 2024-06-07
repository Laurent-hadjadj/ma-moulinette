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

use App\Repository\PortefeuilleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use App\Validator as AcmeAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PortefeuilleRepository::class)]
#[ORM\Table(name: "portefeuille", schema: "ma_moulinette")]
class Portefeuille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque portefeuille'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, unique: true,
        options: ['comment' => 'Titre unique du portefeuille'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32,
        maxMessage: "Le titre ne peut pas dépasser 32 caractères.")]
    #[AcmeAssert\ContainsPortefeuilleUnique]
    //AcmeAssert\ContainsPortefeuilleUnique : Une validation personnalisée pour s'assurer que chaque titre de portefeuille est unique dans la base de données, prévenant les doublons.
    private $titre;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Nom de l’équipe associée au portefeuille'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32,
        maxMessage: "Le nom de l'équipe ne peut pas dépasser 32 caractères.")]
    private $equipe;

    #[ORM\Column(type: 'json',
        options: ['comment' => 'Liste des éléments ou des activités du portefeuille'])]
    private ?array $liste = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,
        options: ['comment' => 'Date de la dernière modification du portefeuille'])]
    private $dateModification;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE,  nullable: false,
        options: ['comment' => 'Date d’enregistrement du portefeuille'])]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getEquipe(): ?string
    {
        return $this->equipe;
    }

    public function setEquipe(string $equipe): static
    {
        $this->equipe = $equipe;

        return $this;
    }

    public function getListe(): array
    {
        return $this->liste;
    }

    public function setListe(array $liste): static
    {
        $this->liste = $liste;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;

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
