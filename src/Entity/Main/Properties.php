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
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /**  Type = property */
    #[ORM\Column(type: Types::STRING)]
    private $type;

    #[ORM\Column(type: Types::INTEGER)]
    private $projetBd;

    #[ORM\Column(type: Types::INTEGER)]
    private $projetSonar;

    #[ORM\Column(type: Types::INTEGER)]
    private $profilBd;

    #[ORM\Column(type: Types::INTEGER)]
    private $profilSonar;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private $dateCreation;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable:true)]
    private $dateModificationProjet;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable:true)]
    private $dateModificationProfil;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:10:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * [Description for getType]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:10:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * [Description for setType]
     *
     * @param string $type
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * [Description for getProjetBd]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:10:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProjetBd(): ?int
    {
        return $this->projetBd;
    }

    /**
     * [Description for setProjetBd]
     *
     * @param int $projetBd
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setProjetBd(int $projetBd): self
    {
        $this->projetBd = $projetBd;

        return $this;
    }

    /**
     * [Description for getProjetSonar]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:10:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProjetSonar(): ?int
    {
        return $this->projetSonar;
    }

    /**
     * [Description for setProjetSonar]
     *
     * @param int $projetSonar
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setProjetSonar(int $projetSonar): self
    {
        $this->projetSonar = $projetSonar;

        return $this;
    }

    /**
     * [Description for getProfilBd]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:10:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProfilBd(): ?int
    {
        return $this->profilBd;
    }

    /**
     * [Description for setProfilBd]
     *
     * @param int $profilBd
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setProfilBd(int $profilBd): self
    {
        $this->profilBd = $profilBd;

        return $this;
    }

    /**
     * [Description for getProfilSonar]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:10:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProfilSonar(): ?int
    {
        return $this->profilSonar;
    }

    /**
     * [Description for setProfilSonar]
     *
     * @param int $profilSonar
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setProfilSonar(int $profilSonar): self
    {
        $this->profilSonar = $profilSonar;

        return $this;
    }

    /**
     * [Description for getDateCreation]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:10:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    /**
     * [Description for setDateCreation]
     *
     * @param \DateTimeInterface $dateCreation
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * [Description for getDateModificationProjet]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:10:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateModificationProjet(): ?\DateTimeInterface
    {
        return $this->dateModificationProjet;
    }

    /**
     * [Description for setDateModificationProjet]
     *
     * @param \DateTimeInterface|null $dateModificationProjet
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:10:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateModificationProjet(?\DateTimeInterface $dateModificationProjet): self
    {
        $this->dateModificationProjet = $dateModificationProjet;

        return $this;
    }

    /**
     * [Description for getDateModificationProfil]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:11:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateModificationProfil(): ?\DateTimeInterface
    {
        return $this->dateModificationProfil;
    }

    /**
     * [Description for setDateModificationProfil]
     *
     * @param \DateTimeInterface|null $dateModificationProfil
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:11:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateModificationProfil(?\DateTimeInterface $dateModificationProfil): self
    {
        $this->dateModificationProfil = $dateModificationProfil;

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
