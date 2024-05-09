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

use App\Repository\Main\ProfilesRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfilesRepository::class)]
class Profiles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 128)]
    #[Assert\NotBlank]
    private $key;

    #[ORM\Column(type: Types::STRING, length: 128)]
    #[Assert\NotBlank]
    private $name;

    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Assert\NotBlank]
    private $languageName;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank]
    private $activeRuleCount;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $rulesUpdateAt;

    #[ORM\Column(type: TYPES::BOOLEAN)]
    #[Assert\NotBlank]
    private $isDefault;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setRulesUpdateAt(\DateTimeInterface $rulesUpdateAt): self
    {
        $this->rulesUpdateAt = $rulesUpdateAt;

        return $this;
    }

    /**
     * [Description for isDefault]
     *
     * @return bool|null
     *
     * Created at: 02/01/2023, 18:10:18 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    /**
     * [Description for setDefault]
     *
     * @param bool $isDefault
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDefault(bool $default): self
    {
        $this->isDefault = $default;

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
