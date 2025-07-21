/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/common/select2.min.css';
import '../../../styles/mon-application/projet.css';

import '../../select2/select2.min.js';
import '../../select2/i18n/fr.js'

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';
import '../../common/foundation.js';
import '../../auth/details.js';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe les constantes */
import { http_200, http_400, http_401, http_403, http_404, http_406, http_500, http_503, http_504, deuxMille, cinqMille, contentType, paletteCouleur, matrice, dateOptions, troisMille } from '../../common/constante.js';

/** On importe l'encoder */
import {encode} from '../../common/encode.js';

import {Chart, registerables} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels);

/** Librairie de tirage aléatoire, c'est une chance !, remplace random */
import Chance from 'chance';

/**
 * On importe les méthodes pour :
 * Afficher les données ;
 * Enregistrer les données ;
 */
import {remplissage, afficheHotspotDetails} from './peinture.js';
import {enregistrement} from './enregistrement.js';

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
const shuffle = function(a) {
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
 * @return void
 *
 * Created at: 19/12/2022, 22:09:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const palette = function() {
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
const dessineMoiUnMouton = function(label, dataset) {
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
    sessionStorage.setItem('ma_moulinette_info','Pour éviter une erreur SonarQube !!!');
  }
};

/**
 * [Description for log]
 * Affiche la log.
 *
 * @param string txt
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:10:19 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const log = function(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += `${new Intl.DateTimeFormat('default',
  dateOptions).format(new Date())} ${txt}\n`;
};

/**
 * [Description for ditBonjour]
 * Initialisation de la log.
 *
 * @return void
 *
 * Created at: 19/12/2022, 22:10:52 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const ditBonjour = function() {
  log(' - 📌 Initialisation de la log...');
};

/**
 * [Description for match]
 * Propriétés du sélecteur de recherche.
 *
 * @param mixed params
 * @param mixed data
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:11:27 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const match = function(params, data) {
  if ($.trim(params.term) === '') {
    return data;
  }
  if (typeof data.text === 'undefined') {
    return null;
  }

  if (data.text.indexOf(params.term.toLowerCase()) > -1) {
    const modifiedData = $.extend({}, data, true);
    modifiedData.text += ' (trouvé)';
    return modifiedData;
  }
  return null;
};

/**
 * [Description for selectProjet]
 * Création du sélecteur de projet.
 * http://{url}/api/liste/projet
 *
 * @return void
 *
 * Created at: 19/12/2022, 22:11:56 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const selectProjet = async function() {
  const options = {
    url: `${serveur()}/api/projet/liste`,
    type: 'POST',
    dataType: 'json',
    contentType
  };

  const t = await $.ajax(options);
  // 📌 Vérification des erreurs
  if (t.code !== http_200){
    const hasTrace = !!t.trace;
    const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
    showMessage(t.type, t.message, trace);
    sessionStorage.setItem('ma_moulinette_error', "L'utilisateur n'est pas rattaché à une équipe ou à un projet.");
    return;
  }
  if (t.code === http_200){
    log(' - ℹ️ Je construit la liste des projets autorisés.');
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
};

/**
 * [Description for projetAnalyse]
 * Collecte les informations du projet (projet, version, date)
 * http://{url}/api/collecte/information
 *
 * Phase 01
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 * @return
 *
 * Created at: 19/12/2022, 22:12:44 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetInformation = async function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/collecte/information`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType,
  };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 01');
        return;
      }

    if (t.code === http_200){
      // 📌 Vérification des erreurs
      log(` - ℹ️ (01) Collecte des informations pour la version : ${t.message.projet}`);
      const release = parseInt(t.message.release, 10) || 0;
      const snapshot = parseInt(t.message.snapshot, 10) || 0;
      const autre = parseInt(t.message.autre, 10) || 0;
      const nombre = release + snapshot + autre;
      log(` -     📌 Nombre de version disponible : ${nombre}`);
    } else {
      log(` - ❌ (01) Collecte des informations pour la version en échec.`);
    }

  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 01.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 01');
      log(` - ❌ (01) Collecte des informations pour la version en échec.`);
      return;
  }
};

/**
 * [Description for projetMesure]
 * Collecte des mesures clés du projet (lignes, coverage, duplication, défauts).
 * http://{url}/api/collecte/mesure
 *
 * Phase 02
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 * @return response
 *
 * Created at: 19/12/2022, 22:13:13 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetMesure = async function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/collecte/mesure`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 02');
        return;
      }
    if ( t.code === http_200){
        log(' - ℹ️ (02) Collecte des mesures globales.');
        log(` -     📌 ${t.message.issues} problème(s) trouvé(s).`);
    } else {
        log(` - ❌ (02) Collecte des mesures en échec.`);
    }
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 02.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 02');
      log(` - ❌ (02) Collecte des mesures en échec.`);
      return;
  }
};

/**
  * [Description for projetRating]
  * Récupère la note pour la fiabilité, la sécurité et les mauvaises pratiques.
  * http://{url}'/api/collecte/note
  *
  * Phase 03
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
const projetRating = async function(mavenKey, type) {
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, type };
  const options = {
    url: `${serveur()}/api/collecte/note`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 03');
        return;
      }
    if (t.code === http_200){
      log(` - ℹ️ (03) Collecte de la note pour le type : ${t.type}`);
      log(` -     📌 La note est : ${t.message.note}`);
    } else {
      log(` - ❌ (03) Collecte de la note pour ${t.message.note} en échec.`);
    }
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 03.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 03');
      log(` - ❌ (03) Collecte de la note pour ${t.message.note} en échec.`);
      return;
  }
};

/**
 * [Description for projetOwasp]
 * Récupère le top 10 OWASP et construit la vue
 * Attention une faille peut être comptée deux fois ou plus, cela dépend du tag. Donc il est
 * possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10
 * http://{url}/api/collecte/owasp
 *
 * Phase 04
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:16:16 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetOwasp = async function(mavenKey) {
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/collecte/owasp`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 04');
        return;
      }

    if (t.code === http_200){
      log(' - ℹ️ (04) Collecte des menaces OWASP.');
      if (t.owasp2021 === 'NC') {
      log(` -     📌  Le référentiel OWASP2021 n'est pas supporté par votre serveur SonarQube.`);
      }
    } else {
      log(' - ❌ (04) Collecte des menaces OWASP en échec.');
    }

    if (t.code === http_200 && t.owasp2017 === 0) {
      log(' -     ✅ Bravo aucune faille OWASP 2017 détectée.');
    }
    if (t.code === http_200 && t.owasp2021 === 0) {
      log(' -     ✅ Bravo aucune faille OWASP 2021 détectée.');
    }

    if (t.code === http_200 && t.owasp2017 !== 0) {
      let s='';
      if(parseInt(t.owasp2017,10) > 1 ){ s='s' ;}
      log(` -     ⚠️ J'ai trouvé ${t.owasp2017} faille${s}.`);
    }
    if (t.code===http_200 && t.owasp2021 !== 0 && t.owasp2021 !== 'NC') {
      let s='';
      if(parseInt(t.owasp2021,10)>1){ s='s' ;}
      log(` -     ⚠️ J'ai trouvé ${t.owasp2021} faille${s}.`);
    }
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 04.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 04');
      log(' - ❌ (04) Collecte des menaces OWASP en échec.');
      return;
  }
};

/**
 * [Description for projetHotspot]
 * Traitement des hotspots de type owasp pour SonarQube 8.9 et >
 * On récupère les Hotspot a examiner. Les clés sont uniques
 * (i.e. on ne se base pas sur les tags).
 * http://{url}/api/collecte/hotspot
 *
 * Phase 05
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:17:17 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetHotspot = async function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey};
  const options = {
    url: `${serveur()}/api/collecte/hotspot`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 05');
        return;
      }

    if (t.code === http_200){
        log(' - ℹ️ (05) Collecte des menaces potentielles.');
    } else {
        log(' - ❌ (05) Collecte des menaces potentielles en échec.');
    }

    if (t.code === http_200 && t.nombre === 0){
        log(' -     ✅ Bravo aucune faille potentielle détectée.');
    } else {
        let s='';
        if(parseInt(t.nombre,10) > 1){ s='s'; }
        log(` -     ⚠️ J'ai trouvé ${t.nombre} faille${s} potentielle${s}.`);
    }
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 05.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 05');
      log(' - ❌ (05) Collecte des menaces potentielles en échec.');
      return;
  }
};

/**
 * [Description for projetAnomalie]
 * On récupère le nombre total des défauts (BUG, VULNERABILITY, CODE_SMELL),
 * la répartition par dossier la répartition par severity et la dette technique total.
 * http://{url}/collecte/anomalie
 *
 * Phase 06
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:13:42 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetAnomalie = async function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/collecte/anomalie`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 06');
        return;
      }

      if (t.code === http_200){
          log(' - ℹ️ (06) Collecte des anomalies.');
          log(` -     📌 ${t.info}`);
      } else {
        log(' - ❌ (06) Collecte des anomalies en échec.');
      }
    } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 06.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 06');
      log(' - ❌ (06) Collecte des anomalies en échec.');
      return;
    }
};

/**
 * [Description for projetAnomalieDetails]
 * On récupère pour chaque type (Bug, Vulnerability et Code Smell)
 * http://{url}/collecte/anomalie/detail
 *
 * Phase 07
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:14:11 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetAnomalieDetails = async function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/collecte/anomalie/detail`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 07');
        return;
      }

    if (t.code === http_200){
        log(' - ℹ️ (07) La fréquence des sévérités par type a été collectée.');
    } else {
        log(` - ❌ (07) Je n'ai pas réussi à collecter les données.`);
    }
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 07.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 07');
      log(` - ❌ (07) Je n'ai pas réussi à collecter les données.`);
      return;
  }
};

/**
 * [Description for projetHotspotOwasp]
 * Traitement des hotspot de type owasp pour SonarQube 8.9 et >
 * Pour chaque faille OWASP on récupère les informations.
 * Il est possible d'avoir des doublons
 * (i.e. a cause des tags).
 * http://{url}/collecte/hotspot/owasp
 *
 * Phase 8 et 9
 * {mavenKey} = clé du projet
 * {owasp} = type d'indicateur OWASP
 *
 * @param string mavenKey
 * @param string menace
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:18:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetHotspotOwasp = async function(mavenKey, menace) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey, menace };
  const options = {
    url: `${serveur()}/api/collecte/hotspot/owasp`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 08/09');
        return;
      }

      if (t.code === http_200 && t.info === 'effacement'){
        log(' - ℹ️ (08) Les enregistrements ont été supprimé de la table hostspot_owasp.');
        if (t.owasp2021 === 'NC') {
        log(` - 📌 (09) Le référentiel OWASP2021 n'est pas supporté par votre serveur SonarQube.`);
        }
      }

      if (t.code === http_200 && t.owasp2017 === 0 && t.info === 'enregistrement') {
        log(` -     ✅ Bravo aucune faille OWASP2017 ${menace} potentielle détectée.`);
      }
      if (t.code === http_200 && t.owasp2021 === 0 && t.info === 'enregistrement' && t.owasp2021 != 'NC') {
        log(` -     ✅ Bravo aucune faille OWASP2021 ${menace} potentielle détectée.`);
      }

      if (t.code === http_200 && t.owasp2017 !== 0 && t.info === 'enregistrement' && t.owasp2017 != 'NC') {
        let s='';
        if(parseInt(t.owasp2017,10)>1){ s='s' ;}
        log(` -     ⚠️ J'ai trouvé ${t.owasp2017} faille${s} OWASP ${menace} potentielle${s}.`);
      }
      if (t.code === http_200 && t.owasp2021 !== 0 && t.owasp2021 !== 'NC' && t.info === 'enregistrement') {
        let s='';
        if(parseInt(t.owasp2021,10)>1){ s='s' ;}
        log(` -     ⚠️ J'ai trouvé ${t.owasp2021} faille${s} OWASP ${menace} potentielle${s}.`);
      }
    } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 08/09.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 08/09');
      log(` - ❌ (08/09) Collecte des menaces potentielles Owasp en échec.`);
      return;
    }
};

/**
 * [Description for projetHotspotOwaspDetails]
 * On enregistre le détails des hotspot owasp
 * http://{url}/api/collecte/hotspot/detail
 *
 * Phase 10
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:19:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetHotspotOwaspDetails = async function(mavenKey) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/collecte/hotspot/detail`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 10');
        return;
      }

    if (t.code === http_200) {
      log(` - ℹ️ (10) Collecte des informations détaillées pour les hotspots.`);
      let s='';
      if (parseInt(t.nombre,10) > 1){ s='s'; }
      log(` -     ✅ On a trouvé ${t.nombre} description${s}.`);
    }
    if (t.code===http_406){
      log(` -     ✅ Aucune information n'est disponible pour les hotspots.`);
    }  else {
      log(` - ❌ (10) Collecte des informations détaillées pour les hotspots en échec.`);
    }
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 10.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 10');
      log(` - ❌ (10) Collecte des informations détaillées pour les hotspots en échec.`);
      return;
  }
};

/**
 * [Description for projetNoSonar]
 * On récupère la liste des exclusions de code
 * http://{url}/api/collete/nosonar
 *
 * Phase 11
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:19:44 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetNoSonar = async function(mavenKey){
    /** On vérifie s'il n'y a pas d'erreur lors du traitement */
    const collecte = sessionStorage.getItem('ma_moulinette_collecte');
    if (!collecte || collecte!='Tout va bien!') {
      return;
    }

  const data = { maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/collecte/nosonar`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 11');
        return;
      }

    if (t.code === http_200) {
      log (` - ℹ️ (11) Collecte des annotations NoSonar/SuppressWarning.`);
    } else {
      log (` - ❌ (11) Collecte des annotations NoSonar/SuppressWarning en échec.`);
    }

    if (t.code === http_200 && t.nombre !== 0) {
      let s='';
      if (t.nombre>1) { s='s'; }
      log(` -     ⚠️ J'ai trouvé ${t.nombre} exclusion${s}.`);
      log(`           📌 NoSonar : ${t.message.no_sonar}`);
      log(`           📌 Suppress warning : ${t.message.suppress_warning}`);
    } else {
      log(` -     ✅ Bravo !!! ${t.nombre} exclusion NoSonar trouvée.`);
    }
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 11.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 11');
      log (` - ❌ (11) Collecte des annotations NoSonar/SuppressWarning en échec.`);
      return;
  }
};

/**
 * [Description for projetTodo]
 * On récupère la liste des t_odo
 * http://{url}/api/collecte/t_odo
 *
 * Phase 12
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 10/04/2023, 15:11:30 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetTodo = async function(mavenKey){
    /** On vérifie s'il n'y a pas d'erreur lors du traitement */
    const collecte = sessionStorage.getItem('ma_moulinette_collecte');
    if (!collecte || collecte != 'Tout va bien!') {
      return;
    }

  const data = { maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/collecte/todo`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 12');
        return;
      }

    if (t.code === http_200) {
      log (` - ℹ️ (12) Collecte des commentaires Todo.`);
    } else {
      log (` - ❌ (12) Collecte des commentaires Todo.`);
    }

    if (t.code === http_200 && t.nombre !== 0) {
      let s='';
      if (t.nombre>1){ s = 's'; }
        log(` -     ⚠️ J'ai trouvé ${t.nombre} ToDo${s} à vérifier.`);
      } else {
        log(` -     ✅ Bravo !!! ${t.nombre} ToDo trouvé.`);
      }
    } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 12.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 12');
      log (` - ❌ (12) Collecte des commentaires Todo.`);
      return;
    }
};

/**
 * [Description for projetLogger]
 * On récupère la liste des Logger
 * http://{url}/api/collecte/logger
 *
 * Phase 13
 * {mavenKey} = clé du projet
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 11/07/2024, 21:40:10 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetLogger = async function(mavenKey){
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

const data = { maven_key: mavenKey };
const options = {
  url: `${serveur()}/api/collecte/logger`, type: 'POST',
        dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 13');
        return;
      }

    if (t.code === http_404){
      log(` - 📌 (13) La collecte des LOGGERS n'a pas été activée.`);
    }

    if (t.code === http_200) {
      log(` - ℹ️ (13) Collecte des LOGGERS pour l'application JAVA.`);
      const a = parseInt(t.message.logger_info,10);
      const b = parseInt(t.message.logger_warn,10);
      const c = parseInt(t.message.logger_error,10);
      const d = parseInt(t.message.logger_debug,10);
      const nombre = a+b+c+d;
      let s='';
      if (t.nombre>1){
          s = 's';
          log(` -     ✅ J'ai trouvé ${nombre} Logger${s}.`);
      } else {
          log(` -     ⚠️ Je n'ai pas trouvé de Logger.`);
      }
    }
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors de la phase 13.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 13');
      log (` - ❌ (13) Collecte des Loggers en échec.`);
      return;
  }
};

/**
 * [Description for finCollecte]
 * Affiche un message de fin de collecte
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:20:20 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const finCollecte = function(maven_key){
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (collecte === 'Tout va bien!') {
    log(` - ℹ️ (14) La collecte des données est terminée.`);
    showMessage('success', `<strong>${collecte}</strong> Le processus de collecte a été réalisé avec success pour le projet ${maven_key} !`);
    setTimeout( ()=> { hideMessage(); }, troisMille);
  }
}

/**
 * [Description for afficheMesProjets]
 * On récupère la liste des projets et des favoris de l'utilisateur
 * http://{url}/api/projet/mes-applications/liste
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:21:16 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const afficheMesProjets = async function() {
  const options = {
    url: `${serveur()}/api/projet/mes-applications/liste`, type: 'POST',
    dataType: 'json', contentType };

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_406, http_500];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        return;
      }

    let str, favori, i;

    if (t.code===http_200){
      /* On efface les données.*/
      $('#tableau-liste-projet').html('');
      $('.information-texte').html('[00] - Je dors !!!');

      i=0;
      const favoriSvg=`<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%"
          viewbox="0 0 28 28" class="favori-liste-svg"><title>Mon projet favori.</title>
          <path d="M6.956.34C4.049.85 1.685 2.957.722 5.886c-.285.867-.378 1.54-.383 2.66 0 .826.022 1.165.12 1.705.542 3.027 2.255 6.231 5.265 9.838 1.998 2.4 5.15 5.456 7.86 7.616l.733.586.734-.586c2.709-2.16 5.861-5.215 7.859-7.616 3.01-3.607 4.723-6.811 5.265-9.838.17-.96.17-2.481-.005-3.342-.597-2.947-2.573-5.307-5.205-6.226-.887-.31-1.308-.385-2.299-.42-.76-.022-1.002-.01-1.532.092-1.697.316-3.104 1.08-4.28 2.315l-.537.563-.536-.563A7.716 7.716 0 009.528.362C8.828.224 7.634.22 6.956.34zm2.397 1.958c1.5.298 2.851 1.212 3.782 2.55.126.172.432.706.684 1.189.257.482.482.873.498.873.017 0 .24-.391.498-.873.7-1.327 1.171-1.936 1.981-2.58 1.194-.941 2.639-1.383 4.133-1.251 1.51.132 2.802.787 3.875 1.953a6.569 6.569 0 011.483 2.79c.131.506.148.667.148 1.597 0 .936-.017 1.097-.154 1.723-.574 2.568-2.156 5.41-4.734 8.5-1.784 2.142-4.1 4.451-6.546 6.519l-.684.58-.684-.58c-1.779-1.505-3.924-3.561-5.21-4.985-3.465-3.837-5.403-7.047-6.07-10.034-.137-.626-.153-.787-.153-1.723 0-.93.016-1.09.147-1.596.504-1.97 1.834-3.561 3.596-4.308a5.7 5.7 0 013.41-.344z"/></svg>`;

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
          favori =' - ';
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
                  <span id="R-${i}" class="capsule-bulle R js-liste-afficher-result">
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

      /* On gère le click sur le bouton V (Valider) */
      $('.js-liste-valider').on('click', e => {
        /* On récupère la valeur de l'ID. */
        const id = e.currentTarget.id;
        const a = id.split('-');
        const key = `key-${a[1]}`;

        /* On récupère la clé maven du projet. */
        const element = document.getElementById(key);
        const mavenKey = element.dataset.mavenkey;

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

      /* On gère le click sur le bouton R (afficher les Résultats) */
      $('.js-liste-afficher-result').on('click', e => {

        /* On récupère la valeur de l'ID */
        const id = e.currentTarget.id;
        const a = id.split('-');
        const key='key-'+a[1];

        /* On récupère la clé maven du projet */
        const element = document.getElementById(key);
        const mavenKey = element.dataset.mavenkey;
        $('#select-result').html(`<strong>${mavenKey}</strong>`);
        /* on active le bouton pour afficher les infos du projet */
        $('.js-affiche-result').removeClass('affiche-result-disabled');
        $('.js-affiche-result').addClass('affiche-result-enabled');
        /* On clique sur le bouton afficher les résultats */
        $('.js-affiche-result').trigger('click');
        setTimeout(function(){
          $('.information-texte').html(`[02] - L'affichage des résultats est terminé.`);
        }, cinqMille);
      });

      /* On gère le click sur le bouton S (afficher le tableau de suivi) */
      $('.js-liste-afficher-indicateur').on('click', e => {

        /* On récupère la valeur de l'ID. */
        const id = e.currentTarget.id;
        const a = id.split('-');
        const key = `key-${a[1]}`;

        /* On récupère la clé maven du projet */
        const element = document.getElementById(key);
        const mavenKey = element.dataset.mavenkey;
        $('#select-result').html(`<strong>${mavenKey}</strong>`);
        /* On clique sur le bouton tableau de suivi */
        $('.js-tableau-de-bord').trigger('click');
      });

      /* On gère le click sur le bouton C (COSUI) */
      $('.js-liste-cosui').on('click', e => {
        /* On récupère la valeur de l'ID */
        const id = e.currentTarget.id;
        const a = id.split('-');
        const key = `key-${a[1]}`;

        /* On récupère la clé maven du projet */
        const element = document.getElementById(key);
        const mavenKey = element.dataset.mavenkey;
        $('#select-result').html(`<strong>${mavenKey}</strong>`);

        /* On clique sur le bouton COSUI */
        $('.js-cosui').removeClass('cosui-disabled');
        $('.js-cosui').trigger('click');
      });

      /* On gère le click sur le bouton O (afficher le rapport OWASP) */
      $('.js-liste-owasp').on('click', e => {

        /* On récupère la valeur de l'ID */
        const id = e.currentTarget.id;
        const a = id.split('-');
        const key='key-'+a[1];

        /* On récupère la clé maven du projet */
        const element = document.getElementById(key);
        const mavenKey = element.dataset.mavenkey;
        $('#select-result').html(`<strong>${mavenKey}</strong>`);
        /* On clique sur le bouton OWASP */
        $('.js-analyse-owasp').trigger('click');
      });

      /* On gère le click sur le bouton RM (afficher le rapport de Répartition par Module) */
      $('.js-liste-repartition-module').on('click', e => {

        /* On récupère la valeur de l'ID */
        const id = e.currentTarget.id;
        const a = id.split('-');
        const key=`key-${a[1]}`;

        /* On récupère la clé maven du projet */
        const element = document.getElementById(key);
        const mavenKey = element.dataset.mavenkey;
        $('#select-result').html(`<strong>${mavenKey}</strong>`);
        /* On clique sur le bouton Répartition par module */
        $('.js-repartition-module').trigger('click');
      });
    }
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite.";
      showMessage('alert', message, trace);
  }
};

/***********************************************************/
/**                Main du programme                      **/
/***********************************************************/

/* On dit bonjour */
ditBonjour();
/* On met ajour la liste des projets disponibles */
selectProjet();

/*************************************************************/
/************************** Events ***************************/
/*************************************************************/
/**
 * description
 * Active la gomme pour nettoyer la log.
 */
$('.gomme-svg').on('click', function () {
  $('.log').val('');
});

/******************************************************/
/***                 Choix du projet                  */
/******************************************************/
/**
 * description
 * Événement : Affiche le nom de la clé du projet, active le bouton pour l'analyse.
 */
$('select[name="projet"]').on('change', function () {
  $('#select-result').html(`<strong>${$('select[name="projet"]').val().trim()}</strong>`);

  /** On enregistre la clé maven dans le session storage (utile pour la page Owasp) */
  sessionStorage.setItem('ma_moulinette_projet', $('select[name="projet"]').val().trim());

  /** On supprime la clé de collecte */
  sessionStorage.setItem('ma_moulinette_collecte', 'Tout va bien!');

  /* On regarde si le projet est en favori */
  const data = { maven_key: $('#select-result').text().trim() };
  const options = {
    url: `${serveur()}/api/favori/check`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };
  $.ajax(options).then(t=> {
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_favori', 'Erreur check.');
        return;
      }

    /* 0 (false) and 1 (true). */
    if (t.code === http_200 && (t.favori === 0 || t.favori === false)) {
      $('.favori-svg').removeClass('favori-svg-select');
    }
    if (t.code === http_200 && (t.favori === 1 || t.favori === true)) {
      $('.favori-svg').addClass('favori-svg-select');
    } else {
      $('.favori-svg').removeClass('favori-svg-select');
    }
  });

  /* On débloque les boutons. */

  /* Bouton : Lance la collecte. */
  $('.js-analyse').removeClass('lance-analyse-disabled');
  $('.js-analyse').addClass('lance-analyse-enabled');
  $('.js-analyse').attr('aria-disabled', 'false');

  /* Bouton : Affiche les résultats. */
  $('.js-affiche-result').removeClass('affiche-result-disabled');
  $('.js-affiche-result').addClass('affiche-result-enabled');
  $('.js-affiche-result').attr('aria-disabled', 'false');

  /* Bouton : Ouvre la page d'analyse OWASP. */
  $('.js-analyse-owasp').removeClass('analyse-owasp-disabled');
  $('.js-analyse-owasp').addClass('analyse-owasp-enabled');
  $('.js-analyse-owasp').attr('aria-disabled', 'false');

  /* Bouton : Ouvre la page de suivi des indicateurs. */
  $('.js-tableau-de-bord').removeClass('tableau-de-bord-disabled');
  $('.js-tableau-de-bord' ).addClass('tableau-de-bord-enabled');
  $('.js-tableau-de-bord').attr('aria-disabled', 'false');

  /* Bouton : Ouvre la page du Comité de Suivi. */
  $('.js-cosui').removeClass('cosui-disabled');
  $('.js-cosui' ).addClass('cosui-enabled');
  $('.js-cosui').attr('aria-disabled', 'false');

  /* Bouton : Ouvre la page de répartition des indicateurs par Module. */
  $('.js-repartition-module').removeClass('repartition-module-disabled');
  $('.js-repartition-module' ).addClass('repartition-module-enabled');
  $('.js-repartition-module').attr('aria-disabled', 'false');

  /* Bouton : active le bouton enregistrement. */
  $('.js-enregistrement').removeClass('enregistrement-disabled');
  $('.js-enregistrement' ).addClass('enregistrement-enabled');
  $('.js-enregistrement').attr('aria-disabled', 'false');
});

/******************************************************/
/***        Lance la collecte des indicateurs         */
/******************************************************/

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
    showMessage('alert','<strong>[PROJET]</strong> Vous devez avoir au moins le rôle COLLECTE pour lancer la collecte des données.')
    return;
  }

  /* On récupère la clé du projet qui est affichée. */
  const idProject = $('#select-result').text().trim();
  if (idProject === 'N.C') {
    log(' - ❌ Vous devez choisir un projet !!!');
    return;
  }

  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    log(` - ❌ Une erreur s'est produite sur le projet ${idProject}.`);
    log(`      Vous ne pouvez pas relancer d'analyse pour ce projet.`);
    showMessage('primary', `<strong>${collecte}</strong> Le processus de collecte a été interrompu !`);
  }

  log(' - ℹ️ On lance la collecte...');
  /* On bloque le bouton afficher les résultats. */
  $('.js-affiche-result').removeClass('affiche-result-enabled');
  $('.js-affiche-result').addClass('affiche-result-disabled');

  async function fnAsync() {
    /* Analyse du projet */
    await projetInformation(idProject);           /*(01)*/
    await projetMesure(idProject);                /*(02)*/

    /* Collecte des notes */
    await projetRating(idProject, 'reliability'); /*(03)*/
    await projetRating(idProject, 'security');    /*(03)*/
    await projetRating(idProject, 'sqale');      /*(03)*/

    /* Analyse Sécurité et Owasp. */
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

    /* Récupération des signalements Logger (logger_info, logger_warn, logger_error_, logger_debug). */
    await projetLogger(idProject);                /*(13)*/

    /* Renvoie le statut de fin */
    finCollecte(idProject);
  }

  /* On appelle la fonction de récupération des sévérités pour les VULNERABILITY. */
  fnAsync();

  /* On active le bouton pour afficher les infos du projet. */
  $('.js-affiche-result').removeClass('affiche-result-disabled');
  $('.js-affiche-result').addClass('affiche-result-enabled');
});

/******************************************************/
/***           Affiche les indicateurs                */
/******************************************************/

/**
 * description
 * On passe à la peinture
 */
$('.js-affiche-result').on('click', () => {
  /* On récupère la clé du projet. */
  const apiMaven = $('#select-result').text().trim();
  /** On regarde si tou vas bien ! */
  const collecte=sessionStorage.getItem('ma_moulinette_collecte');
  if (collecte===undefined || collecte!='Tout va bien!') {
    const  t = {};
    sessionStorage.info('peinture', `Pas de données. ${json_encode(t)}.`);
    return;
  };

  /* On appel une fonction externe. */
  if ( $('.js-affiche-result').hasClass('affiche-result-enabled')){
      /* On récupère les résultats. */
      remplissage(apiMaven);
      afficheHotspotDetails(apiMaven);

      if ($('#enregistrement').hasClass('enregistrement-disabled')){
            $('#enregistrement').addClass('enregistrement');
            $('#enregistrement').removeClass('enregistrement-disabled');
        }
    }
});

/******************************************************/
/***   Enregistre les résultats dans l'historique     */
/******************************************************/

/**
 * description
 * On lance l'enregistrement des données
 */
$('.js-enregistrement').on('click', () => {
  /** On vérifie le rôle */
  const userRating = document.querySelector('.js-user-rating');
  const roles = JSON.parse(userRating.dataset.user);

  if (!roles.includes('ROLE_COLLECTE') && !roles.includes('ROLE_BATCH') && !roles.includes('ROLE_GESTIONNAIRE')) {
    showMessage('alert', `<strong>[PROJET-000]</strong> Vous devez avoir au moins le rôle COLLECTE pour lancer la commande d'enregistrement.` );
    return;
  }

  /* On récupère la clé du projet. */
  const apiMaven = $('#select-result').text().trim();
  enregistrement(apiMaven);
});

/******************************************************/
/***           Ouvre la page de SUIVI                */
/******************************************************/

/**
 * description
 * On génère la route et on ouvre la page des tableau de suivi
 */
$('.js-tableau-de-bord').on('click', () => {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID'){
    const apiMaven = $('#select-result').text().trim();
    window.location.href=`${serveur()}/suivi/set?maven_key=${apiMaven}`;
    } else {
    log(' - ERROR - [SUIVI] - Vous devez choisir un projet dans la liste !! !');
    }
});

/******************************************************/
/***              Ouvre la page COSUI                 */
/******************************************************/

/**
 * description
 * On ouvre la page COSUI
 */
$('.js-cosui').on('click', () => {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID'){
    const apiMaven = $('#select-result').text().trim();

    /** on créé un hash avec la méthode reduce() comme clé de salt */
    const salt = apiMaven.split('').reduce((hash, char) => {
      return char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
    } , 0);

  /** on créé un token pour encoder les paramètres */
  const param = `${salt}|${apiMaven}`;
  const a = encode(btoa(param));
  location.href=`${serveur()}/projet/cosui?maven_key=${a}`;
  } else {
    log(' - ERROR - [COSUI] - Vous devez choisir un projet dans la liste !! !');
    }
});

/******************************************************/
/***           Ouvre la page répartition              */
/******************************************************/

/**
 * description
 * On génère la route et on ouvre la page de répartition des indicateurs par module
 */
  $('.js-analyse-owasp').on('click', () => {
    if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID'){
      const mavenKey = $('#select-result').text().trim();

      /* on écrase la clé maven au cas ou */
      sessionStorage.setItem('ma_moulinette_projet', mavenKey);

      /** On ne passe plus de paramètre dans le get */
      window.location.href = `${server()}/owasp`;
    } else {
      log(' - ERROR - [OWASP] - Vous devez choisir un projet dans la liste !! !');
      }
  });

/**
 * description
 * On génère la route et on ouvre la page de répartition des indicateurs par module
 */
$('.js-repartition-module').on('click', () => {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID'){
    const apiMaven = $('#select-result').text().trim();
    /** on créé un hash avec la méthode reduce() comme clé de salt */
    const salt = apiMaven.split('').reduce((hash, char) => {
      return char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
    } , 0);

  /** on créé un token pour encoder les paramètres */
  const param = `${salt}|${apiMaven}`;
  const a = encode(btoa(param));
  location.href=`${serveur()}/repartition?token=${a}`;
  } else {
    log(' - ERROR - [Répartition] Vous devez choisir un projet dans la liste !! !');
    }
});

/******************************************************/
/***                  Les modales                     */
/******************************************************/
/**
 * description
 * On affiche la liste des projets déjà analysés et des favoris
 */
$('#js-affiche-liste').on('click', function () {
  afficheMesProjets();
  $('#modal-liste-projet').foundation('open');
});
/**
 * description
 * On affiche la liste des types d'anomalies par sévérité.
 */
$('#js-affiche-severity').on('click', function () {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    $('#modal-affiche-severity').foundation('open');
  }
});

/**
 * description
 * On affiche la liste des hotspots
 */
$('#js-affiche-detail-hotspot').on('click', function () {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
      $('#modal-liste-hotspot').foundation('open');
  }
});

/**
 * description
 * Événement : Ouvre la fenêtre modale de la distribution de la dette technique.
 */
$('#js-affiche-detail-dette').on('click', () => {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    $('#modal-dette-technique').foundation('open');
  }
});

/**
 * description
 * On affiche la liste des tags to do par langage.
 */
$('#js-affiche-detail-todo').on('click', function () {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    $('#modal-affiche-todo').foundation('open');
  }
});

/**
 * description
 * On affiche la liste des logger par méthode
 */
$('#js-affiche-detail-logger').on('click', function () {
  $('#modal-liste-logger').foundation('open');
});

/**
 * description
 * Événement : on marque le projet comme favori.
 */
$('.favori-svg').on('click', () => {

  /* On regarde si le projet est déjà en favori. */
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    if ($('.favori-svg').hasClass('favori-svg-select')){
          $('.favori-svg').removeClass('favori-svg-select');
      } else {
        $('.favori-svg').addClass('favori-svg-select');
      }

    const data = { maven_key: $('#select-result').text().trim() };
    const options = {
      url: `${serveur()}/api/favori`, type: 'POST',
      dataType: 'json',  data: JSON.stringify(data), contentType };
    $.ajax(options).then( t => {
      // 📌 Vérification des erreurs
      const errorCodes = [http_400, http_401, http_403, http_500, http_503, http_504];
      if (errorCodes.includes(t.code)){
          const hasTrace = !!t.trace;
          const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
          showMessage(t.type, t.message, trace);
          sessionStorage.setItem('ma_moulinette_favori', 'Erreur update.');
          return;
        }

      /* 0 (false) and 1 (true). */
      if (t.code === http_200 && (t.statut === 0 || t.statut === false)) {
        log(' - ℹ️ : Suppression du projet à la liste des favoris.');
      }
      if (t.code === http_200 && (t.statut === 1 || t.statut === true)) {
        log(' - ℹ️ : Ajout du projet à la liste des favoris.');
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
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
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

/******************************************************/
/***                  Main                            */
/******************************************************/
const e = document.getElementById('feedback');
const dernierBidule = e.dataset.bookmark;

if (dernierBidule !== 'null'){
  /* On récupère le nom du projet */
  const b = dernierBidule.split(':');
  const nom = b[1];
  const $newOption = $("<option selected='selected'></option>").val(dernierBidule).text(nom);

  /* On  active le projet */
  $('select[name="projet"]').append($newOption).trigger('change');
}
