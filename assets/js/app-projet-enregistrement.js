/*
 * Copyright (c) 2021-2022.
 * Laurent HADJADJ <laurent_h@me.com>.
 * Licensed Creative Common  CC-BY-NC-SA 4.0.
 * Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 * http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

// Intégration de jquery
import $ from 'jquery';

const date_options = {
  year: "numeric", month: "numeric", day: "numeric",
  hour: "numeric", minute: "numeric", second: "numeric",
  hour12: false
};

/**
 * description
 * Affiche la log.
 */
 function log(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += new Intl.DateTimeFormat('default', date_options).format(new Date()) + txt + '\n';
}

/**
 * description
 * Fonction de remplissage des tableaux.
 */
export function enregistrement(maven_key) {
  const contentType='application/json; charset=utf-8';

  //On récupère les informations sur les versions
  const nom_projet=$('#nom-projet').text().trim();
  /* On enregistre des données brutes pour l'enregistrement.
   * On n'utilise jquery pour la gestion du data Attribute car ce n'est pas fiable.
   * On utilise à la place l'appel JS standard.
   */
  const t1 = document.getElementById('version-release');
  const t2 = document.getElementById('version-snapshot');
  const version_release=t1.dataset.release;
  const version_snapshot=t2.dataset.snapshot;

  const version=$('#version').text().trim();
  const t3 = document.getElementById('date-version');
  const date_version=t3.dataset.date_version;

  //On récupère les exclusions noSonar
  const t4 = document.getElementById('suppress-warning');
  const t5 = document.getElementById('no-sonar');
  const suppress_warning=t4.dataset.s1309;
  const no_sonar=t5.dataset.nosonar;

  //On récupère les informations du projet : lignes, couverture fonctionnelle, duplication, tests unitaires et le nombre de défaut.
  const t6 = document.getElementById('nombre-ligne');
  const t7 = document.getElementById('nombre-ligne-de-code');
  const t8 = document.getElementById('couverture');
  const t9 = document.getElementById('duplication');
  const t10 = document.getElementById('tests-unitaires');
  const t11 = document.getElementById('nombre-defaut');
  const nombre_ligne=t6.dataset.nombre_ligne;
  const nombre_ligne_de_code=t7.dataset.nombre_ligne_de_code;
  const couverture=t8.dataset.coverage;
  const duplication=t9.dataset.duplication;
  const tests_unitaires=t10.dataset.tests_unitaires;
  const nombre_defaut=t11.dataset.nombre_defaut;

  //On récupère les informations sur la dette technique et les anomalies.
   /* Dette technique */
  const t12 = document.getElementById('js-dette');
  const dette=t12.dataset.dette_minute;

  /* Nombre d'anomalie par type */
  const t13 = document.getElementById('nombre-bug');
  const t14 = document.getElementById('nombre-vulnerabilite');
  const t15 = document.getElementById('nombre-mauvaise-pratique');
  const nombre_bug=t13.dataset.nombre_bug;
  const nombre_vulnerability=t14.dataset.nombre_vulnerabilite;
  const nombre_code_smell=t15.dataset.nombre_code_smell;

  /* répartition des anomalies par module */
  const t16 = document.getElementById('nombre-frontend');
  const t17 = document.getElementById('nombre-backend');
  const t18 = document.getElementById('nombre-batch');
  const frontend=t16.dataset.nombre_frontend;
  const backend=t17.dataset.nombre_backend;
  const batch=t18.dataset.nombre_batch;

  /* Répartion des anomalies par sévérité */
  const t19 = document.getElementById('nombre-anomalie-bloquant');
  const t20 = document.getElementById('nombre-anomalie-critique');
  const t21 = document.getElementById('nombre-anomalie-info');
  const t22 = document.getElementById('nombre-anomalie-majeur');
  const t23 = document.getElementById('nombre-anomalie-mineur');
  const nombre_anomalie_bloquant=t19.dataset.nombre_anomalie_bloquant;
  const nombre_anomalie_critique=t20.dataset.nombre_anomalie_critique;
  const nombre_anomalie_info=t21.dataset.nombre_anomalie_info;
  const nombre_anomalie_majeur=t22.dataset.nombre_anomalie_majeur;
  const nombre_anomalie_mineur=t23.dataset.nombre_anomalie_mineur;

  //On récupère les notes sonarqube pour la version courante
  const note_reliability=$('#note-reliability').text().trim();
  const note_security=$('#note-security').text().trim();
  const note_sqale=$('#note-sqale').text().trim();

  //On récupère les hotspots.
  const note_hotspot=$('#note-hotspot').text().trim();

  //On récupère les hotspost par sévérité
  const t24 = document.getElementById('hotspot-high');
  const t25 = document.getElementById('hotspot-medium');
  const t26 = document.getElementById('hotspot-low');
  const t27 = document.getElementById('hotspot-total');
  const hotspot_high=t24.dataset.hotspot_high;
  const hotspot_medium=t25.dataset.hotspot_medium;
  const hotspot_low=t26.dataset.hotspot_low;
  const hotspot_total=t27.dataset.hotspot_total;

  let favori='FALSE';
  //on récupère le statut du favori
  if ($('.favori-svg').hasClass('favori-svg-select')) { favori='TRUE' }

  const data =
  { maven_key: maven_key, nom_projet:nom_projet,
    version_release:version_release, version_snapshot:version_snapshot, version:version,
    date_version:date_version, suppress_warning:suppress_warning, no_sonar:no_sonar,
    nombre_de_ligne_de_code:nombre_ligne_de_code, nombre_ligne:nombre_ligne, couverture:couverture, duplication:duplication,
    tests_unitaires:tests_unitaires, nombre_defaut:nombre_defaut,
    dette:dette,
    nombre_bug:nombre_bug, nombre_vulnerability:nombre_vulnerability, nombre_code_smell:nombre_code_smell,
    frontend:frontend, backend:backend, batch:batch,
    nombre_anomalie_bloquant:nombre_anomalie_bloquant, nombre_anomalie_critique:nombre_anomalie_critique,
    nombre_anomalie_info:nombre_anomalie_info, nombre_anomalie_majeur:nombre_anomalie_majeur,
    nombre_anomalie_mineur:nombre_anomalie_mineur,
    note_reliability:note_reliability, note_security:note_security,
    note_sqale:note_sqale, note_hotspot:note_hotspot, hotspot_high:hotspot_high,
    hotspot_medium:hotspot_medium, hotspot_low:hotspot_low, hotspot_total: hotspot_total,
    favori: favori, initial:'FALSE'
 };

  const options = {
    url: 'http://localhost:8000/api/enregistrement', type: 'PUT', dataType: 'json',
    data: JSON.stringify(data), contentType: contentType }
    $.ajax(options).then((t) => {
        if (t.info=="OK") {log(' - INFO : Enregistrement des informations effectué.');}
          else { log(' - ERROR : L\'enregistrement n\'a pas été réussi !! !.'); }
      });
 }
