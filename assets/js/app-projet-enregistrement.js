/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

/**
 * description
 * Fonction de remplissage des tableaux.
 */
export function enregistrement(maven_key) {

  //On récupère les informations sur les versions
  const nom_projet=$('#nom-projet').text().trim();
  const clef_projet=$('#clef-projet').text().trim();
  const version_release=$('#version-release').text().trim();
  const version_snaphot=$('#version-snapshot').text().trim();
  const version=$('#version').text().trim();
  const date_version=$('#date-version').text().trim();

  //On récupère les exclusions noSonar
  const supress_warning=$('#supress-warning').text().trim();
  const no_sonar=$('#no-sonar').text().trim();


  //On récupère les informations du projet : lignes, couverture fonctionnelle, duplication, tests unitaires et le nombre de défaut.
  const nombre_ligne=$('#nombre-ligne').text().trim();
  let str1=$('#couverture').text();
  const couverture=str1.replace('%', '').trim();
  let str2=$('#duplication').text();
  const duplication=str2.replace('%', '').trim();
  const tests_unitaires= $('#tests-unitaires').text().trim();
  const nombre_defaut=$('#nombre-defaut').text().trim();

  //On récupère les informations sur la dette technique et les anomalies.
  /* Dette technique */
  const dette=$('#dette').text().trim();
  const dette_bug=$('#dette-bug').text().trim();
  const dette_vulnerability=$('#dette-vulnerability').text().trim();
  const dette_code_smell=$('#dette-code-smell').text().trim();

  /* Nombre d'anomalie */
  const nombre_bug=$('#nombre-bug').text().trim();
  const nombre_vulnerability=$('#nombre-vulnerabilite').text().trim();
  const nombre_code_smell=$('#nombre-mauvaise-pratique').text().trim();

  /* Répartion des anomalies par sévérité */
  const bug_bloquant=$('#bug-bloquante').text().trim();
  const bug_critique=$('#bug-critique').text().trim();
  const bug_info=$('#bug-info').text().trim();
  const bug_majeur=$('#bug-majeure').text().trim();
  const bug_mineur=$('#bug-mineure').text().trim();

  const vulnerabilite_bloquante=$('#vulnerabilite-bloquante').text().trim();
  const vulnerabilite_critique=$('#vulnerabilite-critique').text().trim();
  const vulnerabilite_info=$('#vulnerabilite-info').text().trim();
  const vulnerabilite_majeure=$('#vulnerabilite-majeure').text().trim();
  const vulnerabilite_mineure=$('#vulnerabilite-mineure').text().trim();

  const code_smell_bloquant=$('#mauvaise-pratique-bloquante').text().trim();
  const code_smell_critical=$('#mauvaise-pratique-critique').text().trim();
  const code_smell_info=$('#mauvaise-pratique-info').text().trim();
  const code_smell_majeur=$('#mauvaise-pratique-majeure').text().trim();
  const code_smell_mineur=$('#mauvaise-pratique-mineure').text().trim();

  //On récupère les notes sonarqube pour la version courante
  const note_reliability=$('#note-reliability').text().trim();
  const note_security=$('#note-security').text().trim();
  const note_sqale=$('#note-sqale').text().trim();

  //On récupère les hotspots.
  const note_hotspot=$('#note-hotspot').text().trim();

  //On récupère les  hotspost par sévérité
    const hotspot_high=$('#hotspot-high').text().trim();
    const hotspot_medium=$('#hotspot-medium').text().trim();
    const hotspot_low=$('#hotspot-low').text().trim();
    const hotspot_total=$('#affiche-total-hotspot').text().trim();

  const data =
  { maven_key:maven_key, nom_projet:nom_projet, clef_projet:clef_projet,
    version_release:version_release, version_snaphot:version_snaphot, version:version,
    date_version:date_version, supress_warning:supress_warning, no_sonar:no_sonar,
    nombre_ligne:nombre_ligne, couverture:couverture, duplication:duplication,
    tests_unitaires:tests_unitaires, nombre_defaut:nombre_defaut, dette:dette,
    dette_bug:dette_bug, dette_vulnerability:dette_vulnerability, dette_code_smell:dette_code_smell,
    nombre_bug:nombre_bug, nombre_vulnerability:nombre_vulnerability, nombre_code_smell:nombre_code_smell, bug_bloquant:bug_bloquant, bug_critique:bug_critique,
    bug_info:bug_info, bug_majeur:bug_majeur, bug_mineur:bug_mineur,
    vulnerabilite_bloquante:vulnerabilite_bloquante, vulnerabilite_critique:vulnerabilite_critique,
    vulnerabilite_info:vulnerabilite_info, vulnerabilite_majeure:vulnerabilite_majeure,
    vulnerabilite_mineure:vulnerabilite_mineure, code_smell_bloquant:code_smell_bloquant,
    code_smell_critical:code_smell_critical, code_smell_info:code_smell_info,
    code_smell_majeur:code_smell_majeur, code_smell_mineur:code_smell_mineur,
    note_reliability:note_reliability, note_security:note_security,
    note_sqale:note_sqale, note_hotspot:note_hotspot, hotspot_high:hotspot_high,
    hotpost_medium:hotspot_medium, hotspot_low:hotspot_low, hotspot_total: hotspot_total,
 };

  const options = {
    url: 'http://localhost:8000/api/enregistrement', type: 'PUT', dataType: 'json', data: data, contentType: contentType }
    $.ajax(options).then((t) => { console.log(t)});
 }
