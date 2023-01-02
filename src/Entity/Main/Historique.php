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

use App\Repository\Main\HistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueRepository::class)]
class Historique
{

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 128)]
    private $mavenKey;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
    private $version;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 128)]
    private $dateVersion;

    #[ORM\Column(type: 'string', length: 128)]
    private $nomProjet;

    #[ORM\Column(type: 'integer')]
    private $versionRelease;

    #[ORM\Column(type: 'integer')]
    private $versionSnapshot;

    #[ORM\Column(type: 'integer')]
    private $versionAutre;

    #[ORM\Column(type: 'integer')]
    private $suppressWarning;

    #[ORM\Column(type: 'integer')]
    private $noSonar;

    #[ORM\Column(type: 'integer')]
    private $nombreLigne;

    #[ORM\Column(type: 'integer')]
    private $nombreLigneCode;

    #[ORM\Column(type: 'float')]
    private $couverture;

    #[ORM\Column(type: 'float')]
    private $duplication;

    #[ORM\Column(type: 'integer')]
    private $testsUnitaires;

    #[ORM\Column(type: 'integer')]
    private $nombreDefaut;

    #[ORM\Column(type: 'integer')]
    private $nombreBug;

    #[ORM\Column(type: 'integer')]
    private $nombreVulnerability;

    #[ORM\Column(type: 'integer')]
    private $nombreCodeSmell;

    #[ORM\Column(type: 'integer')]
    private $frontend;

    #[ORM\Column(type: 'integer')]
    private $backend;

    #[ORM\Column(type: 'integer')]
    private $autre;

    #[ORM\Column(type: 'integer')]
    private $dette;

    #[ORM\Column(type: 'integer')]
    private $nombreAnomalieBloquant;

    #[ORM\Column(type: 'integer')]
    private $nombreAnomalieCritique;

    #[ORM\Column(type: 'integer')]
    private $nombreAnomalieInfo;

    #[ORM\Column(type: 'integer')]
    private $nombreAnomalieMajeur;

    #[ORM\Column(type: 'integer')]
    private $nombreAnomalieMineur;

    #[ORM\Column(type: 'string', length: 4)]
    private $noteReliability;

    #[ORM\Column(type: 'string', length: 4)]
    private $noteSecurity;

    #[ORM\Column(type: 'string', length: 4)]
    private $noteSqale;

    #[ORM\Column(type: 'string', length: 4)]
    private $noteHotspot;

    #[ORM\Column(type: 'integer')]
    private $hotspotHigh;

    #[ORM\Column(type: 'integer')]
    private $hotspotMedium;

    #[ORM\Column(type: 'integer')]
    private $hotspotLow;

    #[ORM\Column(type: 'integer')]
    private $hotspotTotal;

    #[ORM\Column(type: 'boolean')]
    private $favori;

    #[ORM\Column(type: 'boolean')]
    private $initial;

    #[ORM\Column(type: 'integer')]
    private $bugBlocker;

    #[ORM\Column(type: 'integer')]
    private $bugCritical;

    #[ORM\Column(type: 'integer')]
    private $bugMajor;

    #[ORM\Column(type: 'integer')]
    private $bugMinor;

    #[ORM\Column(type: 'integer')]
    private $bugInfo;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityBlocker;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityCritical;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityMajor;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityMinor;

    #[ORM\Column(type: 'integer')]
    private $vulnerabilityInfo;

    #[ORM\Column(type: 'integer')]
    private $codeSmellBlocker;

    #[ORM\Column(type: 'integer')]
    private $codeSmellCritical;

    #[ORM\Column(type: 'integer')]
    private $codeSmellMajor;

    #[ORM\Column(type: 'integer')]
    private $codeSmellMinor;

    #[ORM\Column(type: 'integer')]
    private $codeSmellInfo;

    #[ORM\Column(type: 'datetime')]
    private $dateEnregistrement;

    public function getMavenKey(): ?string
    {
        return $this->maven_key;
    }

    /*
     * Le généarteur d'entity ne génére pas le setter pour une clé multiple
        public function setMavenKey(string $maven_key): self
        {
            $this->maven_key = $maven_key;
            return $this;
        }
    */

    /*
     * Le généarteur d'entity ne génére pas le setter pour une clé multiple
        public function setVersion(string $version): self
        {
            $this->version = $version;
            return $this;
        }
    */

    /*
     * Le généarteur d'entity ne génére pas le setter pour une clé multiple
        public function setDateVersion(string $date_version): self
        {
            $this->date_version = $date_version;
            return $this;
        }
    */

  /**
   * [Description for setMavenKey]
   *
   * @param string $mavenKey
   *
   * @return self
   *
   * Created at: 02/01/2023, 17:58:55 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function setMavenKey(string $mavenKey): self
      {
          $this->mavenKey = $mavenKey;
          return $this;
      }

    /**
     * [Description for setVersion]
     *
     * @param string $version
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:58:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * [Description for setDateVersion]
     *
     * @param string $dateVersion
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:58:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateVersion(string $dateVersion): self
    {
        $this->dateVersion = $dateVersion;
        return $this;
    }


    /**
     * [Description for getVersion]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:53:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * [Description for getDateVersion]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:53:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateVersion(): ?string
    {
        return $this->dateVersion;
    }

    /**
     * [Description for getNomProjet]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:53:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNomProjet(): ?string
    {
        return $this->nomProjet;
    }

    /**
     * [Description for setNomProjet]
     *
     * @param string $nomProjet
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:53:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNomProjet(string $nomProjet): self
    {
        $this->nomProjet = $nomProjet;

        return $this;
    }

    /**
     * [Description for getVersionRelease]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVersionRelease(): ?int
    {
        return $this->versionRelease;
    }

    /**
     * [Description for setVersionRelease]
     *
     * @param int $versionRelease
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVersionRelease(int $versionRelease): self
    {
        $this->versionRelease = $versionRelease;

        return $this;
    }

    /**
     * [Description for getVersionSnapshot]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVersionSnapshot(): ?int
    {
        return $this->versionSnapshot;
    }

    /**
     * [Description for setVersionSnapshot]
     *
     * @param int $versionSnapshot
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVersionSnapshot(int $versionSnapshot): self
    {
        $this->versionSnapshot = $versionSnapshot;

        return $this;
    }

    /**
     * [Description for getVersionAutre]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVersionAutre(): ?int
    {
        return $this->versionAutre;
    }

    /**
     * [Description for setVersionAutre]
     *
     * @param int $versionAutre
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVersionAutre(int $versionAutre): self
    {
        $this->versionAutre = $versionAutre;

        return $this;
    }

    /**
     * [Description for getSuppressWarning]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getSuppressWarning(): ?int
    {
        return $this->suppressWarning;
    }

    /**
     * [Description for setSuppressWarning]
     *
     * @param int $suppressWarning
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setSuppressWarning(int $suppressWarning): self
    {
        $this->suppressWarning = $suppressWarning;

        return $this;
    }

    /**
     * [Description for getNoSonar]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNoSonar(): ?int
    {
        return $this->noSonar;
    }

    /**
     * [Description for setNoSonar]
     *
     * @param int $noSonar
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNoSonar(int $noSonar): self
    {
        $this->noSonar = $noSonar;

        return $this;
    }

    /**
     * [Description for getNombreLigne]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreLigne(): ?int
    {
        return $this->nombreLigne;
    }

    /**
     * [Description for setNombreLigne]
     *
     * @param int $nombreLigne
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreLigne(int $nombreLigne): self
    {
        $this->nombreLigne = $nombreLigne;

        return $this;
    }

    /**
     * [Description for getNombreLigneCode]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreLigneCode(): ?int
    {
        return $this->nombreLigneCode;
    }

    /**
     * [Description for setNombreLigneCode]
     *
     * @param int $nombreLigneCode
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreLigneCode(int $nombreLigneCode): self
    {
        $this->nombreLigneCode = $nombreLigneCode;

        return $this;
    }

    /**
     * [Description for getCouverture]
     *
     * @return float|null
     *
     * Created at: 02/01/2023, 17:54:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCouverture(): ?float
    {
        return $this->couverture;
    }

    /**
     * [Description for setCouverture]
     *
     * @param float $couverture
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCouverture(float $couverture): self
    {
        $this->couverture = $couverture;

        return $this;
    }

    /**
     * [Description for getDuplication]
     *
     * @return float|null
     *
     * Created at: 02/01/2023, 17:54:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDuplication(): ?float
    {
        return $this->duplication;
    }

    /**
     * [Description for setDuplication]
     *
     * @param float $duplication
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDuplication(float $duplication): self
    {
        $this->duplication = $duplication;

        return $this;
    }

    /**
     * [Description for getTestsUnitaires]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getTestsUnitaires(): ?int
    {
        return $this->testsUnitaires;
    }

    /**
     * [Description for setTestsUnitaires]
     *
     * @param int $testsUnitaires
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setTestsUnitaires(int $testsUnitaires): self
    {
        $this->testsUnitaires = $testsUnitaires;

        return $this;
    }

    /**
     * [Description for getNombreDefaut]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreDefaut(): ?int
    {
        return $this->nombreDefaut;
    }

    /**
     * [Description for setNombreDefaut]
     *
     * @param int $nombreDefaut
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreDefaut(int $nombreDefaut): self
    {
        $this->nombreDefaut = $nombreDefaut;

        return $this;
    }

    /**
     * [Description for getNombreBug]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreBug(): ?int
    {
        return $this->nombreBug;
    }

    /**
     * [Description for setNombreBug]
     *
     * @param int $nombreBug
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreBug(int $nombreBug): self
    {
        $this->nombreBug = $nombreBug;

        return $this;
    }

    /**
     * [Description for getNombreVulnerability]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreVulnerability(): ?int
    {
        return $this->nombreVulnerability;
    }

    /**
     * [Description for setNombreVulnerability]
     *
     * @param int $nombreVulnerability
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreVulnerability(int $nombreVulnerability): self
    {
        $this->nombreVulnerability = $nombreVulnerability;

        return $this;
    }

    /**
     * [Description for getNombreCodeSmell]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:54:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreCodeSmell(): ?int
    {
        return $this->nombreCodeSmell;
    }

    /**
     * [Description for setNombreCodeSmell]
     *
     * @param int $nombreCodeSmell
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:54:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreCodeSmell(int $nombreCodeSmell): self
    {
        $this->nombreCodeSmell = $nombreCodeSmell;

        return $this;
    }

    /**
     * [Description for getFrontend]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getFrontend(): ?int
    {
        return $this->frontend;
    }

    /**
     * [Description for setFrontend]
     *
     * @param int $frontend
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setFrontend(int $frontend): self
    {
        $this->frontend = $frontend;

        return $this;
    }

    /**
     * [Description for getBackend]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBackend(): ?int
    {
        return $this->backend;
    }

    /**
     * [Description for setBackend]
     *
     * @param int $backend
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBackend(int $backend): self
    {
        $this->backend = $backend;

        return $this;
    }

    /**
     * [Description for getAutre]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getAutre(): ?int
    {
        return $this->autre;
    }

    /**
     * [Description for setAutre]
     *
     * @param int $autre
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setAutre(int $autre): self
    {
        $this->autre = $autre;

        return $this;
    }

    /**
     * [Description for getDette]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDette(): ?int
    {
        return $this->dette;
    }

    public function setDette(int $dette): self
    {
        $this->dette = $dette;

        return $this;
    }

    /**
     * [Description for getNombreAnomalieBloquant]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreAnomalieBloquant(): ?int
    {
        return $this->nombreAnomalieBloquant;
    }

    /**
     * [Description for setNombreAnomalieBloquant]
     *
     * @param int $nombreAnomalieBloquant
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreAnomalieBloquant(int $nombreAnomalieBloquant): self
    {
        $this->nombreAnomalieBloquant = $nombreAnomalieBloquant;

        return $this;
    }

    /**
     * [Description for getNombreAnomalieCritique]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreAnomalieCritique(): ?int
    {
        return $this->nombreAnomalieCritique;
    }

    /**
     * [Description for setNombreAnomalieCritique]
     *
     * @param int $nombreAnomalieCritique
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreAnomalieCritique(int $nombreAnomalieCritique): self
    {
        $this->nombreAnomalieCritique = $nombreAnomalieCritique;

        return $this;
    }

    /**
     * [Description for getNombreAnomalieInfo]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreAnomalieInfo(): ?int
    {
        return $this->nombreAnomalieInfo;
    }

    /**
     * [Description for setNombreAnomalieInfo]
     *
     * @param int $nombreAnomalieInfo
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreAnomalieInfo(int $nombreAnomalieInfo): self
    {
        $this->nombreAnomalieInfo = $nombreAnomalieInfo;

        return $this;
    }

    /**
     * [Description for getNombreAnomalieMajeur]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreAnomalieMajeur(): ?int
    {
        return $this->nombreAnomalieMajeur;
    }

    /**
     * [Description for setNombreAnomalieMajeur]
     *
     * @param int $nombreAnomalieMajeur
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreAnomalieMajeur(int $nombreAnomalieMajeur): self
    {
        $this->nombreAnomalieMajeur = $nombreAnomalieMajeur;

        return $this;
    }

    /**
     * [Description for getNombreAnomalieMineur]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNombreAnomalieMineur(): ?int
    {
        return $this->nombreAnomalieMineur;
    }

    /**
     * [Description for setNombreAnomalieMineur]
     *
     * @param int $nombreAnomalieMineur
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNombreAnomalieMineur(int $nombreAnomalieMineur): self
    {
        $this->nombreAnomalieMineur = $nombreAnomalieMineur;

        return $this;
    }

    /**
     * [Description for getNoteReliability]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:55:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNoteReliability(): ?string
    {
        return $this->noteReliability;
    }

    /**
     * [Description for setNoteReliability]
     *
     * @param string $noteReliability
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNoteReliability(string $noteReliability): self
    {
        $this->noteReliability = $noteReliability;

        return $this;
    }

    /**
     * [Description for getNoteSecurity]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:55:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNoteSecurity(): ?string
    {
        return $this->noteSecurity;
    }

    /**
     * [Description for setNoteSecurity]
     *
     * @param string $noteSecurity
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNoteSecurity(string $noteSecurity): self
    {
        $this->noteSecurity = $noteSecurity;

        return $this;
    }

    /**
     * [Description for getNoteSqale]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:55:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNoteSqale(): ?string
    {
        return $this->noteSqale;
    }

    /**
     * [Description for setNoteSqale]
     *
     * @param string $noteSqale
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNoteSqale(string $noteSqale): self
    {
        $this->noteSqale = $noteSqale;

        return $this;
    }

    /**
     * [Description for getNoteHotspot]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:55:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNoteHotspot(): ?string
    {
        return $this->noteHotspot;
    }

    /**
     * [Description for setNoteHotspot]
     *
     * @param string $noteHotspot
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNoteHotspot(string $noteHotspot): self
    {
        $this->noteHotspot = $noteHotspot;

        return $this;
    }

    /**
     * [Description for getHotspotHigh]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getHotspotHigh(): ?int
    {
        return $this->hotspotHigh;
    }

    /**
     * [Description for setHotspotHigh]
     *
     * @param int $hotspotHigh
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setHotspotHigh(int $hotspotHigh): self
    {
        $this->hotspotHigh = $hotspotHigh;

        return $this;
    }

    /**
     * [Description for getHotspotMedium]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getHotspotMedium(): ?int
    {
        return $this->hotspotMedium;
    }

    /**
     * [Description for setHotspotMedium]
     *
     * @param int $hotspotMedium
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setHotspotMedium(int $hotspotMedium): self
    {
        $this->hotspotMedium = $hotspotMedium;

        return $this;
    }

    /**
     * [Description for getHotspotLow]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getHotspotLow(): ?int
    {
        return $this->hotspotLow;
    }

    /**
     * [Description for setHotspotLow]
     *
     * @param int $hotspotLow
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setHotspotLow(int $hotspotLow): self
    {
        $this->hotspotLow = $hotspotLow;

        return $this;
    }

    /**
     * [Description for getHotspotTotal]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:55:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getHotspotTotal(): ?int
    {
        return $this->hotspotTotal;
    }

    /**
     * [Description for setHotspotTotal]
     *
     * @param int $hotspotTotal
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:55:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setHotspotTotal(int $hotspotTotal): self
    {
        $this->hotspotTotal = $hotspotTotal;

        return $this;
    }

    /**
     * [Description for isFavori]
     *
     * @return bool|null
     *
     * Created at: 02/01/2023, 17:55:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isFavori(): ?bool
    {
        return $this->favori;
    }

    /**
     * [Description for setFavori]
     *
     * @param bool $favori
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setFavori(bool $favori): self
    {
        $this->favori = $favori;

        return $this;
    }

    /**
     * [Description for isInitial]
     *
     * @return bool|null
     *
     * Created at: 02/01/2023, 17:56:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isInitial(): ?bool
    {
        return $this->initial;
    }

    /**
     * [Description for setInitial]
     *
     * @param bool $initial
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setInitial(bool $initial): self
    {
        $this->initial = $initial;

        return $this;
    }

    /**
     * [Description for getBugBlocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugBlocker(): ?int
    {
        return $this->bugBlocker;
    }

    /**
     * [Description for setBugBlocker]
     *
     * @param int $bugBlocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugBlocker(int $bugBlocker): self
    {
        $this->bugBlocker = $bugBlocker;

        return $this;
    }

    /**
     * [Description for getBugCritical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugCritical(): ?int
    {
        return $this->bugCritical;
    }

    /**
     * [Description for setBugCritical]
     *
     * @param int $bugCritical
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugCritical(int $bugCritical): self
    {
        $this->bugCritical = $bugCritical;

        return $this;
    }

    /**
     * [Description for getBugMajor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugMajor(): ?int
    {
        return $this->bugMajor;
    }

    /**
     * [Description for setBugMajor]
     *
     * @param int $bugMajor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugMajor(int $bugMajor): self
    {
        $this->bugMajor = $bugMajor;

        return $this;
    }

    /**
     * [Description for getBugMinor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugMinor(): ?int
    {
        return $this->bugMinor;
    }

    /**
     * [Description for setBugMinor]
     *
     * @param int $bugMinor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugMinor(int $bugMinor): self
    {
        $this->bugMinor = $bugMinor;

        return $this;
    }

    /**
     * [Description for getBugInfo]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getBugInfo(): ?int
    {
        return $this->bugInfo;
    }

    /**
     * [Description for setBugInfo]
     *
     * @param int $bugInfo
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setBugInfo(int $bugInfo): self
    {
        $this->bugInfo = $bugInfo;

        return $this;
    }

    /**
     * [Description for getVulnerabilityBlocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityBlocker(): ?int
    {
        return $this->vulnerabilityBlocker;
    }

    /**
     * [Description for setVulnerabilityBlocker]
     *
     * @param int $vulnerabilityBlocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityBlocker(int $vulnerabilityBlocker): self
    {
        $this->vulnerabilityBlocker = $vulnerabilityBlocker;

        return $this;
    }

    /**
     * [Description for getVulnerabilityCritical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityCritical(): ?int
    {
        return $this->vulnerabilityCritical;
    }

    /**
     * [Description for setVulnerabilityCritical]
     *
     * @param int $vulnerabilityCritical
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityCritical(int $vulnerabilityCritical): self
    {
        $this->vulnerabilityCritical = $vulnerabilityCritical;

        return $this;
    }

    /**
     * [Description for getVulnerabilityMajor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityMajor(): ?int
    {
        return $this->vulnerabilityMajor;
    }

    /**
     * [Description for setVulnerabilityMajor]
     *
     * @param int $vulnerabilityMajor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityMajor(int $vulnerabilityMajor): self
    {
        $this->vulnerabilityMajor = $vulnerabilityMajor;

        return $this;
    }

    /**
     * [Description for getVulnerabilityMinor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityMinor(): ?int
    {
        return $this->vulnerabilityMinor;
    }

    /**
     * [Description for setVulnerabilityMinor]
     *
     * @param int $vulnerabilityMinor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityMinor(int $vulnerabilityMinor): self
    {
        $this->vulnerabilityMinor = $vulnerabilityMinor;

        return $this;
    }

    /**
     * [Description for getVulnerabilityInfo]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVulnerabilityInfo(): ?int
    {
        return $this->vulnerabilityInfo;
    }

    /**
     * [Description for setVulnerabilityInfo]
     *
     * @param int $vulnerabilityInfo
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVulnerabilityInfo(int $vulnerabilityInfo): self
    {
        $this->vulnerabilityInfo = $vulnerabilityInfo;

        return $this;
    }

    /**
     * [Description for getCodeSmellBlocker]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:48 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellBlocker(): ?int
    {
        return $this->codeSmellBlocker;
    }

    /**
     * [Description for setCodeSmellBlocker]
     *
     * @param int $codeSmellBlocker
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellBlocker(int $codeSmellBlocker): self
    {
        $this->codeSmellBlocker = $codeSmellBlocker;

        return $this;
    }

    /**
     * [Description for getCodeSmellCritical]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellCritical(): ?int
    {
        return $this->codeSmellCritical;
    }

    /**
     * [Description for setCodeSmellCritical]
     *
     * @param int $codeSmellCritical
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellCritical(int $codeSmellCritical): self
    {
        $this->codeSmellCritical = $codeSmellCritical;

        return $this;
    }

    /**
     * [Description for getCodeSmellMajor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellMajor(): ?int
    {
        return $this->codeSmellMajor;
    }

    /**
     * [Description for setCodeSmellMajor]
     *
     * @param int $codeSmellMajor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:56:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellMajor(int $codeSmellMajor): self
    {
        $this->codeSmellMajor = $codeSmellMajor;

        return $this;
    }

    /**
     * [Description for getCodeSmellMinor]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:56:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellMinor(): ?int
    {
        return $this->codeSmellMinor;
    }

    /**
     * [Description for setCodeSmellMinor]
     *
     * @param int $codeSmellMinor
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:57:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellMinor(int $codeSmellMinor): self
    {
        $this->codeSmellMinor = $codeSmellMinor;

        return $this;
    }

    /**
     * [Description for getCodeSmellInfo]
     *
     * @return int|null
     *
     * Created at: 02/01/2023, 17:57:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getCodeSmellInfo(): ?int
    {
        return $this->codeSmellInfo;
    }

    /**
     * [Description for setCodeSmellInfo]
     *
     * @param int $codeSmellInfo
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:57:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setCodeSmellInfo(int $codeSmellInfo): self
    {
        $this->codeSmellInfo = $codeSmellInfo;

        return $this;
    }

    /**
     * [Description for getDateEnregistrement]
     *
     * @return \DateTimeInterface|null
     *
     * Created at: 02/01/2023, 17:57:06 (Europe/Paris)
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
     * Created at: 02/01/2023, 17:57:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateEnregistrement(\DateTimeInterface $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }
}
