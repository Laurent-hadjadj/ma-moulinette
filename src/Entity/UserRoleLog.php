<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\UserRoleLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRoleLogRepository::class)]
#[ORM\Table(name: "user_role_log", schema: "ma_moulinette")]
class UserRoleLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: "id",
        type: Types::INTEGER,
        nullable: false,
            options: ['comment' => 'ID unique pour la table user_role_log'])]
    private int $id;

    #[ORM\Column(
        name: "user_email",
        type: Types::STRING,
        length: 320,
        nullable: false,
        options: ['comment' => "Adresse de courriel, de l'utilisateur dont les rôles ont été modifiés"])]
    private string $userEmail;

    #[ORM\Column(
        name: "editor_email",
        type: Types::STRING,
        length: 320,
        nullable: false,
        options: ['comment' => "Adresse de courriel de l'éditeur ayant modifié les rôles de l'utilisateur"])]
    private string $editorEmail;

    /**
     * @var array<int, string>
     */
    #[ORM\Column(
        name: "old_roles",
        type: 'json',
        options: ['comment' => "Rôles anciens de l'utilisateur"])]
    private array $oldRoles = [];

    /**
     * @var array<int, string>
     */
    #[ORM\Column(
        name: "new_roles",
        type: 'json',
        options: ['comment' => "Nouveaux rôles de l'utilisateur"])]
    private array $newRoles = [];

    #[ORM\Column(
        name: "old_active",
        type: Types::BOOLEAN,
        options: ['comment' => "Ancien statut actif de l'utilisateur"])]
    private bool $oldActive;

    #[ORM\Column(
        name: "new_active",
        type: Types::BOOLEAN,
        options: ['comment' => "Nouveau statut actif de l'utilisateur"])]
    private bool $newActive;

    /**
     * @var array<int, string>
     */
    #[ORM\Column(
        name: "alerts",
        type: 'json',
        nullable: true,
        options: ['comment' => "Alertes associées au log"])]
    private array $alerts = [];

    #[ORM\Column(
        name: "created_at",
        type: Types::DATETIME_IMMUTABLE,
        options: ['comment' => "Date de création du log"])]
    private \DateTimeImmutable $createdAt;
/**
 * @param array<int|string, mixed> $oldRoles
 * @param array<int|string, mixed> $newRoles
 * @param array<int|string, mixed> $alerts
 */

    public function __construct(string $userEmail, string $editorEmail, array $oldRoles, array $newRoles, bool $oldActive, bool $newActive, array $alerts)
    {
        $this->userEmail = $userEmail;
        $this->editorEmail = $editorEmail;
        $this->oldRoles = $oldRoles;
        $this->newRoles = $newRoles;
        $this->oldActive = $oldActive;
        $this->newActive = $newActive;
        $this->alerts = $alerts;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }
    public function setUserEmail(string $userEmail): void
    {
        $this->userEmail = $userEmail;
    }

    public function getEditorEmail(): string
    {
        return $this->editorEmail;
    }
    public function setEditorEmail(string $editorEmail): void
    {
        $this->editorEmail = $editorEmail;
    }

    /**
     * @return array<int, string>
     */
    public function getOldRoles(): array
    {
        return $this->oldRoles;
    }
    /**
     * @param array<int, string> $oldRoles
     */
    public function setOldRoles(array $oldRoles): void
    {
        $this->oldRoles = $oldRoles;
    }

    /**
     * @return array<int, string>
     */
    public function getNewRoles(): array
    {
        return $this->newRoles;
    }
    /**
     * @param array<int, string> $newRoles
     */
    public function setNewRoles(array $newRoles): void
    {
        $this->newRoles = $newRoles;
    }

    public function isOldActive(): bool
    {
        return $this->oldActive;
    }
    public function setOldActive(bool $oldActive): void
    {
        $this->oldActive = $oldActive;
    }

    public function isNewActive(): bool
    {
        return $this->newActive;
    }
    public function setNewActive(bool $newActive): void
    {
        $this->newActive = $newActive;
    }

    /**
     * @return array<int, string>
     */
    public function getAlerts(): array
    {
        return $this->alerts;
    }
    /**
     * @param array<int, string> $alerts
     */
    public function setAlerts(array $alerts): void
    {
        $this->alerts = $alerts;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
