<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\UserAgentAnalysisRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

/**
 * [Description UserAgentAnalysis]
 *
 * Table contenant les résultats d'analyse du User-Agent via Matomo DeviceDetector.
 *
 * Cette table est orientée statistiques et reporting.
 * Les données sont volontairement dénormalisées pour faciliter les agrégations.
 */
#[ORM\Entity(repositoryClass: UserAgentAnalysisRepository::class)]
#[ORM\Index(name: 'idx_device_type', columns: ["device_type"])]
#[ORM\Index(name: 'idx_os_name', columns: ["os_name"])]
#[ORM\Index(name: 'idx_browser_name', columns: ["browser_name"])]
#[ORM\Index(name: 'idx_is_bot', columns: ["is_bot"])]
#[ORM\Index(name: 'idx_created_at', columns: ["created_at"])]
#[ORM\Index(name: 'idx_event_type', columns: ["event_type"])]
#[ORM\Index(name: 'idx_session_id', columns: ["session_id"])]
#[ORM\Table(
    name: 'user_agent_analysis',
    schema: 'ma_moulinette',
    options: ['comment' => 'Table d’analyse pour le User-Agent']
    )]
class UserAgentAnalysis {
    /**
     * Identifiant technique unique de l'analyse.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT,
        nullable: false,
        options: ['comment' => 'Identifiant unique de l’analyse User-Agent']
    )]
    private ?int $id = null;

    /**
     * Référence vers l'événement source analysé.
     *
     * Relation 1–1 :
     * un événement ne peut être analysé qu'une seule fois.
     */
    #[ORM\OneToOne(targetEntity: UserAgentEvent::class)]
    #[ORM\JoinColumn(
        name: 'user_agent_event_id',
        referencedColumnname: 'id',
        nullable: false,
        onDelete: "CASCADE"
    )]
    private ?UserAgentEvent $userAgentEvent = null;

    /**
     * Type d'événement fonctionnel.
     *
     * Valeurs possibles :
     * - LOGIN_PAGE_VIEW : affichage de la page de login (utilisateur anonyme)
     * - LOGIN_SUCCESS_REDIRECT : redirection après authentification réussie
     * - LOGOUT : déconnexion de l'utilisateur
     */
    #[ORM\Column(
        name: 'event_type',
        type: Types::STRING,
        length: 50,
        nullable: true,
        options: ['comment' => 'Type fonctionnel de l’événement déclencheur']
    )]
    private ?string $eventType = null;

    /**
     * URL ou chemin ayant déclenché l'événement.
     *
     * Exemple :
     * - /login
     * - /dashboard
     * - /logout
     *
     * ⚠️ Ne pas utiliser ce champ comme source métier,
     * il est uniquement informatif.
     */
    #[ORM\Column(
        name: 'url',
        type: Types::STRING,
        length: 2048,
        nullable: true,
        options: ['comment' => 'URL ou path déclencheur de l’événement']
    )]
    private ?string $url = null;

    /**
     * Identifiant de session PHP.
     *
     * Cas possibles :
     * - NULL : utilisateur anonyme (avant login)
     * - non NULL : session active après authentification
     */
    #[ORM\Column(
        name: 'session_id',
        type: Types::STRING,
        length: 128,
        nullable: true,
        options: ['comment' => 'Identifiant de session PHP si existant']
    )]
    private ?string $sessionId = null;

    /**
     * Type d'appareil détecté.
     *
     * Valeurs possibles : desktop, smartphone, tablet, tv, console, unknown
     */
    #[ORM\Column(
        name: 'device_type',
        type: Types::STRING,
        length: 30,
        nullable: true,
        options: ['comment' => 'Type d’appareil détecté']
    )]
    private ?string $deviceType = null;

    /**
     * Système d'exploitation détecté.
     *
     * Exemple : Windows, macOS, Linux, Android, iOS
     */
    #[ORM\Column(
        name: 'os_name',
        type: Types::STRING,
        length: 50,
        nullable: true,
        options: ['comment' => 'Nom du système d’exploitation']
    )]
    private ?string $osName = null;

    /**
     * Version du système d'exploitation.
     */
    #[ORM\Column(
        name: 'os_version',
        type: Types::STRING,
        length: 50,
        nullable: true,
        options: ['comment' => 'Version du système d’exploitation']
    )]
    private ?string $osVersion = null;

    /**
     * Nom du navigateur détecté.
     *
     * Exemple : Chrome, Firefox, Safari, Edge
     */
    #[ORM\Column(
        name: 'browser_name',
        type: Types::STRING,
        length: 50,
        nullable: true,
        options: ['comment' => 'Nom du navigateur']
    )]
    private ?string $browserName = null;

    /**
     * Version du navigateur.
     */
    #[ORM\Column(
        name: 'browser_version',
        type: Types::STRING,
        length: 50,
        nullable: true,
        options: ['comment' => 'Version du navigateur']
    )]
    private ?string $browserVersion = null;

    /**
     * Indique si le client est identifié comme un bot.
     */
    #[ORM\Column(
        name: 'is_bot',
        type: Types::BOOLEAN,
        nullable: true,
        options: ['comment' => 'Indique si le client est un bot']
    )]
    private ?bool $isBot = null;

    /**
     * Version du moteur Matomo DeviceDetector utilisé pour l'analyse.
     *
     * Permet de rejouer ou comparer les analyses dans le temps.
     */
    #[ORM\Column(
        name: 'detector_version',
        type: Types::STRING,
        length: 20,
        nullable: false,
        options: ['comment' => 'Version du moteur Matomo DeviceDetector']
    )]
    private ?string $detectorVersion = null;

    /**
     * Date de création de l'analyse.
     */
    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de création de l’analyse']
    )]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct(string $detectorVersion)
    {
        $this->detectorVersion = $detectorVersion;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserAgentEvent(): ?UserAgentEvent
    {
        return $this->userAgentEvent;
    }

    public function setUserAgentEvent(UserAgentEvent $userAgentEvent): self
    {
        $this->userAgentEvent = $userAgentEvent;
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function getDeviceType(): ?string
    {
        return $this->deviceType;
    }

    public function setDeviceType(?string $deviceType): self
    {
        $this->deviceType = $deviceType;
        return $this;
    }

    public function getOsName(): ?string
    {
        return $this->osName;
    }

    public function setOsName(?string $osName): self
    {
        $this->osName = $osName;
        return $this;
    }

    public function getOsVersion(): ?string
    {
        return $this->osVersion;
    }

    public function setOsVersion(?string $osVersion): self
    {
        $this->osVersion = $osVersion;
        return $this;
    }

    public function getBrowserName(): ?string
    {
        return $this->browserName;
    }

    public function setBrowserName(?string $browserName): self
    {
        $this->browserName = $browserName;
        return $this;
    }

    public function getBrowserVersion(): ?string
    {
        return $this->browserVersion;
    }

    public function setBrowserVersion(?string $browserVersion): self
    {
        $this->browserVersion = $browserVersion;
        return $this;
    }

    public function isBot(): ?bool
    {
        return $this->isBot;
    }

    public function setIsBot(?bool $isBot): self
    {
        $this->isBot = $isBot;
        return $this;
    }

    public function getDetectorVersion(): ?string
    {
        return $this->detectorVersion;
    }

    public function setDetectorVersion(string $detectorVersion): self
    {
        $this->detectorVersion = $detectorVersion;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

}
