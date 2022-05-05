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

use App\Repository\InformationProjetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InformationProjetRepository::class)]
class InformationProjet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    private $maven_key;

    #[ORM\Column(type: 'string', length: 32)]
    private $analyse_key;

    #[ORM\Column(type: 'datetime')]
    private $date;

    #[ORM\Column(type: 'string', length: 32)]
    private $project_version;

    #[ORM\Column(type: 'string', length: 32)]
    private $type;

    #[ORM\Column(type: 'datetime')]
    private $date_enregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnalyseKey(): ?string
    {
        return $this->analyse_key;
    }

    public function setAnalyseKey(string $analyse_key): self
    {
        $this->analyse_key = $analyse_key;

        return $this;
    }

    public function getMavenKey(): ?string
    {
        return $this->maven_key;
    }

    public function setMavenKey(string $maven_key): self
    {
        $this->maven_key = $maven_key;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getProjectVersion(): ?string
    {
        return $this->project_version;
    }

    public function setProjectVersion(string $project_version): self
    {
        $this->project_version = $project_version;

        return $this;
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

    public function getDateEnregistrement(): ?\DateTimeInterface
    {
        return $this->date_enregistrement;
    }

    public function setDateEnregistrement(\DateTimeInterface $date_enregistrement): self
    {
        $this->date_enregistrement = $date_enregistrement;

        return $this;
    }
}
