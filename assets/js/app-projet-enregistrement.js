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
  let t1, t2, t3, t4, t5, t6, t7, t8, t9, t10, t11, t12, t13, t14, t15, t16, t17, t18, t19, t20, t21, t22, t23, t24, t25, t26, t27, t28
  const contentType='application/json; charset=utf-8';

  //On récupère les informations sur les versions
  const nom_projet=$('#nom-projet').text().trim();
  /* On enregistre des données brutes pour l'enregistrement.
   * On n'utilise jquery pour la gestion du data Attribute car ce n'est pas fiable.
   * On utilise à la place l'appel JS standard.
   */
  t1 = document.getElementById('no-sonar');
  t2 = document.getElementById('no-sonar');
  const version_release=t1.dataset.release;
  const version_snaphot=t2.dataset.snapshot;

  const version=$('#version').text().trim();
  t3 = document.getElementById('date-version');
  const date_version=t3.dataset.date_version;

  //On récupère les exclusions noSonar
  t4 = document.getElementById('suppress-warning');
  t5 = document.getElementById('no-sonar');
  const suppress_warning=t4.dataset.s1309;
  const no_sonar=t5.dataset.nosonar;

  //On récupère les informations du projet : lignes, couverture fonctionnelle, duplication, tests unitaires et le nombre de défaut.
  t6 = document.getElementById('nombre-ligne');
  t7 = document.getElementById('couverture');
  t8 = document.getElementById('duplication');
  t9 = document.getElementById('tests-unitaires');
  t10 = document.getElementById('nombre-defaut');
  const nombre_ligne=t6.dataset.nombre_ligne;
  const couverture=t7.dataset.coverage;
  const duplication=t8.dataset.duplication;
  const tests_unitaires=t9.dataset.tests_unitaires;
  const nombre_defaut=t10.dataset.nombre_defaut;


  //On récupère les informations sur la dette technique et les anomalies.
   /* Dette technique */
  const dette=$('#dette').text().trim();
  const dette_reliability=$('#js-dette-reliability').text().trim();
  const dette_vulnerability=$('#js-dette-vulnerability').text().trim();
  const dette_code_smell=$('#js-dette-code-smell').text().trim();

  t25 = document.getElementById('js-dette');
  t26 = document.getElementById('js-dette-reliability');
  t27 = document.getElementById('js-dette-vulnerability');
  t28 = document.getElementById('js-dette-code-smell');
  const dette_minute=t25.dataset.dette_minute;
  const dette_reliability_minute=t26.dataset.dette_reliability_minute;
  const dette_vulnerability_minute=t27.dataset.dette_vulnerability_minute;
  const dette_code_smell_minute=t28.dataset.dette_code_smell_minute;

  /* Nombre d'anomalie par type */
  t11 = document.getElementById('nombre-bug');
  t12 = document.getElementById('nombre-vulnerabilite');
  t13 = document.getElementById('nombre-mauvaise-pratique');
  const nombre_bug=t11.dataset.nombre_bug;
  const nombre_vulnerability=t12.dataset.nombre_vulnerabilite;
  const nombre_code_smell=t13.dataset.nombre_code_smell;

  /* répartition des anomalies par module */
  t14 = document.getElementById('nombre-frontend');
  t15 = document.getElementById('nombre-backend');
  t16 = document.getElementById('nombre-batch');
  const frontend=t14.dataset.nombre_frontend;
  const backend=t15.dataset.nombre_backend;
  const batch=t16.dataset.nombre_batch;

  /* Répartion des anomalies par sévérité */
  t16 = document.getElementById('nombre-anomalie-bloquante');
  t17 = document.getElementById('nombre-anomalie-critique');
  t18 = document.getElementById('nombre-anomalie-info');
  t19 = document.getElementById('nombre-anomalie-majeure');
  t20 = document.getElementById('nombre-anomalie-mineure');
  const nombre_anomalie_bloquante=t16.dataset.nombre_anomalie_bloquante;
  const nombre_anomalie_critique=t17.dataset.nombre_anomalie_critique;
  const nombre_anomalie_info=t18.dataset.nombre_anomalie_info;
  const nombre_anomalie_majeure=t19.dataset.nombre_anomalie_majeure;
  const nombre_anomalie_mineure=t20.dataset.nombre_anomalie_mineure;


  //On récupère les notes sonarqube pour la version courante
  const note_reliability=$('#note-reliability').text().trim();
  const note_security=$('#note-security').text().trim();
  const note_sqale=$('#note-sqale').text().trim();

  //On récupère les hotspots.
  const note_hotspot=$('#note-hotspot').text().trim();

  //On récupère les  hotspost par sévérité
  t21 = document.getElementById('hotspot-high');
  t22 = document.getElementById('hotspot-medium');
  t23 = document.getElementById('hotspot-low');
  t24 = document.getElementById('hotspot-total');
  const hotspot_high=t21.dataset.hotspot_high;
  const hotspot_medium=t22.dataset.hotspot_medium;
  const hotspot_low=t23.dataset.hotspot_low;
  const hotspot_total=t24.dataset.hotspot_total;

  const data =
  { maven_key: maven_key, nom_projet:nom_projet,
    version_release:version_release, version_snaphot:version_snaphot, version:version,
    date_version:date_version, suppress_warning:suppress_warning, no_sonar:no_sonar,
    nombre_ligne:nombre_ligne, couverture:couverture, duplication:duplication,
    tests_unitaires:tests_unitaires, nombre_defaut:nombre_defaut,
    dette:dette, dette_minute: dette_minute,
    dette_reliability:dette_reliability, dette_vulnerability:dette_vulnerability, dette_code_smell:dette_code_smell,
    dette_reliability_minute:dette_reliability_minute, dette_vulnerability_minute:dette_vulnerability_minute, dette_code_smell_minute:dette_code_smell_minute,
    nombre_bug:nombre_bug, nombre_vulnerability:nombre_vulnerability, nombre_code_smell:nombre_code_smell,
    frontend:frontend, backend:backend, batch:batch,
    nombre_anomalie_bloquante:nombre_anomalie_bloquante, nombre_anomalie_critique:nombre_anomalie_critique,
    nombre_anomalie_info:nombre_anomalie_info, nombre_anomalie_majeur:nombre_anomalie_majeure,
    nombre_anomalie_mineur:nombre_anomalie_mineure,
    note_reliability:note_reliability, note_security:note_security,
    note_sqale:note_sqale, note_hotspot:note_hotspot, hotspot_high:hotspot_high,
    hotspot_medium:hotspot_medium, hotspot_low:hotspot_low, hotspot_total: hotspot_total,
 };


  const options = {
    url: 'http://localhost:8000/api/enregistrement', type: 'PUT', dataType: 'json',
    data: JSON.stringify(data), contentType: contentType }
    $.ajax(options).then((t) => {
        if (t.info=="OK") {log(' - INFO : Enregistrement des informations effectué.');}
          else {log(' - ERROR : L\'enregistrement n\'a pas été réussi !! !.'); }
      });
 }
