/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import '../css/projet.css';

/* Intégration de jquery */
import $ from 'jquery';

import 'select2';
import 'select2/dist/js/i18n/fr.js';

import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(ChartDataLabels);

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';
import './app-authentification-details.js';

/* On importe les paramètres serveur. */
import {serveur} from './properties.js';

/** Librairie de tirage aléatoire */
import Chance from 'chance';

/**
 * On importe les méthodes pour :
 * Afficher les données ;
 * Enregistrer les données ;
 */
import {remplissage, afficheHotspotDetails} from './app-projet-peinture.js';
import {enregistrement} from './app-projet-enregistrement.js';

/** On importe les constantes */
import {contentType, http_200, http_400, http_401, http_403, http_404, http_406, dateOptions,
        matrice, paletteCouleur,
        deuxMille, troisMille, cinqMille, http_500} from './constante.js';

/**
 * [Description for shuffle]
 * Mélangeur de couleur
 *
 * @param mixed a
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:08:24 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const shuffle=function(a) {
  let j, x, i;
  /** On crée un nouvel objet chance */
  const chance = new Chance();

  /** On mélange la matrice */
  for (i = a.length - 1; i > 0; i--) {
    j = chance.natural({ min: 0, max: i });
    x = a[i];
    a[i] = a[j];
    a[j] = x;
  }
  return a;
};

/**
 * [Description for palette]
 * Renvoie une nouvelle palette de couleur
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:09:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const palette=function() {
  const nouvellePalette = [];
  shuffle(matrice);
  matrice.forEach(el => {
    nouvellePalette.push(paletteCouleur[el]);
  });
  return nouvellePalette;
};

/**
 * [Description for dessineMoiUnMouton]
 * Affiche le graphique des sources
 *
 * @param mixed label
 * @param mixed dataset
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:09:45 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const dessineMoiUnMouton=function(label, dataset) {
  const nouvellePalette = palette();
  const data = {labels: label,
                datasets: [{ data: dataset, backgroundColor: nouvellePalette,
                              borderWidth: 1,
                              datalabels: { align: 'center', anchor: 'center' }
                          }]
                };

  const options = {
    animations: { tension: { duration: deuxMille, easing: 'linear', loop: false } },
    maintainAspectRatio: true,
    responsive: true,
    plugins: {
      title: { display: false },
      tooltip: { enabled: true },
      legend: {},
      datalabels: {
        color: '#fff',
        font: function (context) {
          const w = context.chart.width;
          return {
            size: w < 512 ? 12 : 14, weight: 'bold'};
        },
      }
    }
  };

  const chartStatus = Chart.getChart('graphique-autre-version');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-autre-version').getContext('2d');
  const charts = new Chart(ctx, { type: 'doughnut', data, options });
  if (charts===null){
    console.info('Pour éviter une erreur sonar !!!');
  }
};

/**
 * [Description for log]
 * Affiche la log.
 *
 * @param mixed txt
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:10:19 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const log=function(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += `${new Intl.DateTimeFormat('default',
  dateOptions).format(new Date())} ${txt}\n`;
};

/**
 * [Description for ditBonjour]
 * Initialisation de la log.
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:10:52 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const ditBonjour=function() {
  log(' - Initialisation de la log...');
};

/**
 * description
 * Active la gomme pour nettoyer la log.
 */
$('.gomme-svg').on('click', function () {
  $('.log').val('');
});

/**
 * [Description for match]
 * Propriétés du selecteur de recherche.
 *
 * @param mixed params
 * @param mixed data
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:11:27 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const match=function(params, data) {
  if ($.trim(params.term) === '') {
    return data;
  }
  if (typeof data.text === 'undefined') {
    return null;
  }

  if (data.text.indexOf(params.term) > -1) {
    const modifiedData = $.extend({}, data, true);
    modifiedData.text += ' (trouvé)';
    return modifiedData;
  }
  return null;
};

/**
 * [Description for selectProjet]
 * Création du selecteur de projet.
 * http://{url}/api/liste/projet
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:11:56 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const selectProjet=async function() {
  const data = { mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/liste`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };
  const async = await $.ajax(options).then(t => {
    if (t.code===http_400 || t.code===http_406 || t.code===http_500){
      afficheMessage(t)
      sessionStorage.setItem('liste des projet autorisée', "L'utilisateur n'est pas rattaché à une équipe ou pas de projet.");
      return;
    }
    if (t.code===http_200){
      log(' - INFO : Je construit la liste des projets autorisées.');
      $('.js-projet').select2({
        matcher: match,
        placeholder: 'Cliquez pour ouvrir la liste',
        allowClear: true,
        width: '100%',
        minimumInputLength: 2,
        minimumResultsForSearch: 20,
        language: 'fr',
        data: t.projet
      });
      $('.analyse').removeClass('hide');
    }
  })
};

/**
 * [Description for afficheMessage]
 * Mutualise l'affichage des messages d'erreur.
 *
 * @param mixed t
 *
 * @return void
 *
 * Created at: 14/03/2024 10:11:15 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const afficheMessage=function(t){
  $('#callout-projet-message').removeClass('hide success alert warning primary secondary');
  $('#callout-projet-message').addClass(t.type);
  $('#js-reference-information').html(t.reference);
  $('#js-message-information').html(t.message);
}

/**
 * [Description for projetAnalyse]
 * Collecte les informations du projet (projet, version, date)
 * http://{url}/api/projet/information
 *
 * Phase 01
 *
 * {mode} = null, TEST
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return
 *
 * Created at: 19/12/2022, 22:12:44 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetInformation=function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('collecte');
  if (!collecte || collecte!='Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/information`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType,
  };

  return new Promise(resolve => {
    $.ajax(options).then(t => {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 01');
        return;
      }
      if (t.code===http_200){
        log(` - INFO : (01) Nombre de version disponible : ${t.nombreVersion}`);
      }
      resolve();
      });
    });
};

/**
 * [Description for projetMesure]
 * Collecte des mesures clés du projet (lignes, couvertures, duplication, défauts).
 * http://{url}/api/projet/mesure
 *
 * Phase 02
 *
* {mode} = null, TEST
* {mavenKey} = clé du projet
*
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:13:13 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetMesure=function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('collecte');
  if (!collecte || collecte!='Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/mesure`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };
  return new Promise(resolve => {
    $.ajax(options).then(t => {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 02');
        return;
      }
      if (t.code===http_200){
          log(' - INFO : (02) Ajout des mesures.');
      }
        resolve();
    });
  });
};

/**
  * [Description for projetRating]
  * Récupère la note pour la fiabilité, la sécurité et les mauvaises pratiques.
  * http://{url}'/api/projet/note
  *
  * Phase 03
  *
  * {mode} = null, TEST
  * {mavenKey} = clé du projet
  * {type} = reliability, security, sqale
  *
  * @param string mavenKey
  * @param string type
  *
  * @return response
  *
  * Created at: 19/12/2022, 22:15:12 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  */
const projetRating=function(mavenKey, type) {
  const collecte = sessionStorage.getItem('collecte');
  if (!collecte || collecte!='Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, type, mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/note`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  return new Promise(resolve => {
    $.ajax(options).then(t => {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 03');
        return;
      }
      if (t.code===http_200){
        log(` - INFO : (03) Collecte de la note pour le type : ${t.type}`);
      }
        resolve();
    });
  });
};

/**
 * [Description for projetOwasp]
 * Récupère le top 10 OWASP et construit la vue
 * Attention une faille peut être comptée deux fois ou plus, cela dépend du tag. Donc il est
 * possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10
 * http://{url}/api/projet/issues/owasp
 *
 * Phase 04
 *
 * {mode} = null, TEST
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:16:16 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetOwasp=function(mavenKey) {
  const collecte = sessionStorage.getItem('collecte');
  if (!collecte || collecte!='Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, mode: 'null'};
  const options = {
    url: `${serveur()}/api/projet/issues/owasp`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  return new Promise(resolve => {
    $.ajax(options).then(t=> {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 04.');
        return;
      }
      if (t.code===http_200 && t.owasp===0){
          log(' - INFO : (04) Bravo aucune faille OWASP détectée.');
      } else {
          log(` - WARN : (04) J'ai trouvé ${t.owasp} faille(s).`);
      }
      resolve();
    });
  });
};

/**
 * [Description for projetHotspot]
 * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
 * On récupère les Hotspot a examiner. Les clés sont uniques
 * (i.e. on ne se base pas sur les tags).
 * http://{url}/api/projet/hotspot
 *
 * Phase 05
 *
 * {mode} = null, TEST
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:17:17 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetHotspot=function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('collecte');
  if (!collecte || collecte!='Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, mode: 'null'};
  const options = {
    url: `${serveur()}/api/projet/hotspot`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  return new Promise(resolve => {
    $.ajax(options).then(t=> {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 05.');
        return;
      }
      if (t.code===http_200 && t.hotspots===0){
          log(' - INFO : (05) Bravo aucune faille potentielle détectée.');
      } else {
          log(` - WARN : (05) J'ai trouvé ${t.hotspots} faille(s) potentielle(s).`);
      }
    resolve();
    });
  });
};

/**
 * [Description for projetAnomalie]
 * On récupère le nombre total des défauts (BUG, VULNERABILITY, CODE_SMELL),
 * la répartition par dossier la répartition par severity et la dette technique total.
 * http://{url}/api/projet/anomalie
 *
 * Phase 06
 *
 * {mode} = null, TEST
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:13:42 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetAnomalie=function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('collecte');
  if (!collecte || collecte!='Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/anomalie`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  return new Promise(resolve => {
    $.ajax(options).then(t => {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 06.');
        return;
      }
      if (t.code===http_200){
          log(` - INFO : (06) ${t.info}`);
      }
    resolve();
    });
  });
};

/**
 * [Description for projetAnomalieDetails]
 * On récupère pour chaque type (Bug, Vulnerability et Code Smell)
 * http://{url}/api/projet/anomalie/details
 *
 * Phase 07
 *
 * {mode} = null, TEST
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:14:11 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetAnomalieDetails=function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('collecte');
  if (!collecte || collecte!='Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/anomalie/details`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  return new Promise(resolve => {
    $.ajax(options).then(t => {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 07');
        return;
      }
      if (t.code===http_200){
          log(' - INFO : (07) La fréquence des sévérités par type a été collectée.');
      } else {
          log(` - ERROR : (07) Je n'ai pas réussi à collecter les données (${t.erreur}).`);
      }
      resolve();
    });
  });
};

/**
 * [Description for projetHotspotOwasp]
 * Traitement des hotspot de type owasp pour sonarqube 8.9 et >
 * Pour chaque faille OWASP on récupère les information. Il est possible d'avoir des doublons
 * (i.e. a cause des tags).
 * http://{url}/api/projet/hotspot/owasp
 *
 * Phase 8 et 9
 *
 * {mode} = null, TEST
 * {mavenKey} = clé du projet
 * {owasp} = type d'indicateur OWASP
 *
 * @param string mavenKey
 * @param string owasp
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:18:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetHotspotOwasp=function(mavenKey, owasp) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('collecte');
  if (!collecte || collecte!='Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, mode: 'null', owasp };
  const options = {
    url: `${serveur()}/api/projet/hotspot/owasp`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  return new Promise(resolve => {
    $.ajax(options).then(t=> {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 08-09.');
        return;
      }
      if (t.code===http_200 && t.info==='effacement'){
        log(' - INFO : (08) Les enregistrements ont été supprimé de la table hostspot_owasp.');
      }
      if (t.code===http_200 && t.hotspots === 0 && t.info==='enregistrement') {
        log(` - INFO : (09) Bravo aucune faille OWASP ${owasp} potentielle détectée.`);
      }
      if (t.code===http_200 && t.hotspots !== 0 && t.info==='enregistrement') {
        log(` - WARN : (09) J'ai trouvé ${t.hotspots} faille(s) OWASP ${owasp} potentielle(s).`);
      }
      resolve();
    });
  });
};

/**
 * [Description for projetHotspotOwaspDetails]
 * On enregistre le détails des hostspot owasp
 * http://{url}/api/projet/hotspot/details
 *
 * Phase 10
 *
 * {mode} = null, TEST
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:19:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetHotspotOwaspDetails=function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('collecte');
  if (!collecte || collecte!='Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/hotspot/details`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  return new Promise(resolve => {
    $.ajax(options).then(t=> {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 10.');
        return;
      }
      if (t.code===http_200) {
        log(` - INFO : (10) On a trouvé ${t.ligne} descriptions.`);
      }
      if (t.code===http_406){
        log(` - INFO : (10) Aucun détails n'est disponible pour les hotspots.`);
      }
      resolve();
    });
  });
};

/**
 * [Description for projetNoSonar]
 * On récupére la liste des exclusions de code
 * http://{url}/api/projet/nosonar
 *
 * Phase 11
 *
 * {mode} = null, TEST
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:19:44 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetNoSonar=function(mavenKey){
    /** On vérifie s'il n'y a pas d'erreur lors du traitement */
    const collecte = sessionStorage.getItem('collecte');
    if (!collecte || collecte!='Tout va bien!') {
      return;
    }

  const data = { maven_key: mavenKey, mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/nosonar`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  return new Promise(resolve => {
    $.ajax(options).then(t=> {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 11.');
        return;
      }
      if (t.code===http_200 && t.nosonar !== 0) {
        log(` - WARM : (11) J'ai trouvé ${t.nosonar} exclusion(s) NoSonar.`);
      } else {
        log(` - INFO : (11) Bravo !!! ${t.nosonar} exclusion NoSonar trouvée.`);
      }
      resolve();
    });
  });
};

/**
 * [Description for projetTodo]
 * On récupére la liste des t_odo
 * http://{url}/api/projet/to do
 *
 * Phase 12
 *
 * {mode} = null, TEST
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 10/04/2023, 15:11:30 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetTodo=function(mavenKey){
    /** On vérifie s'il n'y a pas d'erreur lors du traitement */
    const collecte = sessionStorage.getItem('collecte');
    if (!collecte || collecte!='Tout va bien!') {
      return;
    }

  const data = { maven_key: mavenKey, mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/todo`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  return new Promise(resolve => {
    $.ajax(options).then(t=> {
      if (t.code===http_400 || t.code===http_401 || t.code===http_403 || t.code===http_404){
        afficheMessage(t)
        sessionStorage.setItem('collecte', 'Erreur phase 12');
        return;
      }
      if (t.code===http_200 && t.todo !== 0) {
          log(` - WARM : (12) J'ai trouvé ${t.todo} ToDo(s) à vérifier.`);
        } else {
          log(` - INFO : (12) Bravo !!! ${t.todo} ToDo trouvé.`);
        }
      resolve();
    });
  });
};

/**
 * [Description for finCollecte]
 * Affiche un messag de fin de collecte
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:20:20 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const finCollecte=function(){
  setTimeout(function(){
    log(` - INFO : (13) La collecte des données est terminée.`), troisMille});
    /** On vérifie s'il n'y a pas d'erreur lors du traitement */
    const collecte = sessionStorage.getItem('collecte');
    if (!collecte || collecte!='Tout va bien!') {
      const type='secondary';
      const reference=`<strong>${collecte}</strong> `;
      const message='Le processus de collecte a été interrompu !';
      const t={type, reference, message};
      afficheMessage(t);
    }
  }

/**
 * [Description for afficheMesProjets]
 * On récupére la liste des projets et des favoris de l'utilisateur
 * http://{url}/api/projet/mes-applications/liste
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:21:16 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const afficheMesProjets=function() {
  const data = { mode: 'null' };
  const options = {
    url: `${serveur()}/api/projet/mes-applications/liste`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType  };

  return new Promise(resolve => {
    $.ajax(options).then(t=> {
      if (t.code===http_400 || t.code===http_406){
        afficheMessage(t)
        return;
      }
      let str, favori, i;

      if (t.code===http_200){
        /* On efface les données.*/
        $('#tableau-liste-projet').html('');
        $('.information-texte').html('[00] - Je dors !!!');

        i=0;
        const favoriSvg=`<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 512 512" class="favori-liste-svg">
        <g transform="translate(0 512) scale(.1 -.1)">
        <path d="m1215 4984c-531-89-963-456-1139-966-52-151-69-268-70-463 0-144 4-203 22-297 99-527 412-1085 962-1713 365-418 941-950 1436-1326l134-102 134 102c495 376 1071 908 1436 1326 550 628 863 1186 962 1713 31 167 31 432-1 582-109 513-470 924-951 1084-162 54-239 67-420 73-139 4-183 2-280-16-310-55-567-188-782-403l-98-98-98 98c-213 213-472 347-777 402-128 24-346 25-470 4zm438-341c274-52 521-211 691-444 23-30 79-123 125-207 47-84 88-152 91-152s44 68 91 152c128 231 214 337 362 449 218 164 482 241 755 218 276-23 512-137 708-340 127-133 222-302 271-486 24-88 27-116 27-278 0-163-3-191-28-300-105-447-394-942-865-1480-326-373-749-775-1196-1135l-125-101-125 101c-325 262-717 620-952 868-633 668-987 1227-1109 1747-25 109-28 137-28 300 0 162 3 190 27 278 92 343 335 620 657 750 201 81 411 101 623 60z" />
        </g></svg>`;

        /**
         * Pour chaque élément de la liste des projets analysés,
         * on affiche le projet et si le projet est en favori
         * on ajoute un petit-coeur.
         */
        t.projets.forEach(element => {
          i++;
          if (element.favori){
            favori = favoriSvg;
          } else {
            favori=' - ';
          }

          str = `<tr id="name-${i}" class="open-sans">
                  <td id="key-${i}" data-mavenkey="${element.key}">${element.name}</td>
                  <td class="text-center">${favori}</td>
                  <td class="text-center capsule">
                    <span id="V-${i}" class="capsule-bulle V js-liste-valider">
                      <span id="tooltips-${i}" data-tooltip tabindex="1" title="Je choisi ce projet.">V</span>
                    <span>
                  </td>
                  <td class="text-center capsule">
                    <span id="R-${i}" class="capsule-bulle R js-liste-afficher-resultat">
                      <span id="tooltips-${i}" data-tooltip tabindex="2" title="J'affiche les résultats.">R</span>
                    </span>
                  </td>
                  <td class="text-center capsule">
                    <span id="S-${i}" class="capsule-bulle S js-liste-afficher-indicateur">
                      <span id="tooltips-${i}" data-tooltip tabindex="3" title="J'affiche le tableau de suivi.">S</span>
                    </span>
                  </td>
                  <td class="text-center capsule">
                    <span id="C-${i}" class="capsule-bulle C js-liste-cosui">
                      <span id="tooltips-${i}" data-tooltip tabindex="4" title="J'affiche le tableau d'analyse COSUI.">C</span>
                    </span>
                  </td>
                  <td class="text-center capsule">
                    <span id="O-${i}" class="capsule-bulle O js-liste-owasp">
                      <span id="tooltips-${i}" data-tooltip tabindex="5" title="J'affiche le rapport OWASP.">O</span>
                    </span>
                  </td>
                  <td class="text-center capsule">
                    <span id="RM-${i}" class="capsule-bulle RM js-liste-repartition-module">
                      <span id="tooltips-${i}" data-tooltip tabindex="6" title="J'affiche le tableau de répartition par module.">RM</span>
                    </span>
                  </td>
                  </tr>`;
          $('#tableau-liste-projet').append(str);
        });
        $(document).foundation();
        /* On met à jour le nombre des projets collectés. */
        $('#affiche-total-projet').html(`<span id="nombre-projet" class="stat">${i}</span>`);

        /* On gére le click sur le bouton V (Valider) */
        $('.js-liste-valider').on('click', e => {
          /* On récupère la valeur de l'ID. */
          const id = e.currentTarget.id;
          const a = id.split('-');
          const key='key-'+a[1];

          /* On récupère la clé maven du projet. */
          const element = document.getElementById(key);
          const mavenKey=element.dataset.mavenkey;

          /* On récupère le nom du projet */
          const b = mavenKey.split(':');
          const nom = b[1];
          const $newOption = $("<option selected='selected'></option>").val(mavenKey).text(nom);
          /* On  active le projet */
          $('select[name="projet"]').append($newOption).trigger('change');
          setTimeout(function(){
            $('.information-texte').html('[01] - Le choix du projet a été validé.');
          }, deuxMille);
        });

        /* On gére le click sur le bouton R (afficher les Résulats) */
        $('.js-liste-afficher-resultat').on('click', e => {

          /* On récupère la valeur de l'ID */
          const id = e.currentTarget.id;
          const a = id.split('-');
          const key='key-'+a[1];

          /* On récupère la clé maven du projet */
          const element = document.getElementById(key);
          const mavenKey=element.dataset.mavenkey;
          $('#select-result').html(`<strong>${mavenKey}</strong>`);
          /* on active le bouton pour afficher les infos du projet */
          $('.js-affiche-resultat').removeClass('affiche-resultat-disabled');
          $('.js-affiche-resultat').addClass('affiche-resultat-enabled');
          /* On clique sur le bouton afficher les résultats */
          $('.js-affiche-resultat').trigger('click');
          setTimeout(function(){
            $('.information-texte').html('[02] - L\'affichage des résultats est terminé.');
          }, cinqMille);
        });

        /* On gére le click sur le bouton S (afficher le tableau de suivi) */
        $('.js-liste-afficher-indicateur').on('click', e => {

          /* On récupère la valeur de l'ID. */
          const id = e.currentTarget.id;
          const a = id.split('-');
          const key='key-'+a[1];

          /* On récupère la clé maven du projet */
          const element = document.getElementById(key);
          const mavenKey=element.dataset.mavenkey;
          $('#select-result').html(`<strong>${mavenKey}</strong>`);
          /* On clique sur le bouton tableau de suivi */
          $('.js-tableau-suivi').trigger('click');
        });

        /* On gére le click sur le bouton C (Cosui) */
        $('.js-liste-cosui').on('click', e => {
          /* On récupère la valeur de l'ID */
          const id = e.currentTarget.id;
          const a = id.split('-');
          const key='key-'+a[1];

          /* On récupère la clé maven du projet */
          const element = document.getElementById(key);
          const mavenKey=element.dataset.mavenkey;
          $('#select-result').html(`<strong>${mavenKey}</strong>`);

          /* On clique sur le bouton COSUI */
          $('.js-cosui').removeClass('cosui-disabled');
          $('.js-cosui').trigger('click');
        });

        /* On gére le click sur le bouton O (afficher le rapport OWASP) */
        $('.js-liste-owasp').on('click', e => {

          /* On récupère la valeur de l'ID */
          const id = e.currentTarget.id;
          const a = id.split('-');
          const key='key-'+a[1];

          /* On récupère la clé maven du projet */
          const element = document.getElementById(key);
          const mavenKey=element.dataset.mavenkey;
          $('#select-result').html(`<strong>${mavenKey}</strong>`);
          /* On clique sur le bouton OWASP */
          $('.js-analyse-owasp').trigger('click');
        });

        /* On gére le click sur le bouton RM (afficher le rapport de Répartition par Module) */
        $('.js-liste-repartition-module').on('click', e => {

          /* On récupère la valeur de l'ID */
          const id = e.currentTarget.id;
          const a = id.split('-');
          const key='key-'+a[1];

          /* On récupère la clé maven du projet */
          const element = document.getElementById(key);
          const mavenKey=element.dataset.mavenkey;
          $('#select-result').html(`<strong>${mavenKey}</strong>`);
          /* On clique sur le bouton Répartition par module */
          $('.js-repartition-module').trigger('click');
        });
      }
      resolve();
    });
  });
};

/*************** Main du programme **************/
/* On dit bonjour */
ditBonjour();
/* On met ajour la liste des projets disponibles */
selectProjet();

/**
 * description
 * Lance la collecte des données du projet sélectionné.
 * rework de la méthode : utilisation des promises
 */
$('.js-analyse').on('click', function () {

  /** On vérifie le rôle */
  const userRating = document.querySelector('.js-user-rating');
  const roles = JSON.parse(userRating.dataset.user);
  if (!roles.includes('ROLE_COLLECTE') && !roles.includes('ROLE_BATCH') && !roles.includes('ROLE_GESTIONNAIRE')) {
    const type='alert';
    const reference='<strong>[PROJET-001]</strong>';
    const message=' Vous devez avoir au moins le rôle COLLECTE pour lancer la collecte des données.';
    $('#callout-projet-message').removeClass('hide success warning primary secondary');
    $('#callout-projet-message').addClass(type);
    $('#js-reference-information').html(reference);
    $('#js-message-information').html(message);
    return;
  }

  log(' - INFO : On lance la collecte...');
  /* on bloque le bouton afficher les resultats. */
  $('.js-affiche-resultat').removeClass('affiche-resultat-enabled');
  $('.js-affiche-resultat').addClass('affiche-resultat-disabled');

  /* On récupère la clé du projet qui est affichée. */
  const idProject = $('#select-result').text().trim();
  if (idProject === 'N.C') {
    log(' - ERROR : Vous devez choisir un projet !!!');
    return;
  }

  async function fnAsync() {
    /* Analyse du projet */
    await projetInformation(idProject);           /*(01)*/
    await projetMesure(idProject);                /*(02)*/

    /* Analyse Sécurité et Owasp. */
    await projetRating(idProject, 'reliability'); /*(03)*/
    await projetRating(idProject, 'security');    /*(03)*/
    await projetRating(idProject, 'sqale');       /*(03)*/

    await projetOwasp(idProject);                 /*(04)*/
    await projetHotspot(idProject);               /*(05)*/

    /* On récupère les infos sur les anomalies*/
    await projetAnomalie(idProject);              /*(06)*/

    /* On récupère le détails sur les anomalies*/
    await projetAnomalieDetails(idProject);       /*(07)*/

    /* On efface les traces :)*/
    await projetHotspotOwasp(idProject, 'a0');    /*(08)*/
    /* On enregistre les résultats*/
    await projetHotspotOwasp(idProject, 'a1');    /*(09)*/
    await projetHotspotOwasp(idProject, 'a2');    /*(09)*/
    await projetHotspotOwasp(idProject, 'a3');    /*(09)*/
    await projetHotspotOwasp(idProject, 'a4');    /*(09)*/
    await projetHotspotOwasp(idProject, 'a5');    /*(09)*/
    await projetHotspotOwasp(idProject, 'a6');    /*(09)*/
    await projetHotspotOwasp(idProject, 'a7');    /*(09)*/
    await projetHotspotOwasp(idProject, 'a8');    /*(09)*/
    await projetHotspotOwasp(idProject, 'a9');    /*(09)*/
    await projetHotspotOwasp(idProject, 'a10');   /*(09)*/

    /* On enregistre le détails de chaque hotspot owasp. */
    await projetHotspotOwaspDetails(idProject);   /*(10)*/

    /* Récupération des signalements noSonar et SuppressWarning. */
    await projetNoSonar(idProject);               /*(11)*/

    /* Récupération des signalements To do (TS, JAVA, XML). */
    await projetTodo(idProject);                  /*(12)*/

    /* Renvoie le statut de fin */
    finCollecte();
  }

  /* On appelle la fonction de récupèration des sévérités pour les VULNERABILITY. */
  fnAsync();

  /* on active le bouton pour afficher les infos du projet. */
  $('.js-affiche-resultat').removeClass('affiche-resultat-disabled');
  $('.js-affiche-resultat').addClass('affiche-resultat-enabled');
});

/************* Events ***************************/
/**
 * description
 * Événement : Affiche le nom de la clé du projet, active le bouton pour l'analyse.
 */
$('select[name="projet"]').on('change', function () {
  $('#select-result').html(`<strong>${$('select[name="projet"]').val().trim()}</strong>`);

  /** On enregistre la clé maven dans le session storage (utile pour la page Owasp) */
  sessionStorage.setItem('projet', $('select[name="projet"]').val().trim());

  /** On supprime la clé de collecte */
  sessionStorage.setItem('collecte', 'Tout va bien!');

  /* On regarde si le projet est en favori */
  const data = { mavenKey: $('#select-result').text().trim(), 'mode': 'null' };
  const options = {
    url: `${serveur()}/api/favori/check`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };
  $.ajax(options).then(t=> {
    if (t.code===!http_200){
      afficheMessage(t)
      sessionStorage.setItem('favori', 'Erreur check.');
      return;
    }
    /*SQLite : 0 (false) and 1 (true). */
    if (t.code===http_200 && t.favori===0 || t.favori===false) {
      $('.favori-svg').removeClass('favori-svg-select');
    }
    if (t.code===http_200 && t.favori===1 || t.favori===true) {
      $('.favori-svg').addClass('favori-svg-select');
    } else {
      $('.favori-svg').removeClass('favori-svg-select');
    }
  });

  /* On débloque les boutons. */

  /* Bouton : Lance la collecte. */
  $('.js-analyse').removeClass('lance-analyse-disabled');
  $('.js-analyse').addClass('lance-analyse-enabled');

  /* Bouton : Affiche les résultats. */
  $('.js-affiche-resultat').removeClass('affiche-resultat-disabled');
  $('.js-affiche-resultat').addClass('affiche-resultat-enabled');

  /* Bouton : Ouvre la page d'analyse OWASP. */
  $('.js-analyse-owasp').removeClass('analyse-owasp-disabled');
  $('.js-analyse-owasp').addClass('analyse-owasp-enabled');

  /* Bouton : Ouvre la page de suivi des indicateurs. */
  $('.js-tableau-suivi').removeClass('tableau-suivi-disabled');
  $('.js-tableau-suivi' ).addClass('tableau-suivi-enabled');

  /* Bouton : Ouvre la page du Comité de Suivi. */
  $('.js-cosui').removeClass('cosui-disabled');
  $('.js-cosui' ).addClass('cosui-enabled');

  /* Bouton : Ouvre la page de répartition des indicateurs par Module. */
  $('.js-repartition-module').removeClass('repartition-module-disabled');
  $('.js-repartition-module' ).addClass('repartition-module-enabled');

  /* Bouton : active le bouton enregistrement. */
  $('.js-enregistrement').removeClass('enregistrement-disabled');
  $('.js-enregistrement' ).addClass('enregistrement-enabled');
});

/**
 * description
 * On affiche la liste des projets déjà analysés et des favoris
 */
$('.js-affiche-liste').on('click', function () {
  afficheMesProjets();
  $('#modal-liste-projet').foundation('open');
});

/**
 * description
 * On affiche la liste des types d'anomalies par sévérité.
 */
$('.js-affiche-severite').on('click', function () {
  if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID') {
    $('#modal-affiche-severite').foundation('open');
  }
});

/**
 * description
 * On affiche la liste des hotspots
 */
$('#js-affiche-hotspot').on('click', function () {
  if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID') {
      $('#modal-liste-hotspot').foundation('open');
  }
});

/**
 * description
 * Événement : Ouvre la fenêtre modale de la distribution de la dette technique.
 */
$('.js-affiche-details').on('click', () => {
  if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID') {
    $('#modal-dette-technique').foundation('open');
  }
});

/**
 * description
 * On affiche la liste des tags to do par langage.
 */
$('.js-affiche-todo').on('click', function () {
  if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID') {
    $('#modal-affiche-todo').foundation('open');
  }
});

/**
 * description
 * Événement : on marque le projet comme favori.
 */
$('.favori-svg').on('click', () => {

  /* On regarde si le projet est déjà en favori. */
  if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID') {
    if ($('.favori-svg').hasClass('favori-svg-select')){
          $('.favori-svg').removeClass('favori-svg-select');
      } else {
        $('.favori-svg').addClass('favori-svg-select');
      }

    const data = { maven_key: $('#select-result').text().trim(), mode: 'null' };
    const options = {
      url: `${serveur()}/api/favori`, type: 'POST',
      dataType: 'json',  data: JSON.stringify(data), contentType };
      $.ajax(options).then( t => {
        if (t.code===!http_200){
          afficheMessage(t)
          sessionStorage.setItem('favori', 'Erreur update.');
          return;
        }
        /*SQLite : 0 (false) and 1 (true). */
        if (t.code===http_200 && t.statut===0) {
          log(' - INFO : Suppression du projet à la liste des favoris.');
        }
        if (t.code===http_200 && t.statut===1) {
          log(' - INFO : Ajout du projet à la liste des favoris.');
        }
      });
  }
});

/**
 * description
 * On affiche la répartition des versions
 */
$('#js-version-autre').on('click', () => {
  let version ;
  if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID') {
    version = document.getElementById('version-autre');
    if (version.dataset.label === undefined) {
      return;
    }
    /**
     * const label = version.dataset.label;
     * const dataset = version.dataset.dataset;
    */
    const {label, dataset} = version.dataset;
    dessineMoiUnMouton(JSON.parse(label), JSON.parse(dataset));
    $('#modal-autre-version').foundation('open');
  }
});

/**
 * description
 * On passe à la peinture
 */
$('.js-affiche-resultat').on('click', () => {
  /* On récupère la clé du projet. */
  const apiMaven = $('#select-result').text().trim();
  /** On regarde si tou vas bien ! */
  const collecte=sessionStorage.getItem('collecte');
  if (collecte===undefined || collecte!='Tout va bien!') {
    let t={}
    return;
  };
  /* On appel une fonction externe. */
  if ( $('.js-affiche-resultat').hasClass('affiche-resultat-enabled')){
      /* On récupère les résultats. */
      remplissage(apiMaven);
      afficheHotspotDetails(apiMaven);

      if ($('#enregistrement').hasClass('enregistrement-disabled')){
            $('#enregistrement').addClass('enregistrement');
            $('#enregistrement').removeClass('enregistrement-disabled');
        }
    }
});

/**
 * description
 * On lance l'enregistrement des données
 */
$('.js-enregistrement').on('click', () => {
  /** On vérifie le rôle */
  const userRating = document.querySelector('.js-user-rating');
  const roles = JSON.parse(userRating.dataset.user);

  if (!roles.includes('ROLE_COLLECTE') && !roles.includes('ROLE_BATCH') && !roles.includes('ROLE_GESTIONNAIRE')) {
    const type='alert';
    const reference='<strong>[PROJET-000]</strong>';
    const message=` Vous devez avoir au moins le rôle COLLECTE pour lancer la commande d'enregistrement.`;
    let t={type, reference, message}
    afficheMessage(t);
    return;
  }

  /* On récupère la clé du projet. */
  const apiMaven = $('#select-result').text().trim();
  enregistrement(apiMaven);
});

/**
 * description
 * On génére la route et on ouvre la page des tableau de suivi
 */
$('.js-tableau-suivi').on('click', () => {
  if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID'){
    const apiMaven = $('#select-result').text().trim();
    window.location.href='/suivi?mavenKey='+apiMaven+'&mode=null';
    } else {
    log(' - ERROR - Vous devez chosir un projet dans la liste !! !');
    }
});

/**
 * description
 * On ouvre la page COSUI
 */
$('.js-cosui').on('click', () => {
  if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID'){
    const apiMaven = $('#select-result').text().trim();
    window.location.href='/projet/cosui?mavenKey='+apiMaven;
    } else {
    log(' - ERROR - Vous devez chosir un projet dans la liste !! !');
    }
});

/**
 * description
 * On génére la route et on ouvre la page de répartition des indicateurs par module
 */
  $('.js-analyse-owasp').on('click', () => {
    if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID'){
      const mavenKey = $('#select-result').text().trim();

      /* on écrase la clé maven au cas ou */
      sessionStorage.setItem('projet', mavenKey);

      /** On ne passe plus de paramètre dans le get */
      window.location.href='/owasp';
    } else {
      log(' - ERROR - [OWASP] Vous devez chosir un projet dans la liste !! !');
      }
  });

/**
 * description
 * On génére la route et on ouvre la page de répartition des indicateurs par module
 */
$('.js-repartition-module').on('click', () => {
  if ($('select[name="projet"]').val() !=='' && $('select[name="projet"]').val() !=='TheID'){
    const apiMaven = $('#select-result').text().trim();
    window.location.href='/projet/repartition?mavenKey='+apiMaven;
  } else {
    log(' - ERROR - [Répartition] Vous devez chosir un projet dans la liste !! !');
    }
});

/***********    Main */
const e = document.getElementById('feedback');
const dernierBidule=e.dataset.bookmark;

if (dernierBidule !== 'null'){
  /* On récupère le nom du projet */
  const b = dernierBidule.split(':');
  const nom = b[1];
  const $newOption = $("<option selected='selected'></option>").val(dernierBidule).text(nom);
  /* On  active le projet */
  $('select[name="projet"]').append($newOption).trigger('change');
}
