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

use App\Repository\AnomalieDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnomalieDetailsRepository::class)]
#[ORM\Table(name: "anomalie_details", schema: "ma_moulinette")]
class AnomalieDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour la table Anomalie Détails']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères."
    )]
    private $mavenKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom ']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom ne doit pas dépasser 128 caractères."
    )]
    private $name;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bugs bloquants']
    )]
    #[Assert\NotNull]
    private $bugBlocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bugs critiques']
    )]
    #[Assert\NotNull]
    private $bugCritical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bugs d’information']
    )]
    #[Assert\NotNull]
    private $bugInfo;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bugs majeurs']
    )]
    #[Assert\NotNull]
    private $bugMajor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bugs mineurs']
    )]
    #[Assert\NotNull]
    private $bugMinor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités bloquantes']
    )]
    #[Assert\NotNull]
    private $vulnerabilityBlocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités critiques']
    )]
    #[Assert\NotNull]
    private $vulnerabilityCritical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités d’information']
    )]
    #[Assert\NotNull]
    private $vulnerabilityInfo;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités majeures']
    )]
    #[Assert\NotNull]
    private $vulnerabilityMajor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités mineures']
    )]
    #[Assert\NotNull]
    private $vulnerabilityMinor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques bloquantes']
    )]
    #[Assert\NotNull]
    private $codeSmellBlocker;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques critiques']
    )]
    #[Assert\NotNull]
    private $codeSmellCritical;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques d’information']
    )]
    #[Assert\NotNull]
    private $codeSmellInfo;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques majeures']
    )]
    #[Assert\NotNull]
    private $codeSmellMajor;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques mineures']
    )]
    #[Assert\NotNull]
    private $codeSmellMinor;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement des détails de l’anomalie']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): static
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBugBlocker(): ?int
    {
        return $this->bugBlocker;
    }

    public function setBugBlocker(int $bugBlocker): static
    {
        $this->bugBlocker = $bugBlocker;

        return $this;
    }

    public function getBugCritical(): ?int
    {
        return $this->bugCritical;
    }

    public function setBugCritical(int $bugCritical): static
    {
        $this->bugCritical = $bugCritical;

        return $this;
    }

    public function getBugInfo(): ?int
    {
        return $this->bugInfo;
    }

    public function setBugInfo(int $bugInfo): static
    {
        $this->bugInfo = $bugInfo;

        return $this;
    }

    public function getBugMajor(): ?int
    {
        return $this->bugMajor;
    }

    public function setBugMajor(int $bugMajor): static
    {
        $this->bugMajor = $bugMajor;

        return $this;
    }

    public function getBugMinor(): ?int
    {
        return $this->bugMinor;
    }

    public function setBugMinor(int $bugMinor): static
    {
        $this->bugMinor = $bugMinor;

        return $this;
    }

    public function getVulnerabilityBlocker(): ?int
    {
        return $this->vulnerabilityBlocker;
    }

    public function setVulnerabilityBlocker(int $vulnerabilityBlocker): static
    {
        $this->vulnerabilityBlocker = $vulnerabilityBlocker;

        return $this;
    }

    public function getVulnerabilityCritical(): ?int
    {
        return $this->vulnerabilityCritical;
    }

    public function setVulnerabilityCritical(int $vulnerabilityCritical): static
    {
        $this->vulnerabilityCritical = $vulnerabilityCritical;

        return $this;
    }

    public function getVulnerabilityInfo(): ?int
    {
        return $this->vulnerabilityInfo;
    }

    public function setVulnerabilityInfo(int $vulnerabilityInfo): static
    {
        $this->vulnerabilityInfo = $vulnerabilityInfo;

        return $this;
    }

    public function getVulnerabilityMajor(): ?int
    {
        return $this->vulnerabilityMajor;
    }

    public function setVulnerabilityMajor(int $vulnerabilityMajor): static
    {
        $this->vulnerabilityMajor = $vulnerabilityMajor;

        return $this;
    }

    public function getVulnerabilityMinor(): ?int
    {
        return $this->vulnerabilityMinor;
    }

    public function setVulnerabilityMinor(int $vulnerabilityMinor): static
    {
        $this->vulnerabilityMinor = $vulnerabilityMinor;

        return $this;
    }

    public function getCodeSmellBlocker(): ?int
    {
        return $this->codeSmellBlocker;
    }

    public function setCodeSmellBlocker(int $codeSmellBlocker): static
    {
        $this->codeSmellBlocker = $codeSmellBlocker;

        return $this;
    }

    public function getCodeSmellCritical(): ?int
    {
        return $this->codeSmellCritical;
    }

    public function setCodeSmellCritical(int $codeSmellCritical): static
    {
        $this->codeSmellCritical = $codeSmellCritical;

        return $this;
    }

    public function getCodeSmellInfo(): ?int
    {
        return $this->codeSmellInfo;
    }

    public function setCodeSmellInfo(int $codeSmellInfo): static
    {
        $this->codeSmellInfo = $codeSmellInfo;

        return $this;
    }

    public function getCodeSmellMajor(): ?int
    {
        return $this->codeSmellMajor;
    }

    public function setCodeSmellMajor(int $codeSmellMajor): static
    {
        $this->codeSmellMajor = $codeSmellMajor;

        return $this;
    }

    public function getCodeSmellMinor(): ?int
    {
        return $this->codeSmellMinor;
    }

    public function setCodeSmellMinor(int $codeSmellMinor): static
    {
        $this->codeSmellMinor = $codeSmellMinor;

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
