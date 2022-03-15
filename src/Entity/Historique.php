<?php
/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

namespace App\Entity;

use App\Repository\HistoriqueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueRepository::class)]
class Historique
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column(type: 'integer')]
  private $id;

  #[ORM\Column(type: 'string', length: 128)]
  private $maven_key;

  #[ORM\Column(type: 'string', length: 128)]
  private $nom_projet;

  #[ORM\Column(type: 'integer')]
  private $version_release;

  #[ORM\Column(type: 'integer')]
  private $version_snaphot;

  #[ORM\Column(type: 'string', length: 32)]
  private $version;

  #[ORM\Column(type: 'datetime')]
  private $date_version;

  #[ORM\Column(type: 'integer')]
  private $suppress_warning;

  #[ORM\Column(type: 'integer')]
  private $no_sonar;

  #[ORM\Column(type: 'integer')]
  private $nombre_ligne;

  #[ORM\Column(type: 'float')]
  private $couverture;

  #[ORM\Column(type: 'float')]
  private $duplication;

  #[ORM\Column(type: 'integer')]
  private $tests_unitaires;

  #[ORM\Column(type: 'integer')]
  private $nombre_defaut;

  #[ORM\Column(type: 'string', length: 32)]
  private $dette;

  #[ORM\Column(type: 'integer')]
  private $dette_minute;

  #[ORM\Column(type: 'string', length: 32)]
  private $dette_reliability;

  #[ORM\Column(type: 'string', length: 32)]
  private $dette_vulnerability;

  #[ORM\Column(type: 'string', length: 32)]
  private $dette_code_smell;

  #[ORM\Column(type: 'integer')]
  private $dette_reliability_minute;

  #[ORM\Column(type: 'integer')]
  private $dette_vulnerability_minute;

  #[ORM\Column(type: 'integer')]
  private $dette_code_smell_minute;

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
  private $batch;

  #[ORM\Column(type: 'integer')]
  private $nombre_anomalie_bloquante;

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

  #[ORM\Column(type: 'string', length: 4)]
  private $hotspot_high;

  #[ORM\Column(type: 'integer')]
  private $hotspot_medium;

  #[ORM\Column(type: 'integer')]
  private $hotspot_low;

  #[ORM\Column(type: 'integer')]
  private $hotspot_total;

  #[ORM\Column(type: 'boolean')]
  private $favori;

  #[ORM\Column(type: 'datetime')]
  private $date_enregistrement;

  public function getId(): ?int
  {
      return $this->id;
  }

  public function getMavenKey(): ?string
  {
      return $this->maven_key;
  }

  public function setMavenKey(string $maven_key): self
  {
      $this->maven_key = $maven_key;

      return $this;
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

  public function getVersionSnaphot(): ?int
  {
      return $this->version_snaphot;
  }

  public function setVersionSnaphot(int $version_snaphot): self
  {
      $this->version_snaphot = $version_snaphot;

      return $this;
  }

  public function getVersion(): ?string
  {
      return $this->version;
  }

  public function setVersion(string $version): self
  {
      $this->version = $version;

      return $this;
  }

  public function getDateVersion(): ?\DateTimeInterface
  {
      return $this->date_version;
  }

  public function setDateVersion(\DateTimeInterface $date_version): self
  {
      $this->date_version = $date_version;

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

  public function getDette(): ?string
  {
      return $this->dette;
  }

  public function setDette(string $dette): self
  {
      $this->dette = $dette;

      return $this;
  }

  public function getDetteMinute(): ?int
  {
      return $this->dette_minute;
  }

  public function setDetteMinute(int $dette_minute): self
  {
      $this->dette_minute = $dette_minute;

      return $this;
  }

  public function getDetteReliability(): ?string
  {
      return $this->dette_reliability;
  }

  public function setDetteReliability(string $dette_reliability): self
  {
      $this->dette_reliability = $dette_reliability;

      return $this;
  }

  public function getDetteVulnerability(): ?string
  {
      return $this->dette_vulnerability;
  }

  public function setDetteVulnerability(string $dette_vulnerability): self
  {
      $this->dette_vulnerability = $dette_vulnerability;

      return $this;
  }

  public function getDetteCodeSmell(): ?string
  {
      return $this->dette_code_smell;
  }

  public function setDetteCodeSmell(string $dette_code_smell): self
  {
      $this->dette_code_smell = $dette_code_smell;

      return $this;
  }

  public function getDetteReliabilityMinute(): ?int
  {
      return $this->dette_reliability_minute;
  }

  public function setDetteReliabilityMinute(int $dette_reliability_minute): self
  {
      $this->dette_reliability_minute = $dette_reliability_minute;

      return $this;
  }

  public function getDetteVulnerabilityMinute(): ?int
  {
      return $this->dette_vulnerability_minute;
  }

  public function setDetteVulnerabilityMinute(int $dette_vulnerability_minute): self
  {
      $this->dette_vulnerability_minute = $dette_vulnerability_minute;

      return $this;
  }

  public function getDetteCodeSmellMinute(): ?int
  {
      return $this->dette_code_smell_minute;
  }

  public function setDetteCodeSmellMinute(int $dette_code_smell_minute): self
  {
      $this->dette_code_smell_minute = $dette_code_smell_minute;

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

  public function getBatch(): ?int
  {
      return $this->batch;
  }

  public function setBatch(int $batch): self
  {
      $this->batch = $batch;

      return $this;
  }

  public function getNombreAnomalieBloquante(): ?int
  {
      return $this->nombre_anomalie_bloquante;
  }

  public function setNombreAnomalieBloquante(int $nombre_anomalie_bloquante): self
  {
      $this->nombre_anomalie_bloquante = $nombre_anomalie_bloquante;

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

  public function getHotspotHigh(): ?string
  {
      return $this->hotspot_high;
  }

  public function setHotspotHigh(string $hotspot_high): self
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

  public function getDateEnregistrement(): ?\DateTimeInterface
  {
      return $this->date_enregistrement;
  }

  public function setDateEnregistrement(\DateTimeInterface $date_enregistrement): self
  {
      $this->date_enregistrement = $date_enregistrement;

      return $this;
  }

  public function getFavori(): ?bool
  {
      return $this->favori;
  }

  public function setFavori(bool $favori): self
  {
      $this->favori = $favori;

      return $this;
  }

}
