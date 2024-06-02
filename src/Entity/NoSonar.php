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

use App\Repository\NoSonarRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NoSonarRepository::class)]
#[ORM\Table(name: "no_sonar", schema: "ma_moulinette")]
class NoSonar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique la table NoSonar'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private $mavenKey;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => 'Règle NoSonar appliquée'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128,
        maxMessage: "La règle nosonar ne doit pas dépasser 128 caractères.")]
    private $rule;

    #[ORM\Column(type: Types::TEXT, nullable: false,
        options: ['comment' => 'Composant auquel la règle est appliquée'])]
    #[Assert\NotBlank]
    private $component;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Ligne où la règle NoSonar est appliquée'])]
    #[Assert\NotNull]
    private $line;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true,
    options: ['comment' => 'Mode de collete : MANUEL | AUTOMATIQUE'])]
    #[Assert\Length(max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères.")]
    private ?string $modeCollecte=null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true,
    options: ['comment' => "Nom de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom de l'utilisatzeur ne peut pas dépasser 128 caractères.")]
    private ?string $utilisateurCollecte=null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement de l’entrée NoSonar'])]
    #[Assert\NotNull]
    private $dateEnregistrement;

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

    public function getRule(): ?string
    {
        return $this->rule;
    }

    public function setRule(string $rule): static
    {
        $this->rule = $rule;

        return $this;
    }

    public function getComponent(): ?string
    {
        return $this->component;
    }

    public function setComponent(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function setLine(int $line): static
    {
        $this->line = $line;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): static
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }

    public function setModeCollecte(?string $modeCollecte): static
    {
        $this->modeCollecte = $modeCollecte;

        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }

    public function setUtilisateurCollecte(?string $utilisateurCollecte): static
    {
        $this->utilisateurCollecte = $utilisateurCollecte;

        return $this;
    }

}
