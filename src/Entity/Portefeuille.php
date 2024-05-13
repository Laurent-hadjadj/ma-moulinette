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

use App\Repository\PortefeuilleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use App\Validator as AcmeAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PortefeuilleRepository::class)]
class Portefeuille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque portefeuille']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        unique: true,
        options: ['comment' => 'Titre unique du portefeuille']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    #[AcmeAssert\ContainsPortefeuilleUnique]
    //AcmeAssert\ContainsPortefeuilleUnique : Une validation personnalisée pour s'assurer que chaque titre de portefeuille est unique dans la base de données, prévenant les doublons.
    private $titre;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Nom de l\'équipe associée au portefeuille']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private $equipe;

    #[ORM\Column(
        type: 'json',
        options: ['comment' => 'Liste des éléments ou des activités du portefeuille']
    )]
    private $liste = [];

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification du portefeuille']
    )]
    #[Assert\NotNull]
    private $dateModification;

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: false,
        options: ['comment' => 'Date d\'enregistrement du portefeuille']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    /**
     * [Description for getId]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:09:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * [Description for getTitre]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:09:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getTitre(): ?string
    {
        return $this->titre;
    }

    /**
     * [Description for setTitre]
     *
     * @param string $titre
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:09:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * [Description for getEquipe]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:09:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getEquipe(): ?string
    {
        return $this->equipe;
    }

    /**
     * [Description for setEquipe]
     *
     * @param string $equipe
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:09:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setEquipe(string $equipe): self
    {
        $this->equipe = $equipe;

        return $this;
    }


    /**
     * [Description for getListe]
     *
     * @return array
     *
     * Created at: 02/01/2023, 18:09:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getListe(): array
    {
        return $this->liste;
    }

    /**
     * [Description for setListe]
     *
     * @param array $liste
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:09:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setListe(array $liste): self
    {
        $this->liste = $liste;

        return $this;
    }

    /**
     * [Description for getDateModification]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:09:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    /**
     * [Description for setDateModification]
     *
     * @param \DateTimeInterface|null $dateModification
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:09:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateModification(?\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:09:46 (Europe/Paris)
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
     * Created at: 02/01/2023, 18:09:48 (Europe/Paris)
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
