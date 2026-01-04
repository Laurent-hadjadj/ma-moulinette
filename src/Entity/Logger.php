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

use App\Repository\LoggerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LoggerRepository::class)]
#[ORM\Table(
    name: 'logger',
    schema: 'ma_moulinette', )]
class Logger
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'ID unique pour la table logger'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank(message: "La clé Maven ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private string $mavenKey;

    #[ORM\Column(
        name: 'logger_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Logger de type Info'])]
    #[Assert\NotNull(message: "Le logger Info ne peut pas être nul.")]
    private int $loggerInfo;

    #[ORM\Column(
        name: 'logger_warn',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Logger de type Warn'])]
    #[Assert\NotNull(message: "Le logger Warn ne peut pas être nul.")]
    private int $loggerWarn;

    #[ORM\Column(
        name: 'logger_error',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Logger de type Error'])]
    #[Assert\NotNull(message: "Le logger Error ne peut pas être nul.")]
    private int $loggerError;

    #[ORM\Column(
        name: 'logger_debug',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Logger de type Debug'])]
    #[Assert\NotNull(message: "Le logger Debug ne peut pas être nul.")]
    private int $loggerDebug;

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : [COLLECTE] | [TRAITEMENT MANUEL] | [TRAITEMENT AUTOMATIQUE]'])]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères.")]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Length(
        max: 320,
        maxMessage: "Le nom de l'utilisateur ne peut pas dépasser 320 caractères.")]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement de la tâche dans le système'])]
    #[Assert\NotNull(message: "La date d'enregistrement ne peut pas être nulle.")]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct(
        string $mavenKey,
        int $loggerInfo,
        int $loggerWarn,
        int $loggerError,
        int $loggerDebug,
        ?string $modeCollecte = null,
        ?string $utilisateurCollecte = null
    ) {
        $this->mavenKey = $mavenKey;
        $this->loggerInfo = $loggerInfo;
        $this->loggerWarn = $loggerWarn;
        $this->loggerError = $loggerError;
        $this->loggerDebug = $loggerDebug;
        $this->modeCollecte = $modeCollecte;
        $this->utilisateurCollecte = $utilisateurCollecte;
        $this->dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    // Getters et setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;
        return $this;
    }

    public function getLoggerInfo(): int
    {
        return $this->loggerInfo;
    }

    public function setLoggerInfo(int $loggerInfo): self
    {
        $this->loggerInfo = $loggerInfo;
        return $this;
    }

    public function getLoggerWarn(): int
    {
        return $this->loggerWarn;
    }

    public function setLoggerWarn(int $loggerWarn): self
    {
        $this->loggerWarn = $loggerWarn;
        return $this;
    }

    public function getLoggerError(): int
    {
        return $this->loggerError;
    }

    public function setLoggerError(int $loggerError): self
    {
        $this->loggerError = $loggerError;
        return $this;
    }

    public function getLoggerDebug(): int
    {
        return $this->loggerDebug;
    }

    public function setLoggerDebug(int $loggerDebug): self
    {
        $this->loggerDebug = $loggerDebug;
        return $this;
    }

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }

    public function setModeCollecte(?string $modeCollecte): self
    {
        $this->modeCollecte = $modeCollecte;
        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }

    public function setUtilisateurCollecte(?string $utilisateurCollecte): self
    {
        $this->utilisateurCollecte = $utilisateurCollecte;
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
