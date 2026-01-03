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

use App\Repository\UserAgentEventRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

/**
 * [Description UserAgentEvent]
 *
 * Table de collecte brute des événements liés aux User-Agents.
 *
 * Cette entité représente un événement technique déclenché lors du chargement
 * d'une page sensible (login, redirection post-login, logout).
 *
 * ⚠️ Cette table est la source de vérité :
 * - le user-agent est stocké brut
 * - aucune analyse n'est faite ici
 * - seules les colonnes de statut peuvent évoluer
 */
#[ORM\Entity(repositoryClass: UserAgentEventRepository::class)]
#[ORM\Table(name: "user_agent_event", schema: "ma_moulinette")]
#[ORM\Index(name: "idx_processing_status", columns: ["processing_status"])]
#[ORM\Index(name: "idx_event_type", columns: ["event_type"])]
#[ORM\Index(name: "idx_created_at", columns: ["created_at"])]
#[ORM\Index(name: "idx_user_id", columns: ["user_id"])]
#[ORM\Index(name: "idx_session_id", columns: ["session_id"])]
class UserAgentEvent {

    /**
     * [Description for $id]
     * Identifiant technique unique de l'événement
     *
     * @var int|null|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: "id",
        type: Types::BIGINT,
        nullable: false,
        options: ['comment' => 'Identifiant unique de l’événement User-Agent']
    )]
    private ?int $id = null;

    /**
     * Type d'événement fonctionnel.
     *
     * Valeurs possibles :
     * - LOGIN_PAGE_VIEW : affichage de la page de login (utilisateur anonyme)
     * - LOGIN_SUCCESS_REDIRECT : redirection après authentification réussie
     * - LOGOUT : déconnexion de l'utilisateur
     */
    #[ORM\Column(
        name: "event_type",
        type: Types::STRING,
        length: 50,
        nullable: false,
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
        name: "url",
        type: Types::STRING,
        length: 2048,
        nullable: false,
        options: ['comment' => 'URL ou path déclencheur de l’événement']
    )]
    private ?string $url = null;

    /**
     * User-Agent HTTP brut tel que reçu dans la requête.
     *
     * ⚠️ Donnée non fiable :
     * - peut être falsifiée
     * - peut contenir des caractères exotiques
     * - ne doit jamais être interprétée directement
     */
    #[ORM\Column(
        name: "user_agent",
        type: Types::STRING,
        length: 1024,
        nullable: false,
        options: ['comment' => 'User-Agent HTTP brut fourni par le client']
    )]
    private ?string $userAgent = null;

    /**
     * Identifiant de session PHP.
     *
     * Cas possibles :
     * - NULL : utilisateur anonyme (avant login)
     * - non NULL : session active après authentification
     */
    #[ORM\Column(
        name: "session_id",
        type: Types::STRING,
        length: 128,
        nullable: true,
        options: ['comment' => 'Identifiant de session PHP si existant']
    )]
    private ?string $sessionId = null;

    /**
     * Identifiant de l'utilisateur authentifié.
     *
     * Cas possibles :
     * - NULL : utilisateur non authentifié
     * - non NULL : utilisateur connecté
     */
    #[ORM\Column(
        name: "user_id",
        type: Types::BIGINT,
        nullable: true,
        options: ['comment' => 'Identifiant de l’utilisateur authentifié']
    )]
    private ?int $userId = null;

    /**
     * État d'authentification au moment de l'événement.
     *
     * Valeurs possibles :
     * - ANONYMOUS : utilisateur non authentifié
     * - AUTHENTICATED : utilisateur connecté
     */
    #[ORM\Column(
        name: "auth_state",
        type: Types::STRING,
        length: 20,
        nullable: false,
        options: ['comment' => 'État d’authentification lors de l’événement']
    )]
    private ?string $authState = null;

    /**
     * Statut de traitement de l'événement par le service d'analyse.
     *
     * Valeurs possibles :
     * - PENDING : en attente d'analyse
     * - PROCESSING : en cours d'analyse
     * - DONE : analysé avec succès
     * - ERROR : erreur lors de l'analyse
     */
    #[ORM\Column(
        name: "processing_status",
        type: Types::STRING,
        length: 20,
        nullable: false,
        options: ['comment' => 'Statut de traitement par le batch d’analyse']
    )]
    private ?string $processingStatus = null;

    /**
     * Hash de l'adresse IP (SHA-256 + salt applicatif).
     *
     * ⚠️ L'adresse IP en clair ne doit jamais être stockée.
     * Ce champ est optionnel et utilisé uniquement à des fins statistiques ou sécurité.
     */
    #[ORM\Column(
        name: "ip_hash",
        type: Types::STRING,
        length: 64,
        nullable: true,
        options: ['comment' => 'Hash SHA-256 de l’adresse IP client']
    )]
    private ?string $ipHash = null;

    /**
     * Date de création de l'événement.
     */
    #[ORM\Column(
        name: "created_at",
        type: Types::DATETIME_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de création de l’événement']
    )]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de fin de traitement par le service d'analyse.
     */
    #[ORM\Column(
        name: "processed_at",
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: ['comment' => 'Date de traitement de l’événement']
    )]
    private ?\DateTimeImmutable $processedAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
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

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getAuthState(): ?string
    {
        return $this->authState;
    }

    public function setAuthState(string $authState): self
    {
        $this->authState = $authState;
        return $this;
    }

    public function getProcessingStatus(): ?string
    {
        return $this->processingStatus;
    }

    public function setProcessingStatus(string $processingStatus): self
    {
        $this->processingStatus = $processingStatus;
        return $this;
    }

    public function getIpHash(): ?string
    {
        return $this->ipHash;
    }

    public function setIpHash(?string $ipHash): self
    {
        $this->ipHash = $ipHash;
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

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): self
    {
        $this->processedAt = $processedAt;
        return $this;
    }

}
