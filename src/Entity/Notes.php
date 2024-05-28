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

use App\Repository\NotesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NotesRepository::class)]
#[ORM\Table(name: "notes", schema: "ma_moulinette")]
class Notes
{
    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven unique identifiant la note']
    )]
    private $mavenKey;

    #[ORM\Id]
    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Type de la note']
    )]
    #[Assert\NotBlank]
    private $type;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Valeur de la note']
    )]
    #[Assert\NotNull]
    private $value;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d\'enregistrement de la note']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    /**
     * [Description for getMavenKey]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:04:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    /**
     * [Description for getType]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:04:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getType(): ?string
    {
        return $this->type;
    }


    /**
     * [Description for getValue]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 18:04:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getValue(): ?int
    {
        return $this->value;
    }

    /**
     * [Description for setValue]
     *
     * @param int $value
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:04:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }


    public function getDateEnregistrement(): ?\DateTimeInterface
    {
        return $this->dateEnregistrement;
    }


    public function setDateEnregistrement(\DateTimeInterface $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }


    /**
     * Set the value of mavenKey
     *
     * @return  self
     */
    public function setMavenKey($mavenKey)
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
