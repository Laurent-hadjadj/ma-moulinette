<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\MaMoulinetteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaMoulinetteRepository::class)]
#[ORM\Table(
    name: 'ma_moulinette',
    schema: "ma_moulinette",
    options: ['comment' => "Table des version de l'application ma_moulinette"])]
class MaMoulinette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Clé unique'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'version',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => "Numéro de la version de l'application MaMoulinette"])]
    #[Assert\NotBlank(message: "La version ne peut pas être vide.")]
    #[Assert\Length(
        max: 16,
        maxMessage: "La version ne doit pas dépasser 16 caractères.")]
    private string $version;

    #[ORM\Column(
        name: 'date_version',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de création'])]
    #[Assert\NotNull(message: "La date de création ne peut pas être nulle.")]
    private \DateTimeImmutable $dateVersion;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date d'enregistrement"])]
    #[Assert\NotNull(message: "La date d'enregistrement ne peut pas être nulle.")]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct(string $version)
    {
        $this->version = $version;
        $this->dateVersion = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $this->dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    // Getters et setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getDateVersion(): \DateTimeImmutable
    {
        return $this->dateVersion;
    }

    public function setDateVersion(\DateTimeImmutable $dateVersion): self
    {
        $this->dateVersion = $dateVersion;
        return $this;
    }

    public function getDateEnregistrement(): \DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;
        return $this;
    }
}
