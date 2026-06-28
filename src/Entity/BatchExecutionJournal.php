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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BatchExecutionJournalRepository;

/**
 * [Description BatchExecutionJournal]
 */
#[ORM\Entity(repositoryClass: BatchExecutionJournalRepository::class)]
#[ORM\Table(name: "batch_execution_journal", schema: "ma_moulinette")]
class BatchExecutionJournal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique de la table batch_execution_journal']
    )]
    private int $id;

    #[ORM\Column(
        name: 'code',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Code de statut du traitement (200 = OK, 500 = Erreur, etc.)']
    )]
    private int $code = 500;

    /* MODIF 2026-05-05 : ajout de `length: 128`*/
    #[ORM\Column(
        name: 'nom_projet',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du projet']
    )]
    private string $nomProjet = '';

    #[ORM\Column(
        name: 'portefeuille',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Portefeuille de projets']
    )]
    private string $portefeuille = '';

    /** @var string|resource|null La colonne BINARY peut être renvoyée comme stream selon le driver. */
    #[ORM\Column(
        name: 'compte_rendu',
        type: Types::BINARY,
        nullable: false,
        options: ['comment' => 'Compte rendu HTML compressé du traitement.']
    )]
    private $compteRendu = null;

    #[ORM\Column(
        name: 'date_execution',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date d'exécution de la collecte."]
    )]
    private \DateTimeImmutable $dateExecution;

    #[ORM\ManyToOne(targetEntity: BatchExecution::class, inversedBy: 'collectes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?BatchExecution $job = null;

    /* MODIF 2026-05-07 : ajout constructeur (paramètres nommés). */
    public function __construct(
        string $nomProjet = '',
        string $portefeuille = '',
        string $compteRendu = '',
        ?BatchExecution $batchExecution = null,
        ?\DateTimeImmutable $dateExecution = null,
        int $code = 500
    ) {
        $this->nomProjet = $nomProjet;
        $this->portefeuille = $portefeuille;
        $this->setCompteRendu($compteRendu);
        $this->job = $batchExecution;
        $this->dateExecution = $dateExecution ?? new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $this->code = $code;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCompteRendu(string $html): void
    {
        // 9 = niveau de compression le plus haut (0–9)
        $this->compteRendu = gzencode($html, 9);
    }

    /**
     * @return string|resource|null
     */
    public function getCompteRenduBrut()
    {
        return $this->compteRendu; // retourne la propriété privée telle quelle
    }

    public function getCompteRendu(): string
    {
        if ($this->compteRendu === null) {
            return '';
        }

        // Le champ peut être un flux (resource)
        $binary = is_resource($this->compteRendu)
            ? stream_get_contents($this->compteRendu)
            : $this->compteRendu;

        // On tente de décoder si c’est du gzencode()
        $html = @gzdecode($binary);

        // Si la donnée est déjà en texte brut (cas anciens)
        if ($html === false) {
            $html = $binary;
        }

        return $html;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getNomProjet(): ?string
    {
        return $this->nomProjet;
    }

    public function setNomProjet(string $nomProjet): void
    {
        $this->nomProjet = $nomProjet;
    }

    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    public function setPortefeuille(string $portefeuille): void
    {
        $this->portefeuille = $portefeuille;
    }

    public function setDateExecution(\DateTimeImmutable $date): void
    {
        $this->dateExecution = $date;
    }

    public function getDateExecution(): \DateTimeImmutable
    {
        return $this->dateExecution;
    }

    public function setJob(?BatchExecution $job): void
    {
        $this->job = $job;
    }

    public function getJob(): ?BatchExecution
    {
        return $this->job;
    }

    // ----------------------------------------------------
    // Utilitaires
    // ----------------------------------------------------

    public function isSuccess(): bool
    {
        return $this->code === 200;
    }

    public function isError(): bool
    {
        return $this->code >= 400;
    }
}
