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

use App\Repository\Main\ProfilesHistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfilesHistoriqueRepository::class)]
class ProfilesHistorique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    private $dateCourte = null;

    #[ORM\Column(type: Types::STRING, length: 16)]
    #[Assert\NotBlank]
    private $language = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    private $date = null;

    #[ORM\Column(type: Types::STRING, length: 16)]
    #[Assert\NotBlank]
    private $action = null;

    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Assert\NotBlank]
    private $auteur = null;

    #[ORM\Column(type: Types::STRING, length: 128)]
    #[Assert\NotBlank]
    private $regle = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private $description = null;

    #[ORM\Column(type: Types::BLOB)]
    #[Assert\NotBlank]
    private $detail = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    private $dateEnregistrement = null;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 20/02/2024 17:08:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * [Description for getDateCourte]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 20/02/2024 17:08:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateCourte(): ?\DateTimeInterface
    {
        return $this->dateCourte;
    }

    /**
     * [Description for setDateCourte]
     *
     * @param \DateTimeInterface $dateCourte
     *
     * @return static
     *
     * Created at: 20/02/2024 17:09:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateCourte(\DateTimeInterface $dateCourte): static
    {
        $this->dateCourte = $dateCourte;

        return $this;
    }

    /**
     * [Description for getLanguage]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:09:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * [Description for setLangage]
     *
     * @param string $language
     *
     * @return static
     *
     * Created at: 20/02/2024 17:09:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * [Description for getDate]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 20/02/2024 17:09:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * [Description for setDate]
     *
     * @param \DateTimeInterface $date
     *
     * @return static
     *
     * Created at: 20/02/2024 17:09:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * [Description for getAuteur]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:09:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    /**
     * [Description for setAuteur]
     *
     * @param string $auteur
     *
     * @return static
     *
     * Created at: 20/02/2024 17:09:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setAuteur(string $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    /**
     * [Description for getRegle]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:09:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getRegle(): ?string
    {
        return $this->regle;
    }

    /**
     * [Description for setRegle]
     *
     * @param string $regle
     *
     * @return static
     *
     * Created at: 20/02/2024 17:09:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setRegle(string $regle): static
    {
        $this->regle = $regle;

        return $this;
    }

    /**
     * [Description for getDescription]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:09:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * [Description for setDescription]
     *
     * @param string $description
     *
     * @return static
     *
     * Created at: 20/02/2024 17:09:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * [Description for getDetail]
     *
     * @return [type]
     *
     * Created at: 20/02/2024 17:09:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * [Description for setDetail]
     *
     * @param mixed $detail
     *
     * @return static
     *
     * Created at: 20/02/2024 17:09:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDetail($detail): static
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * [Description for getAction]
     *
     * @return string|null
     *
     * Created at: 20/02/2024 17:09:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * [Description for setAction]
     *
     * @param string $action
     *
     * @return static
     *
     * Created at: 20/02/2024 17:09:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 20/02/2024 17:09:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * @return static
     *
     * Created at: 20/02/2024 17:09:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateEnregistrement(\DateTimeInterface $dateEnregistrement): static
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }
}
