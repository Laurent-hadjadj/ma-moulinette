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

use App\Repository\BatchTraitementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BatchTraitementRepository::class)]
#[ORM\Table(
    name: "batch_traitement",
    schema: "ma_moulinette",
    options: ['comment' => 'Gestion des traitements batch']
)]
class BatchTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour la table Batch Traitement']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Mode de collecte du traitement']
    )]
    #[Assert\NotBlank]
    #[Assert\Choice(
        choices: ['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'],
        message: "Le démarrage doit être COLLECTE, TRAITEMENT MANUEL ou TRAITEMENT AUTOMATIQUE"
    )]
    private string $modeCollecte = "TRAITEMENT MANUEL";

    #[ORM\Column(
        name: 'activated',
        type: Types::BOOLEAN,
        nullable: false,
        options: ['comment' => 'Indique si le traitement est activé ou non']
    )]
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    private bool $activated = false;

    #[ORM\Column(
        name: 'success',
        type: Types::BOOLEAN,
        nullable: true,
        options: ['comment' => 'Indique si le traitement a réussi ou échoué']
    )]
    #[Assert\Type('bool')]
    private ?bool $success = null;

    #[ORM\Column(
        name: 'pending',
        type: Types::BOOLEAN,
        nullable: true,
        options: ['comment' => "Indique si le traitement est en attente d'exécution"]
    )]
    #[Assert\Type('bool')]
    private ?bool $pending = null;

    #[ORM\Column(
        name: 'in_progress',
        type: Types::BOOLEAN,
        nullable: false,
        options: ['comment' => "Indique si le traitement est en cours d'exécution"]
    )]
    #[Assert\Type('bool')]
    private bool $inProgress = false;

    #[ORM\Column(
        name: 'titre',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Titre du traitement']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $titre;

    #[ORM\Column(
        name: 'portefeuille',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Nom du portefeuille de projets associé']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $portefeuille = "Aucun";

    #[ORM\Column(
        name: 'nombre_projet',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de projets traités']
    )]
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    private int $nombreProjet = 0;

    #[ORM\Column(
        name: 'responsable',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Responsable du traitement']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $responsable;

    #[ORM\Column(
        name: 'responsable_short',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Identifiant court de l’utilisateur responsable']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $responsableShort;

    #[ORM\Column(
        name: 'debut_traitement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: true,
        options: ['comment' => 'Date et heure de début du traitement']
    )]
    private ?\DateTimeImmutable $debutTraitement = null;

    #[ORM\Column(
        name: 'fin_traitement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: true,
        options: ['comment' => 'Date et heure de fin du traitement']
    )]
    private ?\DateTimeImmutable $finTraitement = null;

    #[ORM\Column(
        name: 'traitement_id',
        type: 'ulid',
        unique: true,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour lier un traitement (ULID sous forme de texte)']
    )]
    private Ulid $traitementId;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du traitement dans le système']
    )]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct(
        string $titre,
        string $portefeuille,
        string $responsable,
        string $responsableShort,
        string $modeCollecte = "TRAITEMENT MANUEL",
        int $nombreProjet = 0
    ) {
        $this->titre = $titre;
        $this->portefeuille = $portefeuille;
        $this->responsable = $responsable;
        $this->responsableShort = $responsableShort;
        $this->modeCollecte = $modeCollecte;
        $this->nombreProjet = $nombreProjet;
        $this->traitementId = new Ulid();
        $this->dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }

    public function setModeCollecte(string $modeCollecte): self
    {
        $this->modeCollecte = $modeCollecte;

        return $this;
    }

    public function isActivated(): ?bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): self
    {
        $this->activated = $activated;

        return $this;
    }

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function isPending(): ?bool
    {
        return $this->pending;
    }

    public function setPending(bool $pending): self
    {
        $this->pending = $pending;

        return $this;
    }

    public function isInProgress(): ?bool
    {
        return $this->inProgress;
    }

    public function setInProgress(bool $inProgress): self
    {
        $this->inProgress = $inProgress;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    public function setPortefeuille(string $portefeuille): self
    {
        $this->portefeuille = $portefeuille;

        return $this;
    }

    public function getNombreProjet(): ?int
    {
        return $this->nombreProjet;
    }

    public function setNombreProjet(int $nombreProjet): self
    {
        $this->nombreProjet = $nombreProjet;

        return $this;
    }

    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    public function setResponsable(string $responsable): self
    {
        $this->responsable = $responsable;

        return $this;
    }

        public function getResponsableShort(): ?string
    {
        return $this->responsableShort;
    }

    public function setResponsableShort(string $responsableShort): self
    {
        $this->responsableShort = $responsableShort;

        return $this;
    }

    public function getDebutTraitement(): ?\DateTimeImmutable
    {
        return $this->debutTraitement;
    }

    public function setDebutTraitement(?\DateTimeImmutable $debutTraitement): self
    {
        $this->debutTraitement = $debutTraitement;

        return $this;
    }

    public function getFinTraitement(): ?\DateTimeImmutable
    {
        return $this->finTraitement;
    }

    public function setFinTraitement(?\DateTimeImmutable $finTraitement): self
    {
        $this->finTraitement = $finTraitement;

        return $this;
    }

    public function getTraitementId(): ?Ulid
    {
        return $this->traitementId;
    }

    public function setTraitementId(Ulid $traitementId): self
    {
        $this->traitementId = $traitementId;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }

}
