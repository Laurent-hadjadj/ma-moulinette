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

use App\Repository\ProfilesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfilesRepository::class)]
class Profiles

{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    private $key;

    #[ORM\Column(type: 'string', length: 128)]
    private $name;

    #[ORM\Column(type: 'string', length: 64)]
    private $language_name;

    #[ORM\Column(type: 'integer')]
    private $active_rule_count;

    #[ORM\Column(type: 'datetime')]
    private $rules_update_at;

    #[ORM\Column(type: 'boolean')]
    private $is_default;

    #[ORM\Column(type: 'datetime')]
    private $date_enregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLanguageName(): ?string
    {
        return $this->language_name;
    }

    public function setLanguageName(string $language_name): self
    {
        $this->language_name = $language_name;

        return $this;
    }

    public function getActiveRuleCount(): ?int
    {
        return $this->active_rule_count;
    }

    public function setActiveRuleCount(int $active_rule_count): self
    {
        $this->active_rule_count = $active_rule_count;

        return $this;
    }

    public function getRulesUpdateAt(): ?\DateTimeInterface
    {
        return $this->rules_update_at;
    }

    public function setRulesUpdateAt(\DateTimeInterface $rules_update_at): self
    {
        $this->rules_update_at = $rules_update_at;

        return $this;
    }

    public function getIsDefault()
    {
        return $this->is_default;
    }

    public function setIsDefault($is_default): self
    {
        $this->is_default = $is_default;

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
