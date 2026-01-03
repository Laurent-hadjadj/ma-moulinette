<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ActuatorRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActuatorRepository::class)]
#[ORM\Table(
    name: 'actuator',
    schema: 'ma_moulinette',
    options: ['comment' => 'Configuration d’accès aux endpoints Actuator']
)]
#[ORM\HasLifecycleCallbacks]
class Actuator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique de la table actuator']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        options: ['comment' => 'Clé Maven du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $mavenKey;

    #[ORM\Column(
        name: 'nom_application',
        type: Types::STRING,
        length: 128,
        options: ['comment' => "Nom de l'application"]
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $nomApplication;

    #[ORM\Column(
        name: 'url',
        type: Types::STRING,
        length: 255,
        options: ['comment' => 'URL de base du serveur']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Url]
    private string $url;

    #[ORM\Column(
        name: 'actuator_user',
        type: Types::STRING,
        length: 128,
        nullable: true,
        options: ['comment' => "Nom de l'utilisateur Actuator"]
    )]
    #[Assert\Length(max: 128)]
    private ?string $actuatorUser = null;

    #[ORM\Column(
        name: 'actuator_password',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => "Mot de passe Actuator chiffré"]
    )]
    private ?string $actuatorPassword = null;

    #[ORM\Column(
        name: 'password_encrypted',
        type: Types::BOOLEAN,
        options: [
            'default' => false,
            'comment' => 'Indique si le mot de passe est chiffré']
    )]
    private bool $passwordEncrypted = false;

    #[ORM\Column(
        name: 'personne',
        type: Types::STRING,
        length: 128,
        options: ['comment' => "Prénom et nom du contact"]
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $personne;

    #[ORM\Column(
        name: 'date_modification',
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification']
    )]
    private ?\DateTime $dateModification = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        options: ['comment' => 'Date et heure d’enregistrement']
    )]
    private \DateTimeImmutable $dateEnregistrement;

    /**
     * @var Collection<int, ActuatorInfo>
     */
    #[ORM\OneToMany(
        mappedBy: 'actuator',
        targetEntity: ActuatorInfo::class,
        orphanRemoval: true,
        cascade: ['persist']
    )]
    #[Assert\Count(min: 1)]
    #[Assert\Valid]
    private Collection $actuatorInfo;

    public function __construct()
    {
        $this->actuatorInfo = new ArrayCollection();
        $this->dateEnregistrement = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->dateModification = new \DateTime();
    }


    // ===============================
    // Lifecycle callbacks
    // ===============================

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function encryptPassword(): void
    {
        if ($this->actuatorPassword === null || $this->passwordEncrypted) {
            return;
        }

        $this->actuatorPassword = self::encrypt(
            $this->actuatorPassword,
            $_ENV['ACTUATOR_SECRET_KEY']
        );

        $this->passwordEncrypted = true;
    }

    // ===============================
    // Crypto helpers
    // ===============================

    private static function encrypt(string $plain, string $key): string
    {
        $key = base64_decode(str_replace('base64:', '', $key));
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        $encrypted = openssl_encrypt(
            $plain,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . $encrypted);
    }

    private static function decrypt(string $cipher, string $key): string
    {
        $key = base64_decode(str_replace('base64:', '', $key));
        $data = base64_decode($cipher);

        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        return openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    // ===============================
    // Getter sécurisé
    // ===============================

    public function getActuatorPasswordDecrypted(): ?string
    {
        if ($this->actuatorPassword === null || !$this->passwordEncrypted) {
            return $this->actuatorPassword;
        }

        return self::decrypt(
            $this->actuatorPassword,
            $_ENV['ACTUATOR_SECRET_KEY']
        );
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    public function getNomApplication(): ?string
    {
        return $this->nomApplication;
    }

    public function setNomApplication(string $nomApplication): self
    {
        $this->nomApplication = $nomApplication;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getActuatorUser(): ?string
    {
        return $this->actuatorUser;
    }

    public function setActuatorUser(?string $actuatorUser): self
    {
        $this->actuatorUser = $actuatorUser;

        return $this;
    }

    public function getActuatorPassword(): ?string
    {
        return $this->actuatorPassword;
    }

    public function setActuatorPassword(?string $actuatorPassword): self
    {
        $this->actuatorPassword = $actuatorPassword;

        return $this;
    }

    public function getPersonne(): ?string
    {
        return $this->personne;
    }

    public function setPersonne(string $personne): self
    {
        $this->personne = $personne;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): void
    {
        $this->dateEnregistrement = $dateEnregistrement;
    }

    /**
     * @return Collection<int, ActuatorInfo>
     */
    public function getActuatorInfo(): Collection
    {
        return $this->actuatorInfo;
    }

    public function addActuatorInfo(ActuatorInfo $actuatorInfo): self
    {
        if (!$this->actuatorInfo->contains($actuatorInfo)) {
            $this->actuatorInfo->add($actuatorInfo);
            $actuatorInfo->setActuator($this);
        }

        return $this;
    }

    public function removeActuatorInfo(ActuatorInfo $actuatorInfo): self
    {
        // orphanRemoval=true ⇒ suppression automatique
        $this->actuatorInfo->removeElement($actuatorInfo);

        return $this;
    }
}
