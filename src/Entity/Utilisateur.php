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

use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: "utilisateur", schema: "ma_moulinette")]
#[UniqueEntity(fields: ['courriel'], message: 'There is already an account with this courriel')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => "clé unique de la table"]
        )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => "Prénom de l'utilisateur"]
        )]
    #[Assert\NotBlank]
    private $prenom;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => "Nom de l'utilisateur"]
        )]
    #[Assert\NotBlank]
    private $nom;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: true,
        options: ['comment' => "Avatar de l'utilisateur"]
        )]
    private $avatar;

    #[ORM\Column(
        type: Types::STRING,
        length: 320,
        nullable: false,
        unique: true,
        options: ['comment' => "Adresse de courriel, clé unique"]
        )]
    #[Assert\NotBlank]
    private $courriel;

    #[ORM\Column(
        type: Types::JSON, /* le type array est deprecated */
        nullable: true,
        options: ['comment' => "Liste des rôles"]
        )]
    #[Assert\NotBlank]
    private $roles = [];

    #[ORM\Column(
        type: Types::JSON, /* le type array est deprecated */
        nullable: true,
        options: ['comment' => "Liste des équipes"]
        )]
    #[Assert\NotNull]
    private $equipe = [];

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => "Mot de passe de l'utilisateur"]
        )]
    #[Assert\NotBlank]
    private $password;

    #[ORM\Column(
        type: Types::BOOLEAN,
        nullable: false,
        options: ['default' => 0, 'comment' => "L'utilisateur est désactivé"]
        )]
        #[Assert\NotNull]
    private $actif;

    # Préférences de l'utilisateur
    #[ORM\Column(
        type: Types::JSON,
        nullable: false,
        options: ['comment' => "Préférences de l'utilisateur"]
        )]
    #[Assert\NotNull]
    private $preference = [];

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => "Indicateur de réinitialisation du mot de passe"]
        )]
    #[Assert\NotNull]
    private $init=0;

    #[ORM\Column(
        type: Types::DATETIMETZ_MUTABLE,
        nullable: true,
        options: ['comment' => "Date de modification"]
        )]
        #[Assert\NotNull]
    private $dateModification;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date de création"]
        )]
        #[Assert\NotNull]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * [Description for getCourriel]
     *
     * @return string|null
     *
     * Created at: 14/02/2023, 00:10:16 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCourriel(): ?string
    {
        return $this->courriel;
    }

    /**
     * [Description for setCourriel]
     *
     * @param string $courriel
     *
     * @return self
     *
     * Created at: 14/02/2023, 00:10:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCourriel(string $courriel): self
    {
        $this->courriel = $courriel;

        return $this;
    }

    /**
     * [Description for getUserIdentifier]
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @return string
     *
     * Created at: 14/02/2023, 00:10:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->courriel;
    }

    // on ne veut pas que l'utilisateur ait un rôle par défaut.
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * [Description for setRoles]
     *
     * @param array $roles
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:11:49 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * [Description for getPassword]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:11:51 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * [Description for setPassword]
     *
     * @param string $password
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:11:53 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * [Description for setActif]
     *
     * @param bool $actif
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:11:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * [Description for getDateModification]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:11:57 (Europe/Paris)
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
     * @param \DateTimeInterface $dateModification
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:11:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateModification(\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 18:12:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:12:02 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateEnregistrement(\DateTimeInterface $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     *
     */
    public function eraseCredentials():void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * [Description for isActif]
     *
     * @return bool|null
     *
     * Created at: 02/01/2023, 18:12:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isActif(): ?bool
    {
        return $this->actif;
    }

    /**
     * [Description for getPrenom]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:12:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    /**
     * [Description for setPrenom]
     *
     * @param string $prenom
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:12:14 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * [Description for getNom]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:12:18 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * [Description for setNom]
     *
     * @param string $nom
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:12:22 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * [Description for getPersonne]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:12:24 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getPersonne(): ?string
    {
        return trim("$this->nom $this->prenom");
    }

    /**
     * [Description for getAvatar]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:12:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * [Description for setAvatar]
     *
     * @param string|null $avatar
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:12:27 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * [Description for getAvatarUrl]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 18:12:29 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAvatarUrl(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        return sprintf('build/avatar/%s', $this->avatar);
    }

    /**
     * [Description for getEquipe]
     *
     * @return array
     *
     * Created at: 02/01/2023, 18:12:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getEquipe(): ?array
    {
        return $this->equipe;
    }

    /**
     * [Description for setEquipe]
     *
     * @param array $equipe
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:12:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setEquipe(array $equipe): self
    {
        $this->equipe = $equipe;

        return $this;
    }


    /**
     * [Description for getPreference]
     *
     * @return array
     *
     * Created at: 28/03/2023, 08:18:42 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getPreference(): ?array
    {
        return $this->preference;
    }

    /**
     * [Description for setPreference]
     *
     * @param array $preference
     *
     * @return self
     *
     * Created at: 28/03/2023, 08:18:51 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setPreference(array $preference): self
    {
        $this->preference = $preference;

        return $this;
    }

    /**
     * [Description for getInit]
     *
     * @return int|null
     *
     * Created at: 31/01/2024 09:15:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getInit(): ?int
    {
        return $this->init;
    }

    /**
     * [Description for setInt]
     *
     * @param int $init
     *
     * @return self
     *
     * Created at: 31/01/2024 09:15:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setInit(int $init): self
    {
        $this->init = $init;

        return $this;
    }

}
