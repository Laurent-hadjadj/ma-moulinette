<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\InformationProjetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InformationProjetRepository::class)]
#[ORM\Table(name: "information_projet", schema: "ma_moulinette")]
class InformationProjet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
            options: ['comment' => 'Identifiant unique pour chaque instance de InformationProjet'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank(message: "La clé Maven ne peut pas être vide.")]
    #[Assert\Length(max: 255,
            maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private $mavenKey;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Clé d’analyse du projet'])]
    #[Assert\NotBlank(message: "La clé d'analyse ne peut pas être vide.")]
    #[Assert\Length(max: 32,
        maxMessage: "La clé d'analyse ne doit pas dépasser 32 caractères.")]
    private $analyseKey;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date de l’analyse du projet'])]
    #[Assert\NotNull(message: "La date de l'analyse ne peut pas être nulle.")]
    private $date;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Version du projet lors de l’analyse'])]
    #[Assert\NotBlank(message: "La version du projet ne peut pas être vide.")]
    #[Assert\Length(max: 32,
        maxMessage: "La version du projet ne doit pas dépasser 32 caractères.")]
    private $projectVersion;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Type d’analyse effectuée'])]
    #[Assert\NotBlank(message: "Le type d'analyse ne peut pas être vide.")]
    #[Assert\Length(max: 32,
        maxMessage: "Le type d'analyse ne doit pas dépasser 32 caractères.")]
    private $type;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
    options: ['default' => 0, 'comment' => 'Nombre total de version sur le serveur SonarQube.'])]
    private int $versionSonar=0;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
    options: ['default' => 0, 'comment' => 'Nombre de version Release sur le serveur SonarQube.'])]
    private int $versionReleaseSonar=0;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
    options: ['default' => 0, 'comment' => 'Nombre de version Snapshot sur le serveur SonarQube.'])]
    private int $versionSnapshotSonar=0;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
    options: ['default' => 0, 'comment' => 'Nombre de version Autre sur le serveur SonarQube.'])]
    private int $versionAutreSonar=0;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true,
    options: ['comment' => 'Mode de collecte : [COLLECTE] | [TRAITEMENT MANUEL] | [TRAITEMENT AUTOMATIQUE]'])]
    #[Assert\Length(max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères.")]
    private ?string $modeCollecte=null;

    #[ORM\Column(type: Types::STRING, length: 320, nullable: true,
    options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Length(max: 320,
        maxMessage: "Le compte de l’utilisateur ne peut pas dépasser 320 caractères.")]
    private ?string $utilisateurCollecte=null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement de l’information du projet'])]
    #[Assert\NotNull(message: "La date d'enregistrement ne peut pas être nulle.")]
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

    public function getAnalyseKey(): ?string
    {
        return $this->analyseKey;
    }

    public function setAnalyseKey(string $analyseKey): static
    {
        $this->analyseKey = $analyseKey;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getProjectVersion(): ?string
    {
        return $this->projectVersion;
    }

    public function setProjectVersion(string $projectVersion): static
    {
        $this->projectVersion = $projectVersion;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getVersionSonar(): int
    {
        return $this->versionSonar;
    }

    public function setVersionSonar(int $versionSonar): self
    {
        $this->versionSonar = $versionSonar;
        return $this;
    }

    public function getVersionReleaseSonar(): int
    {
        return $this->versionReleaseSonar;
    }

    public function setVersionReleaseSonar(int $versionReleaseSonar): self
    {
        $this->versionReleaseSonar = $versionReleaseSonar;
        return $this;
    }

    public function getVersionSnapshotSonar(): int
    {
        return $this->versionSnapshotSonar;
    }

    public function setVersionSnapshotSonar(int $versionSnapshotSonar): self
    {
        $this->versionSnapshotSonar = $versionSnapshotSonar;
        return $this;
    }

    public function getVersionAutreSonar(): int
    {
        return $this->versionAutreSonar;
    }

    public function setVersionAutreSonar(int $versionAutreSonar): self
    {
        $this->versionAutreSonar = $versionAutreSonar;
        return $this;
    }


    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }

    public function setModeCollecte(?string $modeCollecte): static
    {
        $this->modeCollecte = $modeCollecte;

        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }

    public function setUtilisateurCollecte(?string $utilisateurCollecte): static
    {
        $this->utilisateurCollecte = $utilisateurCollecte;

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
