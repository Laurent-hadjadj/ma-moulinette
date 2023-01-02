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
}
