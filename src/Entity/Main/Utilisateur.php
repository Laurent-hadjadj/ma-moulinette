<?php

namespace App\Entity\Main;

use App\Repository\Main\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity(fields: ['courriel'], message: 'There is already an account with this courriel')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 32)]
    private $prenom;

    #[ORM\Column(type: 'string', length: 64)]
    private $nom;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private $avatar;

    #[ORM\Column(type: 'string', length: 320, unique: true)]
    private $courriel;

    #[ORM\Column(type: 'json')]
    private $roles= [];

    #[ORM\Column(type: 'json')]
    private $equipe= [];

    #[ORM\Column(type: 'string', length: 64)]
    private $password;

    #[ORM\Column(type: 'boolean', nullable: false,  options: ['default' => 0]) ]
    private $actif;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateModification;

    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourriel(): ?string
    {
        return $this->courriel;
    }

    public function setCourriel(string $courriel): self
    {
        $this->courriel = $courriel;

        return $this;
    }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->courriel;
    }

    // on ne veut pas que l'utilisateur aut un rôle par défaut.
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getActif()
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;

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
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPersonne(): ?string
    {
        return $this->nom.' '.$this->prenom;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        return sprintf('build/avatar/%s', $this->avatar);
    }

    public function getEquipe(): array
    {
        return $this->equipe;
    }

    public function setEquipe(array $equipe): self
    {
        $this->equipe = $equipe;

        return $this;
    }

}
