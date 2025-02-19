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

use App\Repository\RepartitionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RepartitionRepository::class)]
#[ORM\Table(name: 'repartition', schema: 'ma_moulinette')]
class Repartition
{
    /**
     * ID unique pour chaque répartition.
    */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'ID unique pour chaque répartition'])]
    private ?int $id = null;

    /**
     * Clé identification du projet.
    */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false,
        options: ['comment' => 'Clé identification du projet'])]
    #[Assert\NotBlank()]
    private string $mavenKey;

    /**
     * Nom de l’application.
    */
    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => 'Nom de l’application'])]
    #[Assert\NotBlank()]
    private string $name;

    /**
     * Nombre de bug bloquant.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug bloquant'])]
    private int $bugBlocker = 0;

    /**
     * Nombre de bug critique.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug critique'])]
    private int $bugCritical = 0;

    /**
     * Nombre de bug majeur.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug majeur'])]
    private int $bugMajor = 0;

    /**
     * Nombre de bug mineur.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug mineur'])]
    private int $bugMinor = 0;

    /**
     * Nombre de bug info.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug info'])]
    private int $bugInfo = 0;

    /**
     * Nombre de vulnérabilité bloquante.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité bloquante'])]
    private int $vulnerabilityBlocker = 0;

    /**
     * Nombre de vulnérabilité critique.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité critique'])]
    private int $vulnerabilityCritical = 0;

    /**
     * Nombre de vulnérabilité majeure.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité majeure'])]
    private int $vulnerabilityMajor = 0;

    /**
     * Nombre de vulnérabilité mineure.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité mineure'])]
    private int $vulnerabilityMinor = 0;

    /**
     * Nombre de vulnérabilité en info.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité en info'])]
    private int $vulnerabilityInfo = 0;

    /**
     * Nombre de mauvaise pratique bloquante.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique bloquante'])]
    private int $codeSmellBlocker = 0;

    /**
     * Nombre de mauvaise pratique critique.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique critique'])]
    private int $codeSmellCritical = 0;

    /**
     * Nombre de mauvaise pratique majeure.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique majeure'])]
    private int $codeSmellMajor = 0;

    /**
     * Nombre de mauvaise pratique mineure.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique mineure'])]
    private int $codeSmellMinor = 0;

    /**
     * Nombre de mauvaise pratique en info.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique en info'])]
    private int $codeSmellInfo = 0;

    /**
     * Répartition des anomalies de l’application frontend.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => "Répartition des anomalies de l’application frontend"])]
    private int $frontend = 0;

    // FRONTEND
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug bloquant (frontend)'])]
    private int $frontendBugBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug critique (frontend)'])]
    private int $frontendBugCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug majeur (frontend)'])]
    private int $frontendBugMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug mineur (frontend)'])]
    private int $frontendBugMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug informatif (frontend)'])]
    private int $frontendBugInfo = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité bloquante (frontend)'])]
    private int $frontendVulnerabilityBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité critique (frontend)'])]
    private int $frontendVulnerabilityCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité majeure (frontend)'])]
    private int $frontendVulnerabilityMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité mineure (frontend)'])]
    private int $frontendVulnerabilityMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité informative (frontend)'])]
    private int $frontendVulnerabilityInfo = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de code smell bloquant (frontend)'])]
    private int $frontendCodeSmellBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de code smell critique (frontend)'])]
    private int $frontendCodeSmellCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de code smell majeur (frontend)'])]
    private int $frontendCodeSmellMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de code smell mineur (frontend)'])]
    private int $frontendCodeSmellMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de code smell informatif (frontend)'])]
    private int $frontendCodeSmellInfo = 0;

    /**
     * Répartition des anomalies de l’application backend.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => "Répartition des anomalies de l’application backend"])]
    private int $backend = 0;

    // BACKEND
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug bloquant (backend)'])]
    private int $backendBugBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug critique (backend)'])]
    private int $backendBugCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug majeur (backend)'])]
    private int $backendBugMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug mineur (backend)'])]
    private int $backendBugMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug informatif (backend)'])]
    private int $backendBugInfo = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité bloquante (backend)'])]
    private int $backendVulnerabilityBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité critique (backend)'])]
    private int $backendVulnerabilityCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité majeure (backend)'])]
    private int $backendVulnerabilityMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité mineure (backend)'])]
    private int $backendVulnerabilityMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité informative (backend)'])]
    private int $backendVulnerabilityInfo = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique bloquant (backend)'])]
    private int $backendCodeSmellBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique critique (backend)'])]
    private int $backendCodeSmellCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique majeur (backend)'])]
    private int $backendCodeSmellMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique mineur (backend)'])]
    private int $backendCodeSmellMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique informatif (backend)'])]
    private int $backendCodeSmellInfo = 0;

    /**
     * Répartition des anomalies de l’application autres.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => "Répartition des anomalies de l’application autres"])]
    private int $autre = 0;

    // AUTRE
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug bloquant (autre)'])]
    private int $autreBugBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug critique (autre)'])]
    private int $autreBugCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug majeur (autre)'])]
    private int $autreBugMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug mineur (autre)'])]
    private int $autreBugMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug informatif (autre)'])]
    private int $autreBugInfo = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité bloquante (autre)'])]
    private int $autreVulnerabilityBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité critique (autre)'])]
    private int $autreVulnerabilityCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité majeure (autre)'])]
    private int $autreVulnerabilityMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité mineure (autre)'])]
    private int $autreVulnerabilityMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité informative (autre)'])]
    private int $autreVulnerabilityInfo = 0;

    #[ORM\Column(type: Types::INTEGER,
    options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique bloquant (autre)'])]
    private int $autreCodeSmellBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique critique (autre)'])]
    private int $autreCodeSmellCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique majeur (autre)'])]
    private int $autreCodeSmellMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique mineur (autre)'])]
    private int $autreCodeSmellMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique informatif (autre)'])]
    private int $autreCodeSmellInfo = 0;

    /**
     * Répartition des anomalies de l’application non définies.
    */
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => "Répartition des anomalies de l’application non définies"])]
    private int $inconnue = 0;

    // INCONNUE
    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug bloquant (inconnue)'])]
    private int $inconnueBugBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug critique (inconnue)'])]
    private int $inconnueBugCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug majeur (inconnue)'])]
    private int $inconnueBugMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug mineur (inconnue)'])]
    private int $inconnueBugMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de bug informatif (inconnue)'])]
    private int $inconnueBugInfo = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité bloquante (inconnue)'])]
    private int $inconnueVulnerabilityBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité critique (inconnue)'])]
    private int $inconnueVulnerabilityCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité majeure (inconnue)'])]
    private int $inconnueVulnerabilityMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité mineure (inconnue)'])]
    private int $inconnueVulnerabilityMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de vulnérabilité informative (inconnue)'])]
    private int $inconnueVulnerabilityInfo = 0;

    #[ORM\Column(type: Types::INTEGER,
    options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique bloquant (inconnue)'])]
    private int $inconnueCodeSmellBlocker = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique critique (inconnue)'])]
    private int $inconnueCodeSmellCritical = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique majeur (inconnue)'])]
    private int $inconnueCodeSmellMajor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique mineur (inconnue)'])]
    private int $inconnueCodeSmellMinor = 0;

    #[ORM\Column(type: Types::INTEGER,
        options: ['default' => 0, 'comment' => 'Nombre de mauvaise pratique informatif (inconnue)'])]
    private int $inconnueCodeSmellInfo = 0;

    /**
     * Timestamp en milliseconde unique pour chaque analyse.
    */
    #[ORM\Column(type: Types::BIGINT,
        nullable: false, options: ['comment' => 'Timestamp en milliseconde unique pour chaque analyse'])]
    #[Assert\NotBlank()]
    private int $setup;

    /**
     * Timestamp en milliseconde unique pour chaque analyse.
    */
    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Avancement du processus: initial, collecte, analyse'])]
    #[Assert\NotBlank()]
    private string $control ='initial';

    /**
     * Mode de collecte : collecte, traitement manuel ou traitement automatique.
    */
    #[ORM\Column(type: Types::STRING, length: 32, nullable: true,
        options: ['comment' => 'Mode de collecte : collecte, traitement manuel ou traitement automatique'])]
    private ?string $modeCollecte = null;

    /**
     * Compte de l’utilisateur qui a réalisé la collecte.
    */
    #[ORM\Column(type: Types::STRING, length: 320, nullable: true,
        options: ['comment' => 'Compte de l’utilisateur qui a réalisé la collecte'])]
    private ?string $utilisateurCollecte = null;

    /**
     * Date d’enregistrement de la répartition dans le système.
    */
    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => "Date d’enregistrement de la répartition dans le système"])]
    #[Assert\NotBlank()]
    private \DateTimeImmutable $dateEnregistrement;

    // --- Getters et Setters ---

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getBugBlocker(): int
    {
        return $this->bugBlocker;
    }

    public function setBugBlocker(int $bugBlocker): self
    {
        $this->bugBlocker = $bugBlocker;
        return $this;
    }

    public function getBugCritical(): int
    {
        return $this->bugCritical;
    }

    public function setBugCritical(int $bugCritical): self
    {
        $this->bugCritical = $bugCritical;
        return $this;
    }

    public function getBugMajor(): int
    {
        return $this->bugMajor;
    }

    public function setBugMajor(int $bugMajor): self
    {
        $this->bugMajor = $bugMajor;
        return $this;
    }

    public function getBugMinor(): int
    {
        return $this->bugMinor;
    }

    public function setBugMinor(int $bugMinor): self
    {
        $this->bugMinor = $bugMinor;
        return $this;
    }

    public function getBugInfo(): int
    {
        return $this->bugInfo;
    }

    public function setBugInfo(int $bugInfo): self
    {
        $this->bugInfo = $bugInfo;
        return $this;
    }

    public function getVulnerabilityBlocker(): int
    {
        return $this->vulnerabilityBlocker;
    }

    public function setVulnerabilityBlocker(int $vulnerabilityBlocker): self
    {
        $this->vulnerabilityBlocker = $vulnerabilityBlocker;
        return $this;
    }

    public function getVulnerabilityCritical(): int
    {
        return $this->vulnerabilityCritical;
    }

    public function setVulnerabilityCritical(int $vulnerabilityCritical): self
    {
        $this->vulnerabilityCritical = $vulnerabilityCritical;
        return $this;
    }

    public function getVulnerabilityMajor(): int
    {
        return $this->vulnerabilityMajor;
    }

    public function setVulnerabilityMajor(int $vulnerabilityMajor): self
    {
        $this->vulnerabilityMajor = $vulnerabilityMajor;
        return $this;
    }

    public function getVulnerabilityMinor(): int
    {
        return $this->vulnerabilityMinor;
    }

    public function setVulnerabilityMinor(int $vulnerabilityMinor): self
    {
        $this->vulnerabilityMinor = $vulnerabilityMinor;
        return $this;
    }

    public function getVulnerabilityInfo(): int
    {
        return $this->vulnerabilityInfo;
    }

    public function setVulnerabilityInfo(int $vulnerabilityInfo): self
    {
        $this->vulnerabilityInfo = $vulnerabilityInfo;
        return $this;
    }

    public function getCodeSmellBlocker(): int
    {
        return $this->codeSmellBlocker;
    }

    public function setCodeSmellBlocker(int $codeSmellBlocker): self
    {
        $this->codeSmellBlocker = $codeSmellBlocker;
        return $this;
    }

    public function getCodeSmellCritical(): int
    {
        return $this->codeSmellCritical;
    }

    public function setCodeSmellCritical(int $codeSmellCritical): self
    {
        $this->codeSmellCritical = $codeSmellCritical;
        return $this;
    }

    public function getCodeSmellMajor(): int
    {
        return $this->codeSmellMajor;
    }

    public function setCodeSmellMajor(int $codeSmellMajor): self
    {
        $this->codeSmellMajor = $codeSmellMajor;
        return $this;
    }

    public function getCodeSmellMinor(): int
    {
        return $this->codeSmellMinor;
    }

    public function setCodeSmellMinor(int $codeSmellMinor): self
    {
        $this->codeSmellMinor = $codeSmellMinor;
        return $this;
    }

    public function getCodeSmellInfo(): int
    {
        return $this->codeSmellInfo;
    }

    public function setCodeSmellInfo(int $codeSmellInfo): self
    {
        $this->codeSmellInfo = $codeSmellInfo;
        return $this;
    }

    public function getFrontend(): int
    {
        return $this->frontend;
    }

    public function setFrontend(int $frontend): self
    {
        $this->frontend = $frontend;
        return $this;
    }

    // FRONTEND
    public function getFrontendBugBlocker(): int
    {
        return $this->frontendBugBlocker;
    }

    public function setFrontendBugBlocker(int $frontendBugBlocker): self
    {
        $this->frontendBugBlocker = $frontendBugBlocker;
        return $this;
    }

    public function getFrontendBugCritical(): int
    {
        return $this->frontendBugCritical;
    }

    public function setFrontendBugCritical(int $frontendBugCritical): self
    {
        $this->frontendBugCritical = $frontendBugCritical;
        return $this;
    }

    public function getFrontendBugMajor(): int
    {
        return $this->frontendBugMajor;
    }

    public function setFrontendBugMajor(int $frontendBugMajor): self
    {
        $this->frontendBugMajor = $frontendBugMajor;
        return $this;
    }

    public function getFrontendBugMinor(): int
    {
        return $this->frontendBugMinor;
    }

    public function setFrontendBugMinor(int $frontendBugMinor): self
    {
        $this->frontendBugMinor = $frontendBugMinor;
        return $this;
    }

    public function getFrontendBugInfo(): int
    {
        return $this->frontendBugInfo;
    }

    public function setFrontendBugInfo(int $frontendBugInfo): self
    {
        $this->frontendBugInfo = $frontendBugInfo;
        return $this;
    }

    public function getFrontendVulnerabilityBlocker(): int
    {
        return $this->frontendVulnerabilityBlocker;
    }

    public function setFrontendVulnerabilityBlocker(int $frontendVulnerabilityBlocker): self
    {
        $this->frontendVulnerabilityBlocker = $frontendVulnerabilityBlocker;
        return $this;
    }

    public function getFrontendVulnerabilityCritical(): int
    {
        return $this->frontendVulnerabilityCritical;
    }

    public function setFrontendVulnerabilityCritical(int $frontendVulnerabilityCritical): self
    {
        $this->frontendVulnerabilityCritical = $frontendVulnerabilityCritical;
        return $this;
    }

    public function getFrontendVulnerabilityMajor(): int
    {
        return $this->frontendVulnerabilityMajor;
    }

    public function setFrontendVulnerabilityMajor(int $frontendVulnerabilityMajor): self
    {
        $this->frontendVulnerabilityMajor = $frontendVulnerabilityMajor;
        return $this;
    }

    public function getFrontendVulnerabilityMinor(): int
    {
        return $this->frontendVulnerabilityMinor;
    }

    public function setFrontendVulnerabilityMinor(int $frontendVulnerabilityMinor): self
    {
        $this->frontendVulnerabilityMinor = $frontendVulnerabilityMinor;
        return $this;
    }

    public function getFrontendVulnerabilityInfo(): int
    {
        return $this->frontendVulnerabilityInfo;
    }

    public function setFrontendVulnerabilityInfo(int $frontendVulnerabilityInfo): self
    {
        $this->frontendVulnerabilityInfo = $frontendVulnerabilityInfo;
        return $this;
    }

    public function getFrontendCodeSmellBlocker(): int
    {
        return $this->frontendCodeSmellBlocker;
    }

    public function setFrontendCodeSmellBlocker(int $frontendCodeSmellBlocker): self
    {
        $this->frontendCodeSmellBlocker = $frontendCodeSmellBlocker;
        return $this;
    }

    public function getFrontendCodeSmellCritical(): int
    {
        return $this->frontendCodeSmellCritical;
    }

    public function setFrontendCodeSmellCritical(int $frontendCodeSmellCritical): self
    {
        $this->frontendCodeSmellCritical = $frontendCodeSmellCritical;
        return $this;
    }

    public function getFrontendCodeSmellMajor(): int
    {
        return $this->frontendCodeSmellMajor;
    }

    public function setFrontendCodeSmellMajor(int $frontendCodeSmellMajor): self
    {
        $this->frontendCodeSmellMajor = $frontendCodeSmellMajor;
        return $this;
    }

    public function getFrontendCodeSmellMinor(): int
    {
        return $this->frontendCodeSmellMinor;
    }

    public function setFrontendCodeSmellMinor(int $frontendCodeSmellMinor): self
    {
        $this->frontendCodeSmellMinor = $frontendCodeSmellMinor;
        return $this;
    }

    public function getFrontendCodeSmellInfo(): int
    {
        return $this->frontendCodeSmellInfo;
    }

    public function setFrontendCodeSmellInfo(int $frontendCodeSmellInfo): self
    {
        $this->frontendCodeSmellInfo = $frontendCodeSmellInfo;
        return $this;
    }
    public function getBackend(): int
    {
        return $this->backend;
    }

    public function setBackend(int $backend): self
    {
        $this->backend = $backend;
        return $this;
    }

    // BACKEND
    public function getBackendBugBlocker(): int
    {
        return $this->backendBugBlocker;
    }

    public function setBackendBugBlocker(int $backendBugBlocker): self
    {
        $this->backendBugBlocker = $backendBugBlocker;
        return $this;
    }

    public function getBackendBugCritical(): int
    {
        return $this->backendBugCritical;
    }

    public function setBackendBugCritical(int $backendBugCritical): self
    {
        $this->backendBugCritical = $backendBugCritical;
        return $this;
    }

    public function getBackendBugMajor(): int
    {
        return $this->backendBugMajor;
    }

    public function setBackendBugMajor(int $backendBugMajor): self
    {
        $this->backendBugMajor = $backendBugMajor;
        return $this;
    }

    public function getBackendBugMinor(): int
    {
        return $this->backendBugMinor;
    }

    public function setBackendBugMinor(int $backendBugMinor): self
    {
        $this->backendBugMinor = $backendBugMinor;
        return $this;
    }

    public function getBackendBugInfo(): int
    {
        return $this->backendBugInfo;
    }

    public function setBackendBugInfo(int $backendBugInfo): self
    {
        $this->backendBugInfo = $backendBugInfo;
        return $this;
    }

    public function getBackendVulnerabilityBlocker(): int
    {
        return $this->backendVulnerabilityBlocker;
    }

    public function setBackendVulnerabilityBlocker(int $backendVulnerabilityBlocker): self
    {
        $this->backendVulnerabilityBlocker = $backendVulnerabilityBlocker;
        return $this;
    }

    public function getBackendVulnerabilityCritical(): int
    {
        return $this->backendVulnerabilityCritical;
    }

    public function setBackendVulnerabilityCritical(int $backendVulnerabilityCritical): self
    {
        $this->backendVulnerabilityCritical = $backendVulnerabilityCritical;
        return $this;
    }

    public function getBackendVulnerabilityMajor(): int
    {
        return $this->backendVulnerabilityMajor;
    }

    public function setBackendVulnerabilityMajor(int $backendVulnerabilityMajor): self
    {
        $this->backendVulnerabilityMajor = $backendVulnerabilityMajor;
        return $this;
    }

    public function getBackendVulnerabilityMinor(): int
    {
        return $this->backendVulnerabilityMinor;
    }

    public function setBackendVulnerabilityMinor(int $backendVulnerabilityMinor): self
    {
        $this->backendVulnerabilityMinor = $backendVulnerabilityMinor;
        return $this;
    }

    public function getBackendVulnerabilityInfo(): int
    {
        return $this->backendVulnerabilityInfo;
    }

    public function setBackendVulnerabilityInfo(int $backendVulnerabilityInfo): self
    {
        $this->backendVulnerabilityInfo = $backendVulnerabilityInfo;
        return $this;
    }

    public function getBackendCodeSmellBlocker(): int
    {
        return $this->backendCodeSmellBlocker;
    }

    public function setBackendCodeSmellBlocker(int $backendCodeSmellBlocker): self
    {
        $this->backendCodeSmellBlocker = $backendCodeSmellBlocker;
        return $this;
    }

    public function getBackendCodeSmellCritical(): int
    {
        return $this->backendCodeSmellCritical;
    }

    public function setBackendCodeSmellCritical(int $backendCodeSmellCritical): self
    {
        $this->backendCodeSmellCritical = $backendCodeSmellCritical;
        return $this;
    }

    public function getBackendCodeSmellMajor(): int
    {
        return $this->backendCodeSmellMajor;
    }

    public function setBackendCodeSmellMajor(int $backendCodeSmellMajor): self
    {
        $this->backendCodeSmellMajor = $backendCodeSmellMajor;
        return $this;
    }

    public function getBackendCodeSmellMinor(): int
    {
        return $this->backendCodeSmellMinor;
    }

    public function setBackendCodeSmellMinor(int $backendCodeSmellMinor): self
    {
        $this->backendCodeSmellMinor = $backendCodeSmellMinor;
        return $this;
    }

    public function getBackendCodeSmellInfo(): int
    {
        return $this->backendCodeSmellInfo;
    }

    public function setBackendCodeSmellInfo(int $backendCodeSmellInfo): self
    {
        $this->backendCodeSmellInfo = $backendCodeSmellInfo;
        return $this;
    }

    public function getAutre(): int
    {
        return $this->autre;
    }

    public function setAutre(int $autre): self
    {
        $this->autre = $autre;
        return $this;
    }

    public function getAutreBugBlocker(): int
    {
        return $this->autreBugBlocker;
    }

    public function setAutreBugBlocker(int $autreBugBlocker): self
    {
        $this->autreBugBlocker = $autreBugBlocker;
        return $this;
    }

    public function getAutreBugCritical(): int
    {
        return $this->autreBugCritical;
    }

    public function setAutreBugCritical(int $autreBugCritical): self
    {
        $this->autreBugCritical = $autreBugCritical;
        return $this;
    }

    public function getAutreBugMajor(): int
    {
        return $this->autreBugMajor;
    }

    public function setAutreBugMajor(int $autreBugMajor): self
    {
        $this->autreBugMajor = $autreBugMajor;
        return $this;
    }

    public function getAutreBugMinor(): int
    {
        return $this->autreBugMinor;
    }

    public function setAutreBugMinor(int $autreBugMinor): self
    {
        $this->autreBugMinor = $autreBugMinor;
        return $this;
    }

    public function getAutreBugInfo(): int
    {
        return $this->autreBugInfo;
    }

    public function setAutreBugInfo(int $autreBugInfo): self
    {
        $this->autreBugInfo = $autreBugInfo;
        return $this;
    }

    public function getAutreVulnerabilityBlocker(): int
    {
        return $this->autreVulnerabilityBlocker;
    }

    public function setAutreVulnerabilityBlocker(int $autreVulnerabilityBlocker): self
    {
        $this->autreVulnerabilityBlocker = $autreVulnerabilityBlocker;
        return $this;
    }

    public function getAutreVulnerabilityCritical(): int
    {
        return $this->autreVulnerabilityCritical;
    }

    public function setAutreVulnerabilityCritical(int $autreVulnerabilityCritical): self
    {
        $this->autreVulnerabilityCritical = $autreVulnerabilityCritical;
        return $this;
    }

    public function getAutreVulnerabilityMajor(): int
    {
        return $this->autreVulnerabilityMajor;
    }

    public function setAutreVulnerabilityMajor(int $autreVulnerabilityMajor): self
    {
        $this->autreVulnerabilityMajor = $autreVulnerabilityMajor;
        return $this;
    }

    public function getAutreVulnerabilityMinor(): int
    {
        return $this->autreVulnerabilityMinor;
    }

    public function setAutreVulnerabilityMinor(int $autreVulnerabilityMinor): self
    {
        $this->autreVulnerabilityMinor = $autreVulnerabilityMinor;
        return $this;
    }

    public function getAutreVulnerabilityInfo(): int
    {
        return $this->autreVulnerabilityInfo;
    }

    public function setAutreVulnerabilityInfo(int $autreVulnerabilityInfo): self
    {
        $this->autreVulnerabilityInfo = $autreVulnerabilityInfo;
        return $this;
    }

    public function getAutreCodeSmellBlocker(): int
    {
        return $this->autreCodeSmellBlocker;
    }

    public function setAutreCodeSmellBlocker(int $autreCodeSmellBlocker): self
    {
        $this->autreCodeSmellBlocker = $autreCodeSmellBlocker;
        return $this;
    }

    public function getAutreCodeSmellCritical(): int
    {
        return $this->autreCodeSmellCritical;
    }

    public function setAutreCodeSmellCritical(int $autreCodeSmellCritical): self
    {
        $this->autreCodeSmellCritical = $autreCodeSmellCritical;
        return $this;
    }

    public function getAutreCodeSmellMajor(): int
    {
        return $this->autreCodeSmellMajor;
    }

    public function setAutreCodeSmellMajor(int $autreCodeSmellMajor): self
    {
        $this->autreCodeSmellMajor = $autreCodeSmellMajor;
        return $this;
    }

    public function getAutreCodeSmellMinor(): int
    {
        return $this->autreCodeSmellMinor;
    }

    public function setAutreCodeSmellMinor(int $autreCodeSmellMinor): self
    {
        $this->autreCodeSmellMinor = $autreCodeSmellMinor;
        return $this;
    }

    public function getAutreCodeSmellInfo(): int
    {
        return $this->autreCodeSmellInfo;
    }

    public function setAutreCodeSmellInfo(int $autreCodeSmellInfo): self
    {
        $this->autreCodeSmellInfo = $autreCodeSmellInfo;
        return $this;
    }

    public function getInconnue(): int
    {
        return $this->inconnue;
    }

    public function setInconnue(int $inconnue): self
    {
        $this->inconnue = $inconnue;
        return $this;
    }

    public function getInconnueBugBlocker(): int
    {
        return $this->inconnueBugBlocker;
    }

    public function setInconnueBugBlocker(int $inconnueBugBlocker): self
    {
        $this->inconnueBugBlocker = $inconnueBugBlocker;
        return $this;
    }

    public function getInconnueBugCritical(): int
    {
        return $this->inconnueBugCritical;
    }

    public function setInconnueBugCritical(int $inconnueBugCritical): self
    {
        $this->inconnueBugCritical = $inconnueBugCritical;
        return $this;
    }

    public function getInconnueBugMajor(): int
    {
        return $this->inconnueBugMajor;
    }

    public function setInconnueBugMajor(int $inconnueBugMajor): self
    {
        $this->inconnueBugMajor = $inconnueBugMajor;
        return $this;
    }

    public function getInconnueBugMinor(): int
    {
        return $this->inconnueBugMinor;
    }

    public function setInconnueBugMinor(int $inconnueBugMinor): self
    {
        $this->inconnueBugMinor = $inconnueBugMinor;
        return $this;
    }

    public function getInconnueBugInfo(): int
    {
        return $this->inconnueBugInfo;
    }

    public function setInconnueBugInfo(int $inconnueBugInfo): self
    {
        $this->inconnueBugInfo = $inconnueBugInfo;
        return $this;
    }

    public function getInconnueVulnerabilityBlocker(): int
    {
        return $this->inconnueVulnerabilityBlocker;
    }

    public function setInconnueVulnerabilityBlocker(int $inconnueVulnerabilityBlocker): self
    {
        $this->inconnueVulnerabilityBlocker = $inconnueVulnerabilityBlocker;
        return $this;
    }

    public function getInconnueVulnerabilityCritical(): int
    {
        return $this->inconnueVulnerabilityCritical;
    }

    public function setInconnueVulnerabilityCritical(int $inconnueVulnerabilityCritical): self
    {
        $this->inconnueVulnerabilityCritical = $inconnueVulnerabilityCritical;
        return $this;
    }

    public function getInconnueVulnerabilityMajor(): int
    {
        return $this->inconnueVulnerabilityMajor;
    }

    public function setInconnueVulnerabilityMajor(int $inconnueVulnerabilityMajor): self
    {
        $this->inconnueVulnerabilityMajor = $inconnueVulnerabilityMajor;
        return $this;
    }

    public function getInconnueVulnerabilityMinor(): int
    {
        return $this->inconnueVulnerabilityMinor;
    }

    public function setInconnueVulnerabilityMinor(int $inconnueVulnerabilityMinor): self
    {
        $this->inconnueVulnerabilityMinor = $inconnueVulnerabilityMinor;
        return $this;
    }

    public function getInconnueVulnerabilityInfo(): int
    {
        return $this->inconnueVulnerabilityInfo;
    }

    public function setInconnueVulnerabilityInfo(int $inconnueVulnerabilityInfo): self
    {
        $this->inconnueVulnerabilityInfo = $inconnueVulnerabilityInfo;
        return $this;
    }

    public function getInconnueCodeSmellBlocker(): int
    {
        return $this->inconnueCodeSmellBlocker;
    }

    public function setInconnueCodeSmellBlocker(int $inconnueCodeSmellBlocker): self
    {
        $this->inconnueCodeSmellBlocker = $inconnueCodeSmellBlocker;
        return $this;
    }

    public function getInconnueCodeSmellCritical(): int
    {
        return $this->inconnueCodeSmellCritical;
    }

    public function setInconnueCodeSmellCritical(int $inconnueCodeSmellCritical): self
    {
        $this->inconnueCodeSmellCritical = $inconnueCodeSmellCritical;
        return $this;
    }

    public function getInconnueCodeSmellMajor(): int
    {
        return $this->inconnueCodeSmellMajor;
    }

    public function setInconnueCodeSmellMajor(int $inconnueCodeSmellMajor): self
    {
        $this->inconnueCodeSmellMajor = $inconnueCodeSmellMajor;
        return $this;
    }

    public function getInconnueCodeSmellMinor(): int
    {
        return $this->inconnueCodeSmellMinor;
    }

    public function setInconnueCodeSmellMinor(int $inconnueCodeSmellMinor): self
    {
        $this->inconnueCodeSmellMinor = $inconnueCodeSmellMinor;
        return $this;
    }

    public function getInconnueCodeSmellInfo(): int
    {
        return $this->inconnueCodeSmellInfo;
    }

    public function setInconnueCodeSmellInfo(int $inconnueCodeSmellInfo): self
    {
        $this->inconnueCodeSmellInfo = $inconnueCodeSmellInfo;
        return $this;
    }

    public function getSetup(): int
    {
        return $this->setup;
    }

    public function setSetup(int $setup): self
    {
        $this->setup = $setup;
        return $this;
    }

    public function getControl(): ?string
    {
        return $this->control;
    }

    public function setControl(?string $control): self
    {
        $this->control = $control;
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
