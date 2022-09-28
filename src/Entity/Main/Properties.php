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

namespace App\Entity\Main;

use App\Repository\Main\PropertiesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertiesRepository::class)]
class Properties
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**  Type = property */
    #[ORM\Column(type: 'string')]
    private $type;

    #[ORM\Column(type: 'integer')]
    private $projet_bd;

    #[ORM\Column(type: 'integer')]
    private $projet_sonar;

    #[ORM\Column(type: 'integer')]
    private $profil_bd;

    #[ORM\Column(type: 'integer')]
    private $profil_sonar;

    #[ORM\Column(type: 'datetime')]
    private $date_creation;

    #[ORM\Column(type: 'datetime', nullable:true)]
    private $date_modification_projet;

    #[ORM\Column(type: 'datetime', nullable:true)]
    private $date_modification_profil;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getProjetBd(): ?int
    {
        return $this->projet_bd;
    }

    public function setProjetBd(int $projet_bd): self
    {
        $this->projet_bd = $projet_bd;

        return $this;
    }

    public function getProjetSonar(): ?int
    {
        return $this->projet_sonar;
    }

    public function setProjetSonar(int $projet_sonar): self
    {
        $this->projet_sonar = $projet_sonar;

        return $this;
    }

    public function getProfilBd(): ?int
    {
        return $this->profil_bd;
    }

    public function setProfilBd(int $profil_bd): self
    {
        $this->profil_bd = $profil_bd;

        return $this;
    }

    public function getProfilSonar(): ?int
    {
        return $this->profil_sonar;
    }

    public function setProfilSonar(int $profil_sonar): self
    {
        $this->profil_sonar = $profil_sonar;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    public function getDateModificationProjet(): ?\DateTimeInterface
    {
        return $this->date_modification_projet;
    }

    public function setDateModificationProjet(?\DateTimeInterface $date_modification_projet): self
    {
        $this->date_modification_projet = $date_modification_projet;

        return $this;
    }

    public function getDateModificationProfil(): ?\DateTimeInterface
    {
        return $this->date_modification_profil;
    }

    public function setDateModificationProfil(?\DateTimeInterface $date_modification_profil): self
    {
        $this->date_modification_profil = $date_modification_profil;

        return $this;
    }

}
