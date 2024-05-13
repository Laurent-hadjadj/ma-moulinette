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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProfilesRepository::class)]
class Profiles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque profil']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé unique du profil']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private $key;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du profil']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private $name;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Nom du langage de programmation']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private $languageName;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de règles actives associées au profil']
    )]
    #[Assert\NotNull]
    private $activeRuleCount;

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: false,
        options: ['comment' => 'Date de la dernière mise à jour des règles']
    )]
    #[Assert\NotNull]
    private $rulesUpdateAt;

    #[ORM\Column(
        type: TYPES::BOOLEAN,
        nullable: false,
        options: ['comment' => 'Indique si le profil est le profil par défaut']
    )]
    #[Assert\NotNull]
    private $referentielDefault;

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: false,
        options: ['comment' => 'Date d\'enregistrement du profil']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:09:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * [Description for getKey]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:10:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * [Description for setKey]
     *
     * @param string $key
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * [Description for getName]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:10:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * [Description for setName]
     *
     * @param string $name
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * [Description for getLanguageName]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:10:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getLanguageName(): ?string
    {
        return $this->languageName;
    }

    /**
     * [Description for setLanguageName]
     *
     * @param string $languageName
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setLanguageName(string $languageName): self
    {
        $this->languageName = $languageName;

        return $this;
    }

    /**
     * [Description for getActiveRuleCount]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:10:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getActiveRuleCount(): ?int
    {
        return $this->activeRuleCount;
    }

    /**
     * [Description for setActiveRuleCount]
     *
     * @param int $activeRuleCount
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setActiveRuleCount(int $activeRuleCount): self
    {
        $this->activeRuleCount = $activeRuleCount;

        return $this;
    }

    /**
     * [Description for getRulesUpdateAt]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:10:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getRulesUpdateAt(): ?\DateTimeInterface
    {
        return $this->rulesUpdateAt;
    }


    /**
     * [Description for setRulesUpdateAt]
     *
     * @param \DateTimeInterface $rulesUpdateAt
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setRulesUpdateAt(\DateTimeInterface $rulesUpdateAt): self
    {
        $this->rulesUpdateAt = $rulesUpdateAt;

        return $this;
    }

    /**
     * [Description for isreferentielDefault]
     *
     * @return bool|null
     *
     * Created at: 02/01/2023, 18:10:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isreferentielDefault(): ?bool
    {
        return $this->referentielDefault;
    }

    /**
     * [Description for setReferentielDefault]
     *
     * @param bool $referentielDefault
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setReferentielDefault(bool $referentielDefault): self
    {
        $this->referentielDefault = $referentielDefault;

        return $this;
    }


    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:10:23 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:10:24 (Europe/Paris)
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
