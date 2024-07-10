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

use App\Repository\LoggerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoggerRepository::class)]
#[ORM\Table(name: "logger", schema: "ma_moulinette")]
class Logger
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'ID unique pour la table logger'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private $mavenKey;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Logger de type Info'])]
    #[Assert\NotNull]
    private $loggerInfo;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Logger de type Warn'])]
    #[Assert\NotNull]
    private $loggerWarn;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Logger de type Error'])]
    #[Assert\NotNull]
    private $loggerError;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Logger de type Debug'])]
    #[Assert\NotNull]
    private $loggerDebug;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true,
    options: ['comment' => 'Mode de collete : MANUEL | AUTOMATIQUE'])]
    #[Assert\Length(max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères.")]
    private ?string $modeCollecte=null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true,
    options: ['comment' => "Nom de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom de l'utilisatzeur ne peut pas dépasser 128 caractères.")]
    private ?string $utilisateurCollecte=null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement de la tâche dans le système'])]
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

    public function getLoggerInfo(): ?int
    {
        return $this->loggerInfo;
    }

    public function setLoggerInfo(int $loggerInfo): static
    {
        $this->loggerInfo = $loggerInfo;

        return $this;
    }

    public function getLoggerWarn(): ?int
    {
        return $this->loggerWarn;
    }

    public function setLoggerWarn(int $loggerWarn): static
    {
        $this->loggerWarn = $loggerWarn;

        return $this;
    }

    public function getLoggerError(): ?int
    {
        return $this->loggerError;
    }

    public function setLoggerError(int $loggerError): static
    {
        $this->loggerError = $loggerError;

        return $this;
    }

    public function getLoggerDebug(): ?int
    {
        return $this->loggerDebug;
    }

    public function setLoggerDebug(int $loggerDebug): static
    {
        $this->loggerDebug = $loggerDebug;

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
