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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ActuatorRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ActuatorRepository::class)]
#[ORM\Table(name: "actuator", schema: "ma_moulinette")]
/* @ORM\HasLifecycleCallbacks() */
class Actuator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique de la table'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false,
    options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank]
    #[Assert\Length( max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private $mavenKey;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => "Nom de l'application."])]
    #[Assert\NotBlank]
    #[Assert\Length(max:128, maxMessage: "Le nom de l'application ne doit pas dépasser 128 caractères.")]
    private $nomApplication;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false,
        options: ['comment' => 'URL de base du serveur.'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255, maxMessage: "L'URL ne doit pas dépasser 255 caractères.")]
    private $url;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true,
        options: ['comment' => "Nom de l'utilisateur Actuator"])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128, maxMessage: "Le nom de l'utilisateur Actuator ne doit pas dépasser 128 caractères.")]
    private ?string $actuatorUser=null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable:true,
        options: ['comment' => "Mot de passe de l'utilisateur Actuator"])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128, maxMessage: "Le mot de passe de l'utilisateur Actuator ne doit pas dépasser 128 caractères.")]
    private ?string $actuatorPassword=null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => "Prénom et nom de l'utilisateur"])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128, maxMessage: "Le prénom et nom de l'utilisateur ne doit pas dépasser 128 caractères.")]
    private ?string $personne;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,
    options: ['comment' => 'Date de la dernière modification.'])]
    private $dateModification;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement.'])]
    private $dateEnregistrement;

    /**
     * @var Collection<int, ActuatorInfo>
     */
    #[ORM\OneToMany(mappedBy: "actuator", targetEntity: ActuatorInfo::class, orphanRemoval: true, cascade: ["persist"])]
    #[Assert\Count(min: 1)]
    #[Assert\Valid]
    private $actuatorInfo;
    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ActuatorInfo", mappedBy="actuator")
     */
    public function __construct()
    {
        $this->actuatorInfo = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): static
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    public function getNomApplication(): ?string
    {
        return $this->nomApplication;
    }

    public function setNomApplication(string $nomApplication): static
    {
        $this->nomApplication = $nomApplication;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getActuatorUser(): ?string
    {
        return $this->actuatorUser;
    }

    public function setActuatorUser(string $actuatorUser): static
    {
        $this->actuatorUser = $actuatorUser;

        return $this;
    }

    public function getActuatorPassword(): ?string
    {
        return $this->actuatorPassword;
    }

    public function setActuatorPassword(string $actuatorPassword): static
    {
        $this->actuatorPassword = $actuatorPassword;

        return $this;
    }

    public function getPersonne(): ?string
    {
        return $this->personne;
    }

    public function setPersonne(string $personne): static
    {
        $this->personne = $personne;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate(): void
    {
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(): void
    {
        $this->dateEnregistrement = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, ActuatorInfo>
     */
    public function getActuatorInfo(): Collection
    {
        return $this->actuatorInfo;
    }

    public function addActuatorInfo(ActuatorInfo $actuatorInfo): self
    {
        if (!$this->actuatorInfo->contains($actuatorInfo)) {
            $this->actuatorInfo[] = $actuatorInfo;
            $actuatorInfo->setActuator($this);
        }

        return $this;
    }

    public function removeActuatorInfo(ActuatorInfo $actuatorInfo): self
    {
        if ($this->actuatorInfo->removeElement($actuatorInfo)) {
            // set the owning side to null (unless already changed)
            if ($actuatorInfo->getActuator() === $this) {
                $actuatorInfo->setActuator(null);
            }
        }

        return $this;
    }

}
