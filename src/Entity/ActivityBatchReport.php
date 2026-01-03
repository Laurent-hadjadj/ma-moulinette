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

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

/**
 * [Description ActivityBatchReport]
 */
#[ORM\Entity]
#[ORM\Table(name: 'activity_batch_report', schema: 'ma_moulinette',
    options: ['comment' => 'Rapport d’exécution des traitements batch']
)]
#[ORM\Index(name: 'idx_date_enregistrement', columns: ['date_enregistrement'])]
#[ORM\Index(name: 'idx_task_status', columns: ['task_count', 'task_done', 'page'])]
class ActivityBatchReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique du rapport batch']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'date_start',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date et heure de début du traitement batch']
    )]
    private \DateTimeImmutable $dateStart;

    #[ORM\Column(
        name: 'date_end',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date et heure de fin du traitement batch']
    )]
    private \DateTimeImmutable $dateEnd;

    #[ORM\Column(
        name: 'task_count',
        type: Types::INTEGER,
        nullable: false,
        options: [
            'default' => 0,
            'comment' => 'Nombre total de tâches à traiter'
        ]
    )]
    private int $taskCount = 0;

    #[ORM\Column(
        name: 'task_done',
        type: Types::INTEGER,
        nullable: false,
        options: [
            'default' => 0,
            'comment' => 'Nombre de tâches traitées avec succès'
        ]
    )]
    private int $taskDone = 0;

    #[ORM\Column(
        name: 'page',
        type: Types::INTEGER,
        nullable: false,
        options: [
            'default' => 0,
            'comment' => 'Numéro de page ou lot traité'
        ]
    )]
    private int $page = 0;

    #[ORM\Column(
        name: 'last_error',
        type: Types::JSON,
        nullable: true,
        options: ['comment' => 'Liste des erreurs rencontrées lors du traitement batch']
    )]
    private ?array $lastError = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date et heure d’enregistrement du rapport']
    )]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->dateEnregistrement = new \DateTimeImmutable();
    }

    // Getters et setters pour chaque propriété
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDateStart(): \DateTimeImmutable
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTimeImmutable $dateStart): self
    {
        $this->dateStart = $dateStart;
        return $this;
    }

    public function getDateEnd(): \DateTimeImmutable
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTimeImmutable $dateEnd): self
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }

    public function getTaskCount(): int
    {
        return $this->taskCount;
    }

    public function setTaskCount(int $taskCount): self
    {
        $this->taskCount = $taskCount;
        return $this;
    }

    public function getTaskDone(): int
    {
        return $this->taskDone;
    }

    public function setTaskDone(int $taskDone): self
    {
        $this->taskDone = $taskDone;
        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function getLastError(): array
    {
        return $this->lastError;
    }

    public function setLastError(array $lastError): self
    {
        $this->lastError = $lastError;
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
