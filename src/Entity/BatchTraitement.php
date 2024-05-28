<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
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
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BatchTraitementRepository::class)]
#[ORM\Table(name: "batch_traitement", schema: "ma_moulinette")]
class BatchTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique du traitement']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Mode de démarrage du traitement']
    )]
    ##[Assert\Choice(choices: ["Manuel", "Automatique"], message: "Le démarrage doit être 'Manuel' ou 'Automatique'")]
    #[Assert\NotBlank]
    private $demarrage = "Manuel";

    #[ORM\Column(
        type: 'boolean',
        nullable: false,
        options: ['comment' => 'Indique si le traitement a réussi ou échoué']
    )]
    #[Assert\Type(
        type: 'bool',
        message: "Le résultat doit être un booléen."
    )]
    #[Assert\NotNull]
    private $resultat = false;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Titre du traitement']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le titre ne doit pas dépasser 32 caractères."
    )]
    private $titre;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Nom du portefeuille de projets associé']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le nom du portefeuille ne doit pas dépasser 32 caractères."
    )]
    private $portefeuille = "Aucun";

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de projets traités']
    )]
    #[Assert\NotNull]
    #[Assert\Type(
        type: 'integer',
        message: "Le nombre de projets doit être un entier."
    )]
    private $nombreProjet = 0;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Responsable du traitement']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom du responsable ne doit pas dépasser 128 caractères."
    )]
    private $responsable;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: true,
        options: ['comment' => 'Date et heure de début du traitement']
    )]
    #[Assert\NotNull]
    private $debutTraitement;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: true,
        options: ['comment' => 'Date et heure de fin du traitement']
    )]
    #[Assert\NotNull]
    private $finTraitement;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du traitement dans le système']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:51:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * [Description for getDemarrage]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:51:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDemarrage(): ?string
    {
        return $this->demarrage;
    }

    /**
     * [Description for setDemarrage]
     *
     * @param string $demarrage
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:51:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDemarrage(string $demarrage): self
    {
        $this->demarrage = $demarrage;

        return $this;
    }

    /**
     * [Description for isResultat]
     *
     * @return bool|null
     *
     * Created at: 02/01/2023, 17:51:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isResultat(): ?bool
    {
        return $this->resultat;
    }

    /**
     * [Description for setResultat]
     *
     * @param bool $resultat
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setResultat(bool $resultat): self
    {
        $this->resultat = $resultat;

        return $this;
    }

    /**
     * [Description for getTitre]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:52:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getTitre(): ?string
    {
        return $this->titre;
    }

    /**
     * [Description for setTitre]
     *
     * @param string $titre
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * [Description for getPortefeuille]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:52:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    /**
     * [Description for setPortefeuille]
     *
     * @param string $portefeuille
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setPortefeuille(string $portefeuille): self
    {
        $this->portefeuille = $portefeuille;

        return $this;
    }

    /**
     * [Description for getNombreProjet]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:52:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreProjet(): ?int
    {
        return $this->nombreProjet;
    }

    /**
     * [Description for setNombreProjet]
     *
     * @param int $nombreProjet
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreProjet(int $nombreProjet): self
    {
        $this->nombreProjet = $nombreProjet;

        return $this;
    }

    /**
     * [Description for getResponsable]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:52:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    /**
     * [Description for setResponsable]
     *
     * @param string $responsable
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setResponsable(string $responsable): self
    {
        $this->responsable = $responsable;

        return $this;
    }

    /**
     * [Description for getDebutTraitement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:52:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDebutTraitement(): ?\DateTimeInterface
    {
        return $this->debutTraitement;
    }

    /**
     * [Description for setDebutTraitement]
     *
     * @param \DateTimeInterface|null $debutTraitement
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDebutTraitement(?\DateTimeInterface $debutTraitement): self
    {
        $this->debutTraitement = $debutTraitement;

        return $this;
    }

    /**
     * [Description for getFinTraitement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:52:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getFinTraitement(): ?\DateTimeInterface
    {
        return $this->finTraitement;
    }

    /**
     * [Description for setFinTraitement]
     *
     * @param \DateTimeInterface|null $finTraitement
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setFinTraitement(?\DateTimeInterface $finTraitement): self
    {
        $this->finTraitement = $finTraitement;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:52:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateEnregistrement(): ?\DateTimeInterface
    {
        return $this->dateEnregistrement;
    }

    /**
     * [Description for setDateEnregistrement]
     *
     * @param \DateTimeInterface $dateEnregistrement
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:52:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateEnregistrement(\DateTimeInterface $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }


    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
