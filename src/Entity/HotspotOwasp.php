<?php
/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

namespace App\Entity;

use App\Repository\HotspotOwaspRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HotspotOwaspRepository::class)]
class HotspotOwasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    private $maven_key;

    #[ORM\Column(type: 'string', length: 8)]
    private $menace;

    #[ORM\Column(type: 'string', length: 8)]
    private $probability;

    #[ORM\Column(type: 'string', length: 16)]
    private $status;

    #[ORM\Column(type: 'integer')]
    private $niveau;

    #[ORM\Column(type: 'datetime')]
    private $date_enregistrement;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMenace(): ?string
    {
        return $this->menace;
    }

    public function setMenace(string $menace): self
    {
        $this->menace = $menace;

        return $this;
    }

    public function getProbability(): ?string
    {
        return $this->probability;
    }

    public function setProbability(string $probability): self
    {
        $this->probability = $probability;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    public function setNiveau(int $niveau): self
    {
        $this->niveau = $niveau;

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
