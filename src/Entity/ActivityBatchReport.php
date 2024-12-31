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

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

/**
 * [Description ActivityBatchReport]
 */
#[ORM\Entity]
#[ORM\Table(name: 'activity_batch_report', schema: "ma_moulinette")]
#[ORM\Index(name: 'idx_date_enregistrement', columns: ['date_enregistrement'])]
#[ORM\Index(name: 'idx_task_status', columns: ['task_count', 'task_done', 'page'])]
#[ORM\Index(name: 'idx_last_error', columns: ['lastError'])]
class ActivityBatchReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false)]
    private \DateTimeImmutable $dateStart;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false)]
    private \DateTimeImmutable $dateEnd;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    private int $taskCount = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    private int $taskDone = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    private int $page = 0;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => "Liste des erreurs du traitement."])]
    private array $lastError = [];

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false)]
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
