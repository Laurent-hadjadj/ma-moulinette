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

use App\Repository\HistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HistoriqueRepository::class)]
#[ORM\Table(name: "historique", schema: "ma_moulinette")]
class Historique
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 255,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\Length(max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private $mavenKey;

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 32,
        options: ['comment' => 'Version du projet dans l’historique'])]
    #[Assert\Length(max: 32,
        maxMessage: "La version du projet ne doit pas dépasser 32 caractères.")]
    #[Assert\NotBlank]
    private $version;

    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 128,
        options: ['comment' => 'Date de la version du projet'])]
    #[Assert\Length(max: 128,
        maxMessage: "La date de la version ne doit pas dépasser 128 caractères.")]
    #[Assert\NotBlank]
    private $dateVersion;

    #[ORM\Column(type: Types::STRING, length: 128,
        options: ['comment' => 'Nom du projet associé à cette version'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom du projet ne doit pas dépasser 128 caractères.")]
    private $nomProjet;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Indicateur de release pour la version spécifique'])]
    #[Assert\NotNull]
    private $versionRelease;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Indicateur de snapshot pour la version spécifique'])]
    #[Assert\NotNull]
    private $versionSnapshot;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Indicateur pour les autres types de versions'])]
    #[Assert\NotNull]
    private $versionAutre;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Compteur des suppressions d’avertissements'])]
    #[Assert\NotNull]
    private $suppressWarning;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Compteur de l’utilisation de NoSonar'])]
    #[Assert\NotNull]
    private $noSonar;


    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Compteur de l’utilisation de Todo'])]
    #[Assert\NotNull]
    private $todo;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre total de lignes dans le projet'])]
    #[Assert\NotNull]
    private $nombreLigne;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre total de lignes de code dans le projet'])]
    #[Assert\NotNull]
    private $nombreLigneCode;

    #[ORM\Column(type: Types::FLOAT,
        options: ['comment' => 'Pourcentage de couverture de code par les tests'])]
    #[Assert\NotNull]
    private $couverture;

    #[ORM\Column(type: Types::FLOAT,
        options: ['comment' => 'Pourcentage de duplication dans le code'])]
    #[Assert\NotNull]
    private $duplicationDensity;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de tests unitaires exécutés'])]
    #[Assert\NotNull]
    private $testsUnitaires;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre total de défauts détectés'])]
    #[Assert\NotNull]
    private $nombreDefaut;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre total de bugs détectés'])]
    #[Assert\NotNull]
    private $nombreBug;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre total de vulnérabilités détectées'])]
    #[Assert\NotNull]
    private $nombreVulnerability;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre total de mauvaises prariques détectés'])]
    #[Assert\NotNull]
    private $nombreCodeSmell;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Développements spécifiques front-end'])]
    #[Assert\NotNull]
    private $frontend;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Développements spécifiques back-end'])]
    #[Assert\NotNull]
    private $backend;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Autres développements spécifiques'])]
    #[Assert\NotNull]
    private $autre;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Somme de la dette technique accumulée'])]
    #[Assert\NotNull]
    private $dette;

    #[ORM\Column(type: Types::FLOAT,
        options: ['comment' => 'Ratio de la dette technique (SQALE)'])]
    #[Assert\NotNull]
    private $sqaleDebtRatio;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre d’anomalies bloquantes'])]
    #[Assert\NotNull]
    private $nombreAnomalieBloquant;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre d’anomalies critiques'])]
    #[Assert\NotNull]
    private $nombreAnomalieCritique;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre d’anomalies d’information'])]
    #[Assert\NotNull]
    private $nombreAnomalieInfo;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre d’anomalies majeures'])]
    #[Assert\NotNull]
    private $nombreAnomalieMajeur;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre d’anomalies mineures'])]
    #[Assert\NotNull]
    private $nombreAnomalieMineur;

    #[ORM\Column(type: Types::STRING, length: 16,
            options: ['comment' => 'Note de fiabilité attribuée au projet'])]
    #[Assert\NotBlank]
    private $noteReliability;

    #[ORM\Column(type: Types::STRING, length: 16,
        options: ['comment' => 'Note de sécurité attribuée au projet'])]
    #[Assert\NotBlank]
    private $noteSecurity;

    #[ORM\Column(type: Types::STRING, length: 16,
        options: ['comment' => 'Note SQALE attribuée au projet'])]
    #[Assert\NotBlank]
    private $noteSqale;

    #[ORM\Column(type: Types::STRING, length: 16,
        options: ['comment' => 'Note pour les hotspots de sécurité'])]
    #[Assert\NotBlank]
    private $noteHotspot;

    #[ORM\Column(type: Types::INTEGER,
            options: ['comment' => 'Nombre de hotspots de sécurité de niveau élevé'])]
    #[Assert\NotNull]
    private $hotspotHigh;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de hotspots de sécurité de niveau moyen'])]
    #[Assert\NotNull]
    private $hotspotMedium;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de hotspots de sécurité de niveau faible'])]
    #[Assert\NotNull]
    private $hotspotLow;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre total de hotspots de sécurité'])]
    #[Assert\NotNull]
    private $hotspotTotal;

    #[ORM\Column(type: Types::BOOLEAN,
        options: ['comment' => 'Indique si c’est l’initialisation du projet'])]
    #[Assert\NotNull]
    private $initial;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de bugs bloquants'])]
    #[Assert\NotNull]
    private $bugBlocker;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de bugs critiques'])]
    #[Assert\NotNull]
    private $bugCritical;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de bugs majeurs'])]
    #[Assert\NotNull]
    private $bugMajor;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de bugs mineurs'])]
    #[Assert\NotNull]
    private $bugMinor;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de bugs d’information'])]
    #[Assert\NotNull]
    private $bugInfo;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de vulnérabilités bloquantes'])]
    #[Assert\NotNull]
    private $vulnerabilityBlocker;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de vulnérabilités critiques'])]
    #[Assert\NotNull]
    private $vulnerabilityCritical;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de vulnérabilités majeures'])]
    #[Assert\NotNull]
    private $vulnerabilityMajor;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de vulnérabilités mineures'])]
    #[Assert\NotNull]
    private $vulnerabilityMinor;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de vulnérabilités d’information'])]
    #[Assert\NotNull]
    private $vulnerabilityInfo;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de mauvaises prariques bloquants'])]
    #[Assert\NotNull]
    private $codeSmellBlocker;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de mauvaises prariques critiques'])]
    #[Assert\NotNull]
    private $codeSmellCritical;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de mauvaises prariques majeurs'])]
    #[Assert\NotNull]
    private $codeSmellMajor;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de mauvaises prariques mineurs'])]
    #[Assert\NotNull]
    private $codeSmellMinor;

    #[ORM\Column(type: Types::INTEGER,
        options: ['comment' => 'Nombre de mauvaises prariques d’information'])]
    #[Assert\NotNull]
    private $codeSmellInfo;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true,
    options: ['comment' => 'Mode de collete : MANUEL | AUTOMATIQUE'])]
    #[Assert\Length(max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères.")]
    private ?string $modeCollecte=null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true,
    options: ['comment' => "Nom de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom de l'utilisatzeur ne peut pas dépasser 128 caractères.")]
    private ?string $utilisateurCollecte=null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE,
        options: ['comment' => 'Date d’enregistrement de l’historique'])]
    #[Assert\NotNull]
    private $dateEnregistrement;

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    /*
     * Le générateur d'entity ne génère pas le setter pour une clé multiple
        public function setMavenKey(string $mavenKey): self
        {
            $this->mavenKey = $mavenKey;
            return $this;
        }
    */

    /*
     * Le générateur d'entity ne génère pas le setter pour une clé multiple
        public function setVersion(string $version): self
        {
            $this->version = $version;
            return $this;
        }
    */

    /*
     * Le générateur d'entity ne génère pas le setter pour une clé multiple
        public function setDateVersion(string $dateVersion): self
        {
            $this->dateVersion = $dateVersion;
            return $this;
        }
    */

    public function setMavenKey(string $mavenKey): self
        {
            $this->mavenKey = $mavenKey;
            return $this;
        }
    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }
    public function setDateVersion(string $dateVersion): self
    {
        $this->dateVersion = $dateVersion;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getDateVersion(): ?string
    {
        return $this->dateVersion;
    }

    public function getNomProjet(): ?string
    {
        return $this->nomProjet;
    }

    public function setNomProjet(string $nomProjet): static
    {
        $this->nomProjet = $nomProjet;

        return $this;
    }

    public function getVersionRelease(): ?int
    {
        return $this->versionRelease;
    }

    public function setVersionRelease(int $versionRelease): static
    {
        $this->versionRelease = $versionRelease;

        return $this;
    }

    public function getVersionSnapshot(): ?int
    {
        return $this->versionSnapshot;
    }

    public function setVersionSnapshot(int $versionSnapshot): static
    {
        $this->versionSnapshot = $versionSnapshot;

        return $this;
    }

    public function getVersionAutre(): ?int
    {
        return $this->versionAutre;
    }

    public function setVersionAutre(int $versionAutre): static
    {
        $this->versionAutre = $versionAutre;

        return $this;
    }

    public function getSuppressWarning(): ?int
    {
        return $this->suppressWarning;
    }

    public function setSuppressWarning(int $suppressWarning): static
    {
        $this->suppressWarning = $suppressWarning;

        return $this;
    }

    public function getNoSonar(): ?int
    {
        return $this->noSonar;
    }

    public function setNoSonar(int $noSonar): static
    {
        $this->noSonar = $noSonar;

        return $this;
    }

    public function getNombreLigne(): ?int
    {
        return $this->nombreLigne;
    }

    public function setNombreLigne(int $nombreLigne): static
    {
        $this->nombreLigne = $nombreLigne;

        return $this;
    }

    public function getNombreLigneCode(): ?int
    {
        return $this->nombreLigneCode;
    }

    public function setNombreLigneCode(int $nombreLigneCode): static
    {
        $this->nombreLigneCode = $nombreLigneCode;

        return $this;
    }

    public function getCouverture(): ?float
    {
        return $this->couverture;
    }

    public function setCouverture(float $couverture): static
    {
        $this->couverture = $couverture;

        return $this;
    }

    public function getTestsUnitaires(): ?int
    {
        return $this->testsUnitaires;
    }

    public function setTestsUnitaires(int $testsUnitaires): static
    {
        $this->testsUnitaires = $testsUnitaires;

        return $this;
    }

    public function getNombreDefaut(): ?int
    {
        return $this->nombreDefaut;
    }

    public function setNombreDefaut(int $nombreDefaut): static
    {
        $this->nombreDefaut = $nombreDefaut;

        return $this;
    }

    public function getNombreBug(): ?int
    {
        return $this->nombreBug;
    }

    public function setNombreBug(int $nombreBug): static
    {
        $this->nombreBug = $nombreBug;

        return $this;
    }

    public function getNombreVulnerability(): ?int
    {
        return $this->nombreVulnerability;
    }

    public function setNombreVulnerability(int $nombreVulnerability): static
    {
        $this->nombreVulnerability = $nombreVulnerability;

        return $this;
    }

    public function getNombreCodeSmell(): ?int
    {
        return $this->nombreCodeSmell;
    }

    public function setNombreCodeSmell(int $nombreCodeSmell): static
    {
        $this->nombreCodeSmell = $nombreCodeSmell;

        return $this;
    }

    public function getFrontend(): ?int
    {
        return $this->frontend;
    }

    public function setFrontend(int $frontend): static
    {
        $this->frontend = $frontend;

        return $this;
    }

    public function getBackend(): ?int
    {
        return $this->backend;
    }

    public function setBackend(int $backend): static
    {
        $this->backend = $backend;

        return $this;
    }

    public function getAutre(): ?int
    {
        return $this->autre;
    }

    public function setAutre(int $autre): static
    {
        $this->autre = $autre;

        return $this;
    }

    public function getDette(): ?int
    {
        return $this->dette;
    }

    public function setDette(int $dette): static
    {
        $this->dette = $dette;

        return $this;
    }

    public function getSqaleDebtRatio(): ?float
    {
        return $this->sqaleDebtRatio;
    }

    public function setSqaleDebtRatio(float $sqaleDebtRatio): static
    {
        $this->sqaleDebtRatio = $sqaleDebtRatio;

        return $this;
    }

    public function getNombreAnomalieBloquant(): ?int
    {
        return $this->nombreAnomalieBloquant;
    }

    public function setNombreAnomalieBloquant(int $nombreAnomalieBloquant): static
    {
        $this->nombreAnomalieBloquant = $nombreAnomalieBloquant;

        return $this;
    }

    public function getNombreAnomalieCritique(): ?int
    {
        return $this->nombreAnomalieCritique;
    }

    public function setNombreAnomalieCritique(int $nombreAnomalieCritique): static
    {
        $this->nombreAnomalieCritique = $nombreAnomalieCritique;

        return $this;
    }

    public function getNombreAnomalieInfo(): ?int
    {
        return $this->nombreAnomalieInfo;
    }

    public function setNombreAnomalieInfo(int $nombreAnomalieInfo): static
    {
        $this->nombreAnomalieInfo = $nombreAnomalieInfo;

        return $this;
    }

    public function getNombreAnomalieMajeur(): ?int
    {
        return $this->nombreAnomalieMajeur;
    }

    public function setNombreAnomalieMajeur(int $nombreAnomalieMajeur): static
    {
        $this->nombreAnomalieMajeur = $nombreAnomalieMajeur;

        return $this;
    }

    public function getNombreAnomalieMineur(): ?int
    {
        return $this->nombreAnomalieMineur;
    }

    public function setNombreAnomalieMineur(int $nombreAnomalieMineur): static
    {
        $this->nombreAnomalieMineur = $nombreAnomalieMineur;

        return $this;
    }

    public function getNoteReliability(): ?string
    {
        return $this->noteReliability;
    }

    public function setNoteReliability(string $noteReliability): static
    {
        $this->noteReliability = $noteReliability;

        return $this;
    }

    public function getNoteSecurity(): ?string
    {
        return $this->noteSecurity;
    }

    public function setNoteSecurity(string $noteSecurity): static
    {
        $this->noteSecurity = $noteSecurity;

        return $this;
    }

    public function getNoteSqale(): ?string
    {
        return $this->noteSqale;
    }

    public function setNoteSqale(string $noteSqale): static
    {
        $this->noteSqale = $noteSqale;

        return $this;
    }

    public function getNoteHotspot(): ?string
    {
        return $this->noteHotspot;
    }

    public function setNoteHotspot(string $noteHotspot): static
    {
        $this->noteHotspot = $noteHotspot;

        return $this;
    }

    public function getHotspotHigh(): ?int
    {
        return $this->hotspotHigh;
    }

    public function setHotspotHigh(int $hotspotHigh): static
    {
        $this->hotspotHigh = $hotspotHigh;

        return $this;
    }

    public function getHotspotMedium(): ?int
    {
        return $this->hotspotMedium;
    }

    public function setHotspotMedium(int $hotspotMedium): static
    {
        $this->hotspotMedium = $hotspotMedium;

        return $this;
    }

    public function getHotspotLow(): ?int
    {
        return $this->hotspotLow;
    }

    public function setHotspotLow(int $hotspotLow): static
    {
        $this->hotspotLow = $hotspotLow;

        return $this;
    }

    public function getHotspotTotal(): ?int
    {
        return $this->hotspotTotal;
    }

    public function setHotspotTotal(int $hotspotTotal): static
    {
        $this->hotspotTotal = $hotspotTotal;

        return $this;
    }

    public function isInitial(): ?bool
    {
        return $this->initial;
    }

    public function setInitial(bool $initial): static
    {
        $this->initial = $initial;

        return $this;
    }

    public function getBugBlocker(): ?int
    {
        return $this->bugBlocker;
    }

    public function setBugBlocker(int $bugBlocker): static
    {
        $this->bugBlocker = $bugBlocker;

        return $this;
    }

    public function getBugCritical(): ?int
    {
        return $this->bugCritical;
    }

    public function setBugCritical(int $bugCritical): static
    {
        $this->bugCritical = $bugCritical;

        return $this;
    }

    public function getBugMajor(): ?int
    {
        return $this->bugMajor;
    }

    public function setBugMajor(int $bugMajor): static
    {
        $this->bugMajor = $bugMajor;

        return $this;
    }

    public function getBugMinor(): ?int
    {
        return $this->bugMinor;
    }

    public function setBugMinor(int $bugMinor): static
    {
        $this->bugMinor = $bugMinor;

        return $this;
    }

    public function getBugInfo(): ?int
    {
        return $this->bugInfo;
    }

    public function setBugInfo(int $bugInfo): static
    {
        $this->bugInfo = $bugInfo;

        return $this;
    }

    public function getVulnerabilityBlocker(): ?int
    {
        return $this->vulnerabilityBlocker;
    }

    public function setVulnerabilityBlocker(int $vulnerabilityBlocker): static
    {
        $this->vulnerabilityBlocker = $vulnerabilityBlocker;

        return $this;
    }

    public function getVulnerabilityCritical(): ?int
    {
        return $this->vulnerabilityCritical;
    }

    public function setVulnerabilityCritical(int $vulnerabilityCritical): static
    {
        $this->vulnerabilityCritical = $vulnerabilityCritical;

        return $this;
    }

    public function getVulnerabilityMajor(): ?int
    {
        return $this->vulnerabilityMajor;
    }

    public function setVulnerabilityMajor(int $vulnerabilityMajor): static
    {
        $this->vulnerabilityMajor = $vulnerabilityMajor;

        return $this;
    }

    public function getVulnerabilityMinor(): ?int
    {
        return $this->vulnerabilityMinor;
    }

    public function setVulnerabilityMinor(int $vulnerabilityMinor): static
    {
        $this->vulnerabilityMinor = $vulnerabilityMinor;

        return $this;
    }

    public function getVulnerabilityInfo(): ?int
    {
        return $this->vulnerabilityInfo;
    }

    public function setVulnerabilityInfo(int $vulnerabilityInfo): static
    {
        $this->vulnerabilityInfo = $vulnerabilityInfo;

        return $this;
    }

    public function getCodeSmellBlocker(): ?int
    {
        return $this->codeSmellBlocker;
    }

    public function setCodeSmellBlocker(int $codeSmellBlocker): static
    {
        $this->codeSmellBlocker = $codeSmellBlocker;

        return $this;
    }

    public function getCodeSmellCritical(): ?int
    {
        return $this->codeSmellCritical;
    }

    public function setCodeSmellCritical(int $codeSmellCritical): static
    {
        $this->codeSmellCritical = $codeSmellCritical;

        return $this;
    }

    public function getCodeSmellMajor(): ?int
    {
        return $this->codeSmellMajor;
    }

    public function setCodeSmellMajor(int $codeSmellMajor): static
    {
        $this->codeSmellMajor = $codeSmellMajor;

        return $this;
    }

    public function getCodeSmellMinor(): ?int
    {
        return $this->codeSmellMinor;
    }

    public function setCodeSmellMinor(int $codeSmellMinor): static
    {
        $this->codeSmellMinor = $codeSmellMinor;

        return $this;
    }

    public function getCodeSmellInfo(): ?int
    {
        return $this->codeSmellInfo;
    }

    public function setCodeSmellInfo(int $codeSmellInfo): static
    {
        $this->codeSmellInfo = $codeSmellInfo;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): static
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }

    public function setModeCollecte(?string $modeCollecte): static
    {
        $this->modeCollecte = $modeCollecte;

        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }

    public function setUtilisateurCollecte(?string $utilisateurCollecte): static
    {
        $this->utilisateurCollecte = $utilisateurCollecte;

        return $this;
    }

    public function getDuplicationDensity(): ?float
    {
        return $this->duplicationDensity;
    }

    public function setDuplicationDensity(float $duplicationDensity): static
    {
        $this->duplicationDensity = $duplicationDensity;

        return $this;
    }

    public function getTodo(): ?int
    {
        return $this->todo;
    }

    public function setTodo(int $todo): static
    {
        $this->todo = $todo;

        return $this;
    }
}
