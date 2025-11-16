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
use App\Repository\BatchProfilingRepository;

/**
 * [Description BatchProfiling]
 */
#[ORM\Entity(repositoryClass: BatchProfilingRepository::class)]
#[ORM\Table(name: "batch_profiling", schema: "ma_moulinette")]
class BatchProfiling
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false, options: [
        'comment' => 'Identifiant unique de la table batch_profiling'
    ])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, options: [
        'comment' => 'Nom du portefeuille concerné par le traitement'
    ])]
    private string $portefeuille;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: [
        'comment' => 'Référence unique d’exécution du traitement (token ou UUID)'
    ])]
    private ?string $executionReference = null;

    #[ORM\Column(type: Types::INTEGER, options: [
        'comment' => 'Nombre total de projets traités dans le batch'
    ])]
    private int $nbProjets;

    #[ORM\Column(type: Types::FLOAT, options: [
        'comment' => 'Durée totale d’exécution en secondes'
    ])]
    private float $tempsTotal;

    #[ORM\Column(type: Types::FLOAT, options: [
        'comment' => 'Durée moyenne d’exécution par projet (en secondes)'
    ])]
    private float $tempsMoyen;

    #[ORM\Column(type: Types::FLOAT, options: [
        'comment' => 'Consommation mémoire maximale observée (en Mo)'
    ])]
    private float $memoirePeak;

    #[ORM\Column(type: Types::FLOAT, options: [
        'comment' => 'Consommation mémoire moyenne observée (en Mo)'
    ])]
    private float $memoireMoyenne;

    #[ORM\Column(type: Types::STRING, length: 128, options: [
        'comment' => 'Identifiant ou adresse e-mail de l’utilisateur exécutant le batch'
    ])]
    private string $utilisateur;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false, options: [
        'default' => 'CURRENT_TIMESTAMP',
        'comment' => "Horodatage de l’exécution du batch (timezone Europe/Paris)"
    ])]
    private \DateTimeImmutable $dateExecution;

    public function __construct(
        string $portefeuille,
        int $nbProjets,
        float $tempsTotal,
        float $tempsMoyen,
        float $memoirePeak,
        float $memoireMoyenne,
        string $utilisateur,
        ?string $executionReference = null
    ) {
        $this->portefeuille = $portefeuille;
        $this->nbProjets = $nbProjets;
        $this->tempsTotal = $tempsTotal;
        $this->tempsMoyen = $tempsMoyen;
        $this->memoirePeak = $memoirePeak;
        $this->memoireMoyenne = $memoireMoyenne;
        $this->utilisateur = $utilisateur;
        $this->executionReference = $executionReference;
        $this->dateExecution = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    // --- Getters ---

    public function getId(): ?int { return $this->id; }
    public function getPortefeuille(): string { return $this->portefeuille; }
    public function getExecutionReference(): ?string { return $this->executionReference; }
    public function getNbProjets(): int { return $this->nbProjets; }
    public function getTempsTotal(): float { return $this->tempsTotal; }
    public function getTempsMoyen(): float { return $this->tempsMoyen; }
    public function getMemoirePeak(): float { return $this->memoirePeak; }
    public function getMemoireMoyenne(): float { return $this->memoireMoyenne; }
    public function getUtilisateur(): string { return $this->utilisateur; }
    public function getDateExecution(): \DateTimeImmutable { return $this->dateExecution; }

    // --- Méthodes utilitaires (facultatives mais utiles dans Twig ou debug) ---

    public function getDateExecutionFormatted(string $format = 'd/m/Y H:i'): string
    {
        return $this->dateExecution->format($format);
    }

    /**
     * [Description for __toString]
     *
     * @return string
     * 
     * Created at: 13/11/2025 21:17:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com> 
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0. 
     */
    public function __toString(): string
    {
        return sprintf(
            '[Profiling] %s (%s) - %d projets - %.2fs - %.2f Mo',
            $this->portefeuille,
            $this->utilisateur,
            $this->nbProjets,
            $this->tempsTotal,
            $this->memoirePeak
        );
    }
}
