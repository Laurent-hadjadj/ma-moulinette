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
    private $maven_key;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
    private $version;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 128)]
    private $date_version;

    #[ORM\Column(type: 'string', length: 128)]
    private $nom_projet;

    #[ORM\Column(type: 'integer')]
    private $version_release;

    #[ORM\Column(type: 'integer')]
    private $version_snapshot;

    #[ORM\Column(type: 'integer')]
    private $version_autre;

    #[ORM\Column(type: 'integer')]
    private $suppress_warning;

    #[ORM\Column(type: 'integer')]
    private $no_sonar;

    #[ORM\Column(type: 'integer')]
    private $nombre_ligne;

    #[ORM\Column(type: 'integer')]
    private $nombre_ligne_code;

    #[ORM\Column(type: 'float')]
    private $couverture;

    #[ORM\Column(type: 'float')]
    private $duplication;

    #[ORM\Column(type: 'integer')]
    private $tests_unitaires;

    #[ORM\Column(type: 'integer')]
    private $nombre_defaut;

    #[ORM\Column(type: 'integer')]
    private $nombre_bug;

    #[ORM\Column(type: 'integer')]
    private $nombre_vulnerability;

    #[ORM\Column(type: 'integer')]
    private $nombre_code_smell;

    #[ORM\Column(type: 'integer')]
    private $frontend;

    #[ORM\Column(type: 'integer')]
    private $backend;

    #[ORM\Column(type: 'integer')]
    private $autre;

    #[ORM\Column(type: 'integer')]
    private $dette;

    #[ORM\Column(type: 'integer')]
    private $nombre_anomalie_bloquant;

    #[ORM\Column(type: 'integer')]
    private $nombre_anomalie_critique;

    #[ORM\Column(type: 'integer')]
    private $nombre_anomalie_info;

    #[ORM\Column(type: 'integer')]
    private $nombre_anomalie_majeur;

    #[ORM\Column(type: 'integer')]
    private $nombre_anomalie_mineur;

    #[ORM\Column(type: 'string', length: 4)]
    private $note_reliability;

    #[ORM\Column(type: 'string', length: 4)]
    private $note_security;

    #[ORM\Column(type: 'string', length: 4)]
    private $note_sqale;

    #[ORM\Column(type: 'string', length: 4)]
    private $note_hotspot;

    #[ORM\Column(type: 'integer')]
    private $hotspot_high;

    #[ORM\Column(type: 'integer')]
    private $hotspot_medium;

    #[ORM\Column(type: 'integer')]
    private $hotspot_low;

    #[ORM\Column(type: 'integer')]
    private $hotspot_total;

    #[ORM\Column(type: 'boolean')]
    private $favori;

    #[ORM\Column(type: 'boolean')]
    private $initial;

    #[ORM\Column(type: 'integer')]
    private $bug_blocker;

    #[ORM\Column(type: 'integer')]
    private $bug_critical;

    #[ORM\Column(type: 'integer')]
    private $bug_major;

    #[ORM\Column(type: 'integer')]
    private $bug_minor;

    #[ORM\Column(type: 'integer')]
    private $bug_info;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_blocker;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_critical;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_major;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_minor;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_info;

    #[ORM\Column(type: 'integer')]
    private $code_smell_blocker;

    #[ORM\Column(type: 'integer')]
    private $code_smell_critical;

    #[ORM\Column(type: 'integer')]
    private $code_smell_major;

    #[ORM\Column(type: 'integer')]
    private $code_smell_minor;

    #[ORM\Column(type: 'integer')]
    private $code_smell_info;

    #[ORM\Column(type: 'datetime')]
    private $date_enregistrement;

    public function getMavenKey(): ?string
    {
        return $this->maven_key;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getDateVersion(): ?string
    {
        return $this->date_version;
    }

    public function getNomProjet(): ?string
    {
        return $this->nom_projet;
    }

    public function setNomProjet(string $nom_projet): self
    {
        $this->nom_projet = $nom_projet;

        return $this;
    }

    public function getVersionRelease(): ?int
    {
        return $this->version_release;
    }

    public function setVersionRelease(int $version_release): self
    {
        $this->version_release = $version_release;

        return $this;
    }

    public function getVersionSnapshot(): ?int
    {
        return $this->version_snapshot;
    }

    public function setVersionSnapshot(int $version_snapshot): self
    {
        $this->version_snapshot = $version_snapshot;

        return $this;
    }

    public function getVersionAutre(): ?int
    {
        return $this->version_autre;
    }

    public function setVersionAutre(int $version_autre): self
    {
        $this->version_autre = $version_autre;

        return $this;
    }

    public function getSuppressWarning(): ?int
    {
        return $this->suppress_warning;
    }

    public function setSuppressWarning(int $suppress_warning): self
    {
        $this->suppress_warning = $suppress_warning;

        return $this;
    }

    public function getNoSonar(): ?int
    {
        return $this->no_sonar;
    }

    public function setNoSonar(int $no_sonar): self
    {
        $this->no_sonar = $no_sonar;

        return $this;
    }

    public function getNombreLigne(): ?int
    {
        return $this->nombre_ligne;
    }

    public function setNombreLigne(int $nombre_ligne): self
    {
        $this->nombre_ligne = $nombre_ligne;

        return $this;
    }

    public function getNombreLigneCode(): ?int
    {
        return $this->nombre_ligne_code;
    }

    public function setNombreLigneCode(int $nombre_ligne_code): self
    {
        $this->nombre_ligne_code = $nombre_ligne_code;

        return $this;
    }

    public function getCouverture(): ?float
    {
        return $this->couverture;
    }

    public function setCouverture(float $couverture): self
    {
        $this->couverture = $couverture;

        return $this;
    }

    public function getDuplication(): ?float
    {
        return $this->duplication;
    }

    public function setDuplication(float $duplication): self
    {
        $this->duplication = $duplication;

        return $this;
    }

    public function getTestsUnitaires(): ?int
    {
        return $this->tests_unitaires;
    }

    public function setTestsUnitaires(int $tests_unitaires): self
    {
        $this->tests_unitaires = $tests_unitaires;

        return $this;
    }

    public function getNombreDefaut(): ?int
    {
        return $this->nombre_defaut;
    }

    public function setNombreDefaut(int $nombre_defaut): self
    {
        $this->nombre_defaut = $nombre_defaut;

        return $this;
    }

    public function getNombreBug(): ?int
    {
        return $this->nombre_bug;
    }

    public function setNombreBug(int $nombre_bug): self
    {
        $this->nombre_bug = $nombre_bug;

        return $this;
    }

    public function getNombreVulnerability(): ?int
    {
        return $this->nombre_vulnerability;
    }

    public function setNombreVulnerability(int $nombre_vulnerability): self
    {
        $this->nombre_vulnerability = $nombre_vulnerability;

        return $this;
    }

    public function getNombreCodeSmell(): ?int
    {
        return $this->nombre_code_smell;
    }

    public function setNombreCodeSmell(int $nombre_code_smell): self
    {
        $this->nombre_code_smell = $nombre_code_smell;

        return $this;
    }

    public function getFrontend(): ?int
    {
        return $this->frontend;
    }

    public function setFrontend(int $frontend): self
    {
        $this->frontend = $frontend;

        return $this;
    }

    public function getBackend(): ?int
    {
        return $this->backend;
    }

    public function setBackend(int $backend): self
    {
        $this->backend = $backend;

        return $this;
    }

    public function getAutre(): ?int
    {
        return $this->autre;
    }

    public function setAutre(int $autre): self
    {
        $this->autre = $autre;

        return $this;
    }

    public function getDette(): ?int
    {
        return $this->dette;
    }

    public function setDette(int $dette): self
    {
        $this->dette = $dette;

        return $this;
    }

    public function getNombreAnomalieBloquant(): ?int
    {
        return $this->nombre_anomalie_bloquant;
    }

    public function setNombreAnomalieBloquant(int $nombre_anomalie_bloquant): self
    {
        $this->nombre_anomalie_bloquant = $nombre_anomalie_bloquant;

        return $this;
    }

    public function getNombreAnomalieCritique(): ?int
    {
        return $this->nombre_anomalie_critique;
    }

    public function setNombreAnomalieCritique(int $nombre_anomalie_critique): self
    {
        $this->nombre_anomalie_critique = $nombre_anomalie_critique;

        return $this;
    }

    public function getNombreAnomalieInfo(): ?int
    {
        return $this->nombre_anomalie_info;
    }

    public function setNombreAnomalieInfo(int $nombre_anomalie_info): self
    {
        $this->nombre_anomalie_info = $nombre_anomalie_info;

        return $this;
    }

    public function getNombreAnomalieMajeur(): ?int
    {
        return $this->nombre_anomalie_majeur;
    }

    public function setNombreAnomalieMajeur(int $nombre_anomalie_majeur): self
    {
        $this->nombre_anomalie_majeur = $nombre_anomalie_majeur;

        return $this;
    }

    public function getNombreAnomalieMineur(): ?int
    {
        return $this->nombre_anomalie_mineur;
    }

    public function setNombreAnomalieMineur(int $nombre_anomalie_mineur): self
    {
        $this->nombre_anomalie_mineur = $nombre_anomalie_mineur;

        return $this;
    }

    public function getNoteReliability(): ?string
    {
        return $this->note_reliability;
    }

    public function setNoteReliability(string $note_reliability): self
    {
        $this->note_reliability = $note_reliability;

        return $this;
    }

    public function getNoteSecurity(): ?string
    {
        return $this->note_security;
    }

    public function setNoteSecurity(string $note_security): self
    {
        $this->note_security = $note_security;

        return $this;
    }

    public function getNoteSqale(): ?string
    {
        return $this->note_sqale;
    }

    public function setNoteSqale(string $note_sqale): self
    {
        $this->note_sqale = $note_sqale;

        return $this;
    }

    public function getNoteHotspot(): ?string
    {
        return $this->note_hotspot;
    }

    public function setNoteHotspot(string $note_hotspot): self
    {
        $this->note_hotspot = $note_hotspot;

        return $this;
    }

    public function getHotspotHigh(): ?int
    {
        return $this->hotspot_high;
    }

    public function setHotspotHigh(int $hotspot_high): self
    {
        $this->hotspot_high = $hotspot_high;

        return $this;
    }

    public function getHotspotMedium(): ?int
    {
        return $this->hotspot_medium;
    }

    public function setHotspotMedium(int $hotspot_medium): self
    {
        $this->hotspot_medium = $hotspot_medium;

        return $this;
    }

    public function getHotspotLow(): ?int
    {
        return $this->hotspot_low;
    }

    public function setHotspotLow(int $hotspot_low): self
    {
        $this->hotspot_low = $hotspot_low;

        return $this;
    }

    public function getHotspotTotal(): ?int
    {
        return $this->hotspot_total;
    }

    public function setHotspotTotal(int $hotspot_total): self
    {
        $this->hotspot_total = $hotspot_total;

        return $this;
    }

    public function isFavori(): ?bool
    {
        return $this->favori;
    }

    public function setFavori(bool $favori): self
    {
        $this->favori = $favori;

        return $this;
    }

    public function isInitial(): ?bool
    {
        return $this->initial;
    }

    public function setInitial(bool $initial): self
    {
        $this->initial = $initial;

        return $this;
    }

    public function getBugBlocker(): ?int
    {
        return $this->bug_blocker;
    }

    public function setBugBlocker(int $bug_blocker): self
    {
        $this->bug_blocker = $bug_blocker;

        return $this;
    }

    public function getBugCritical(): ?int
    {
        return $this->bug_critical;
    }

    public function setBugCritical(int $bug_critical): self
    {
        $this->bug_critical = $bug_critical;

        return $this;
    }

    public function getBugMajor(): ?int
    {
        return $this->bug_major;
    }

    public function setBugMajor(int $bug_major): self
    {
        $this->bug_major = $bug_major;

        return $this;
    }

    public function getBugMinor(): ?int
    {
        return $this->bug_minor;
    }

    public function setBugMinor(int $bug_minor): self
    {
        $this->bug_minor = $bug_minor;

        return $this;
    }

    public function getBugInfo(): ?int
    {
        return $this->bug_info;
    }

    public function setBugInfo(int $bug_info): self
    {
        $this->bug_info = $bug_info;

        return $this;
    }

    public function getVulnerabilityBlocker(): ?int
    {
        return $this->vulnerability_blocker;
    }

    public function setVulnerabilityBlocker(int $vulnerability_blocker): self
    {
        $this->vulnerability_blocker = $vulnerability_blocker;

        return $this;
    }

    public function getVulnerabilityCritical(): ?int
    {
        return $this->vulnerability_critical;
    }

    public function setVulnerabilityCritical(int $vulnerability_critical): self
    {
        $this->vulnerability_critical = $vulnerability_critical;

        return $this;
    }

    public function getVulnerabilityMajor(): ?int
    {
        return $this->vulnerability_major;
    }

    public function setVulnerabilityMajor(int $vulnerability_major): self
    {
        $this->vulnerability_major = $vulnerability_major;

        return $this;
    }

    public function getVulnerabilityMinor(): ?int
    {
        return $this->vulnerability_minor;
    }

    public function setVulnerabilityMinor(int $vulnerability_minor): self
    {
        $this->vulnerability_minor = $vulnerability_minor;

        return $this;
    }

    public function getVulnerabilityInfo(): ?int
    {
        return $this->vulnerability_info;
    }

    public function setVulnerabilityInfo(int $vulnerability_info): self
    {
        $this->vulnerability_info = $vulnerability_info;

        return $this;
    }

    public function getCodeSmellBlocker(): ?int
    {
        return $this->code_smell_blocker;
    }

    public function setCodeSmellBlocker(int $code_smell_blocker): self
    {
        $this->code_smell_blocker = $code_smell_blocker;

        return $this;
    }

    public function getCodeSmellCritical(): ?int
    {
        return $this->code_smell_critical;
    }

    public function setCodeSmellCritical(int $code_smell_critical): self
    {
        $this->code_smell_critical = $code_smell_critical;

        return $this;
    }

    public function getCodeSmellMajor(): ?int
    {
        return $this->code_smell_major;
    }

    public function setCodeSmellMajor(int $code_smell_major): self
    {
        $this->code_smell_major = $code_smell_major;

        return $this;
    }

    public function getCodeSmellMinor(): ?int
    {
        return $this->code_smell_minor;
    }

    public function setCodeSmellMinor(int $code_smell_minor): self
    {
        $this->code_smell_minor = $code_smell_minor;

        return $this;
    }

    public function getCodeSmellInfo(): ?int
    {
        return $this->code_smell_info;
    }

    public function setCodeSmellInfo(int $code_smell_info): self
    {
        $this->code_smell_info = $code_smell_info;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeInterface
    {
        return $this->date_enregistrement;
    }

    public function setDateEnregistrement(\DateTimeInterface $date_enregistrement): self
    {
        $this->date_enregistrement = $date_enregistrement;

        return $this;
    }

}
