<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
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
#[UniqueEntity(
    fields: ['courriel'],
    message: "La création de votre compte n'a pas aboutie.")]
#[ORM\Table(
    name: "utilisateur",
    schema: "ma_moulinette",
    options: ['comment' => "table des utilisateurs"])]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => "clé unique de la table utilisateur"])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'prenom',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => "Prénom de l'utilisateur"])]
    #[Assert\NotBlank(groups: ['form', 'default'])]
    private ?string $prenom = null;

    #[ORM\Column(
        name: 'nom',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => "Nom de l'utilisateur"])]
    #[Assert\NotBlank(groups: ['form', 'default'])]
    private ?string $nom = null;

    #[ORM\Column(
        name: 'avatar',
        type: Types::STRING,
        length: 128,
        nullable: true,
        options: ['comment' => "Avatar de l'utilisateur"])]
    private ?string $avatar = null;

    #[ORM\Column(
        name: 'courriel',
        type: Types::STRING,
        length: 320,
        unique: true,
        options: ['comment' => "Adresse de courriel, clé unique"])]
    #[Assert\NotBlank(groups: ['form', 'default'])]
    #[Assert\Email(groups: ['form', 'default'])]
    private ?string $courriel = null;

    #[ORM\Column(
        name: 'roles',
        type: Types::JSON,
        nullable: false,
        options: ['comment' => "Liste des rôles"])]
    private array $roles = [];

    #[ORM\Column(
        name: 'groupe',
        type: Types::JSON,
        nullable: true,
        options: ['comment' => "Liste des équipes"])]
    private array $groupe = [];

    #[ORM\Column(
        name: 'password',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => "Mot de passe de l'utilisateur"])]
    #[Assert\NotBlank(groups: ['default'])]
    private string $password;

    #[ORM\Column(
        name: 'actif',
        type: Types::BOOLEAN,
        nullable: false,
        options: [
            'default' => false,
            'comment' => "L'utilisateur est désactivé"])]
    private bool $actif = false;

    #[ORM\Column(
        name: 'preference',
        type: Types::JSON,
        nullable: false,
        options: ['comment' => "Préférences de l'utilisateur"])]
    private array $preference = [];

    #[ORM\Column(
        name: 'reset_password',
        type: Types::BOOLEAN, nullable: false,
        options: [
            'default' => true,
            'comment' => "Indicateur de réinitialisation du mot de passe"])]
    private bool $resetPassword = true;

    #[ORM\Column(
        name: 'reset_password_count',
        type: Types::INTEGER, nullable: false,
        options: [
            'default' => 1,
            'comment' => 'Nombre de tentative de changement du mot de passe.'])]
    private int $resetPasswordCount = 1;

    #[ORM\Column(
        name: 'date_modification',
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => "Date de modification"])]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(
        name: 'last_activity_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: ['comment' => "Date de modification"])]
    private ?\DateTimeImmutable $lastActivityAt = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date de création"])]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->roles = [];
        $this->groupe = [];
        $this->preference = [];
        $this->resetPasswordCount = 1;
        $this->resetPassword = true;
        $this->actif = false;
        $this->lastActivityAt = new \DateTimeImmutable();
        $this->dateEnregistrement = new \DateTimeImmutable();
    }

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
    public function setCourriel(string $courriel): \App\Entity\Utilisateur
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
    public function setRoles(array $roles): \App\Entity\Utilisateur
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
    public function setPassword(string $password): \App\Entity\Utilisateur
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
    public function setActif(bool $actif): \App\Entity\Utilisateur
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
     * @return \App\Entity\Utilisateur
     *
     * Created at: 04/09/2025 08:46:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateModification(\DateTimeInterface $dateModification): \App\Entity\Utilisateur
    {
            if ($dateModification instanceof \DateTimeImmutable) {
                    $dateModification = \DateTime::createFromImmutable($dateModification);
            }
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
    public function setDateEnregistrement(\DateTimeInterface $dateEnregistrement): \App\Entity\Utilisateur
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
    public function setPrenom(string $prenom): \App\Entity\Utilisateur
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
    public function setNom(string $nom): \App\Entity\Utilisateur
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
    public function setAvatar(?string $avatar): \App\Entity\Utilisateur
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

        return sprintf('/avatar/%s', $this->avatar);

    }

    /**
     * [Description for getGroupe]
     *
     * @return array
     *
     * Created at: 02/01/2023, 18:12:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getGroupe(): array
    {
        return $this->groupe;
    }

    /**
     * [Description for setGroupe]
     *
     * @param array $groupe
     *
     * @return self
     *
     * Created at: 02/01/2023, 18:12:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setGroupe(array $groupe): \App\Entity\Utilisateur
    {
        $this->groupe = $groupe;

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
    public function getPreference(): array
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
    public function setPreference(array $preference): \App\Entity\Utilisateur
    {
        $this->preference = $preference;

        return $this;
    }

    public function isResetPassword(): ?bool
    {
        return $this->resetPassword;
    }

    public function setResetPassword(?bool $resetPassword): static
    {
        $this->resetPassword = $resetPassword;

        return $this;
    }

    public function getResetPasswordCount(): ?int
    {
        return $this->resetPasswordCount;
    }

    public function setResetPasswordCount(int $resetPasswordCount): static
    {
        $this->resetPasswordCount = $resetPasswordCount
        ;

        return $this;
    }

    public function getLastActivityAt(): ?\DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeImmutable $lastActivityAt): self
    {
        $this->lastActivityAt = $lastActivityAt;
        return $this;
    }

}
