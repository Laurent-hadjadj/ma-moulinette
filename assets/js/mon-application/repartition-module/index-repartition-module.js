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
import '../../../styles/mon-application/repartition-module.css';

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

/** On importe les fonctions d'affiche des messages JS */
import {showMessage, hideMessage, typeMessage} from '../../common/message.js';

/** On importe les constantes */
import { contentType, zero, cent, mille, http_200} from '../../common/constante.js';

/** On récupère la clé maven de la clé de l'application. */
const maven_key = $('#js-app').data('application');
const setup = $('#js-setup').text().trim();

/** On initialise le tableau de résultats pour l'analyse */
/** category, severity, frontend, backend, autre, inconnue, inconnue, idc */
let analyseCollecteRepartition = [];

/**
  * [Description for updateProgressGradually]
  *
  * @param int start
  * @param int stop
  *
  * @return void
  *
  * Created at: 12/02/2025 17:57:20 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
  */
async function updateProgressGradually(start, stop) {
  const steps = 10; // Nombre d’étapes intermédiaires pour lisser la progression
  const stepDelay = 100; // 100ms entre chaque étape
  let progress = start;

  for (let i = 1; i <= steps; i++) {
      progress = start + ((stop - start) * (i / steps));
      $('.progress-meter').css('width', `${Math.round(progress)}%`);
      $('.progress-meter-text').text(`${Math.round(progress)}%`);
      await new Promise(resolve => setTimeout(resolve, stepDelay));
  }
}


/**
 * analyse
 * On lance le service d'analyse des données pour le projet
 *
 * @param string mavenKey
 * @param string category
 * @param string severity
 * @param string  css
 *
 * @returns void
 */
const analyse = async function (maven_key, category, severity, css, setup) {
  /**
   * 🏁 Animation de démarrage et de fin
   */
  const startAnalyse = a => {
      const categories = {
          'BUG': 'Bug :',
          'VULNERABILITY': 'Vulnérabilité :',
          'CODE_SMELL': 'Mauvaise Pratique :'
      };
      $('#analyse-animation').addClass('sp-volume');
      $('#analyse-texte').html(categories[a] + ' Analyse en cours...');
  };

  const stopAnalyse = () => {
      $('#analyse-animation').removeClass('sp-volume');
      $('#analyse-texte').html('<span class="open-sans">statut : Fin du traitement.</span>');
  };

  // 📌 Récupération des données du DOM
  const elements = {
      BUG: {
          BLOCKER: document.getElementById('bug-bloquant'),
          CRITICAL: document.getElementById('bug-critique'),
          INFO: document.getElementById('bug-info'),
          MAJOR: document.getElementById('bug-majeur'),
          MINOR: document.getElementById('bug-mineur')
      },
      VULNERABILITY: {
          BLOCKER: document.getElementById('vulnerability-bloquant'),
          CRITICAL: document.getElementById('vulnerability-critique'),
          INFO: document.getElementById('vulnerability-info'),
          MAJOR: document.getElementById('vulnerability-majeur'),
          MINOR: document.getElementById('vulnerability-mineur')
      },
      CODE_SMELL: {
          BLOCKER: document.getElementById('code-smell-bloquant'),
          CRITICAL: document.getElementById('code-smell-critique'),
          INFO: document.getElementById('code-smell-info'),
          MAJOR: document.getElementById('code-smell-majeur'),
          MINOR: document.getElementById('code-smell-mineur')
      }
  };

  // 📌 Configuration AJAX
  const data = { maven_key, category, severity, setup };
  const options = {
      url: `${serveur()}/api/repartition/analyse`,
      type: 'PUT',
      dataType: 'json',
      data: JSON.stringify(data),
      contentType,
      beforeSend: () => setTimeout(() => startAnalyse(category), 1),
      complete: () => setTimeout(stopAnalyse, 1)
  };

  // 🕵️‍♂️ Appel AJAX
  const t = await $.ajax(options);
  // 📌 Vérification des erreurs
  if (t.code !== http_200){
    showMessage(t.type, typeMessage(t.message));
    sessionStorage.setItem('erreur-analyse', 'true');
    return;
  }

  /** On calcule le total des anomalies analysé? */
  const nombreTotalModule = +t.repartition.frontend + +t.repartition.backend + +t.repartition.autre + +t.repartition.inconnue;

  let idc = '-', calculIdc = 0, lowerCategory, capitalizeCategory;
  // 📌 Vérification de l'indice de confiance (idc)
  if (elements[category][severity]) {
      const element = elements[category][severity];
      const lowerSeverity = severity.toLowerCase();
      const capitalizeSeverity = lowerSeverity.charAt(0).toUpperCase() + lowerSeverity.slice(1);

      if (category !== 'CODE_SMELL'){
        lowerCategory = category.toLowerCase();
        capitalizeCategory = lowerCategory.charAt(0).toUpperCase() + lowerCategory.slice(1);
      } else {
        capitalizeCategory = 'CodeSmell';
      }

      if (element.dataset[`nombre${capitalizeCategory}${capitalizeSeverity}`] !== '0') {
          calculIdc = +nombreTotalModule / +element.dataset[`nombre${capitalizeCategory}${capitalizeSeverity}`];
          idc = new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(calculIdc);
      }

      const alertClass = (calculIdc * cent != 100 && idc.trim() !== '-') ? 'texte-rouge' : 'texte-vert';

      let tableId;

      //'const tableId = category === 'BUG' ? 'mon-bo-tableau1' : category === 'VULNERABILITY' ? 'mon-bo-tableau2' : 'mon-bo-tableau3';
      if (category === 'BUG') {
          tableId = 'mon-bo-tableau1';
      } else if (category === 'VULNERABILITY') {
          tableId = 'mon-bo-tableau2';
      } else {
          tableId = 'mon-bo-tableau3';
      }

      // 📌 Construction du tableau dynamique
      const row = `
          <tr>
              <td class="${css}"><strong>${severity}</strong></td>
              <td class="text-center">${t.repartition.frontend}</td>
              <td class="text-center">${t.repartition.backend}</td>
              <td class="text-center">${t.repartition.autre}</td>
              <td class="text-center">${t.repartition.inconnue}</td>
              <td class="text-center ${alertClass}">${idc}</td>
          </tr>`;

      $(`#${tableId}`).append(row);

      /** On enregistre les informations dans le tableau de résultat.*/
      /** category, severity, frontend, backend, autre, inconnue, inconnue */
      analyseCollecteRepartition.push([category, severity, t.repartition.frontend, t.repartition.backend, t.repartition.autre, t.repartition.inconnue]);
  }
};

/**
 * [Description for collecte]
 * On lance la collecte et on affiche la répartition
 *
 * @param string maven_key
 * @param string category
 * @param string severity
 * @param integer start
 * @param integer stop
 * @param integer counter
 * @param integer timer
 * @returns void
 *
 * Created at: 19/12/2022, 22:46:29 (Europe/Paris)
 * @author Laurent HADJADJ <laurent_h@me.com>
 */
const collecte = async function (maven_key, category, severity, counter, timer) {
  // Vérification immédiate pour éviter les traitements inutiles
  if (maven_key === 'NaN' || category === 'NaN') {
      showMessage('warning', "Les paramètres pour récupérer les données sont incorrects (Erreur 500).");
      return;
  }

  // Mapping pour simplifier la conversion de `category` en `categoryTime`
  const categoryMap = {
      'BUG': 'bug',
      'VULNERABILITY': 'vulnerability',
      'CODE_SMELL': 'code-smell'
  };
  const categoryTime = categoryMap[category] || 'unknown';

  // Récupération de l'élément du DOM pour éviter des accès répétés
  const categoryTimeLower = categoryTime.toLowerCase();
  const timerElement = document.getElementById(`js-${categoryTimeLower}-time`);

  // Fonction pour mettre à jour le timer
  const changeTimer = value => {
      const minute = Math.floor(value / 60);
      const restSeconds = value % 60;
      $(`#js-${categoryTimeLower}-time`).html(`${minute}.${restSeconds}`);
  };

  // Configuration de la requête AJAX
  sessionStorage.setItem('erreur-collect-repartition', 'false');
  const data = JSON.stringify({ maven_key, category, severity, setup });

  const options = {
      url: `${serveur()}/api/repartition/collecte`,
      type: 'PUT',
      dataType: 'json',
      data: data,
      contentType,
      beforeSend: function () {
          setTimeout(() => {
              $('#collecte-animation').addClass('sp-volume');
          }, mille);
      },
      complete: function () {
          setTimeout(() => {
              $('#collecte-animation').removeClass('sp-volume');
              changeTimer(timer);
          }, mille);
      }
  };

  // Exécution de la requête AJAX
  try {
      const response = await $.ajax(options);
      if (response.code !== http_200){
        // 📌 Vérification des erreurs
        showMessage(response.type, typeMessage(response.message));
        sessionStorage.setItem('erreur-collect-repartition', 'true')
        return;
      }
      // Mise à jour du nombre d'anomalies
      $('#nombre-anomalie').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(counter));
      // Mise à jour du timer dataset
      timerElement.dataset.timer = response.temps;
  } catch(error) {
      sessionStorage.setItem('error', `Erreur lors de la collecte [${category}, ${severity}] : ${error.message}`);
  }
};

/**
 * [Description for updateRepartition]
 * On finalise le processus d'analyse en mettant à jour la table repartition avec la répartition par module.
 *
 * @param int phase
 *
 * @return void
 *
 * Created at: 16/02/2025 22:40:19 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const updateRepartition = async function(phase){
    const data = { maven_key, setup, calcul: analyseCollecteRepartition };
    const options = {
      url: `${serveur()}/api/repartition/analyse/mise-a-jour`,
      type: 'PUT',
      dataType: 'json',
      data: JSON.stringify(data),
      contentType,
    };

    // Exécution de la requête AJAX
    const t = await $.ajax(options);
    if (t.code !== http_200){
        // 📌 Vérification des erreurs
        showMessage(t.type, typeMessage(t.message));
      }
  }

/********************    Évènement    ************************/
$('#collecte-bug').on('click', async () => {
  /** On désactive le bouton */
  $('#collecte-bug').addClass('bouton-collecte-bug-disabled').attr('aria-disabled', 'true');

  /** On récupère le total et le setup */
  const total = +document.getElementById('nombre-bug').dataset.nombreBug;

  if (total == 0){
    showMessage('warning', "Il n'y a pas de données à collecter pour cette catégorie (Erreur 404).");
    setTimeout(() => { hideMessage(); }, 3000);
    return;
  }

  /** On réinitialise la barre de progression */
  updateProgressGradually(zero, zero);

  // Récupération des valeurs depuis le DOM
  const severities = [
      { key: 'BLOCKER', value: +document.getElementById('bug-bloquant').dataset.nombreBugBlocker },
      { key: 'CRITICAL', value: +document.getElementById('bug-critique').dataset.nombreBugCritical },
      { key: 'MAJOR', value: +document.getElementById('bug-majeur').dataset.nombreBugMajor },
      { key: 'MINOR', value: +document.getElementById('bug-mineur').dataset.nombreBugMinor },
      { key: 'INFO', value: +document.getElementById('bug-info').dataset.nombreBugInfo }
  ];

  // Initialisation du timer
  const timerElement = document.getElementById('js-bug-time');
  timerElement.dataset.timer = 0;

  let start = 0, stop = 0, counter = 0, tempo = 0;
  let accumulatedPercent = 0;

  // Trouver la dernière sévérité non vide
  const lastSeverityIndex = severities.findLastIndex(sev => sev.value !== 0);

  // Parcours des niveaux de sévérité
  for (let i = 0; i < severities.length; i++) {
    let statut = sessionStorage.getItem('erreur-collect-repartition');;
    if (statut === 'true'){
      /**l'appel AJAX a planté on arrête */
      $('#collecte-bug').removeClass('bouton-collecte-bug-disabled').attr('aria-disabled', 'false');
      sessionStorage.setItem('erreur-collect-repartition', 'false');
      updateProgressGradually(zero, zero);
      return;
    }

    const severity = severities[i];
    if (severity.value !== 0) {
        start = stop;
        if (i === lastSeverityIndex) {
            stop = cent;
        } else {
            // Calcul normal des pourcentages
            let calculatedStop = (severity.value / total) * cent;
            stop = Math.round(accumulatedPercent + calculatedStop);
            stop = Math.min(stop, 99);
        }

      accumulatedPercent = stop;
      counter += severity.value;
      tempo += +timerElement.dataset.timer;
      await collecte(maven_key, 'BUG', severity.key, counter, tempo);
      updateProgressGradually(start, stop);
    }
  }

  /** On réactive le bouton à la fin */
  setTimeout(function() {
        $('#collecte-bug').removeClass('bouton-collecte-bug-disabled').attr('aria-disabled', 'false');
        $('#etape-1').css('color', '#c45d4e');
  }, 3000);

});

/** On lance la collecte pour les VULNERABILITY */
$('#collecte-vulnerability').on('click', async () => {
    /** on désactive le bouton */
    $('#collecte-vulnerability').addClass('bouton-collecte-vulnerability-disabled').attr('aria-disabled', 'true');

  const total = +document.getElementById('nombre-vulnerability').dataset.nombreVulnerability;

  if (total == 0){
    showMessage('warning', "Il n'y a pas de données à collecter pour cette catégorie (Erreur 404).");
    setTimeout(() => { hideMessage(); }, 3000);
    return;
  }

  /** On réinitialise la barre de progression */
  updateProgressGradually(zero, zero);

  /** On récupère les résultats bindés dans la page */
  const severities = [
    { key: 'BLOCKER', value: +document.getElementById('vulnerability-bloquant').dataset.nombreVulnerabilityBlocker },
    { key: 'CRITICAL', value: +document.getElementById('vulnerability-critique').dataset.nombreVulnerabilityCritical },
    { key: 'MAJOR', value: +document.getElementById('vulnerability-majeur').dataset.nombreVulnerabilityMajor },
    { key: 'MINOR', value: +document.getElementById('vulnerability-mineur').dataset.nombreVulnerabilityMinor },
    { key: 'INFO', value: +document.getElementById('vulnerability-info').dataset.nombreVulnerabilityInfo }
  ];

  if (isNaN(severities[0]['value']) ||
      isNaN(severities[1]['value']) ||
      isNaN(severities[2]['value']) ||
      isNaN(severities[3]['value']) ||
      isNaN(severities[4]['value'])) {
        showMessage('alert', "Impossible de récupérer les informations concernant cette catégorie.");
      return;
    }

  const timerElement = document.getElementById('js-vulnerability-time');
  timerElement.dataset.timer = 0;

  let start = 0, stop = 0, counter = 0, tempo = 0;
  let accumulatedPercent = 0;

  // Calcul du total restant pour ajuster le dernier élément
  const lastSeverityIndex = severities.findLastIndex(sev => sev.value !== 0);

  // Parcours des niveaux de sévérité
  for (let i = 0; i < severities.length; i++) {
    let statut = sessionStorage.getItem('erreur-collect-repartition');
    if (statut === 'true'){
      /**l'appel AJAX a planté on arrête */
      $('#collecte-vulnerability').removeClass('bouton-collecte-vulnerability-disabled').attr('aria-disabled', 'false');
      sessionStorage.setItem('erreur-collect-repartition', 'false');
      updateProgressGradually(zero, zero);
      return;
    }

    const severity = severities[i];
    if (severity.value !== 0) {
        start = stop;
        if (i === lastSeverityIndex) {
            stop = cent;
        } else {
            // Calcul normal des pourcentages
            let calculatedStop = (severity.value / total) * cent;
            stop = Math.round(accumulatedPercent + calculatedStop);
            stop = Math.min(stop, 99);
        }

        // Mise à jour du cumul
        accumulatedPercent = stop;
        counter += severity.value;
        tempo += +timerElement.dataset.timer;
        await collecte(maven_key, 'VULNERABILITY', severity.key, counter, tempo);
        updateProgressGradually(start, stop);
      }
    }

    /** On réactive le bouton à la fin */
    setTimeout(function() {
      $('#collecte-vulnerability').removeClass('bouton-collecte-vulnerability-disabled').attr('aria-disabled', 'false');
      $('#etape-2').css('color', '#c45d4e');
    }, 3000);

});


/** On lance la collecte pour les CODE_SMELL */
$('#collecte-code-smell').on('click', async () => {

  /** On désactive le bouton */
  $('#collecte-code-smell').addClass('bouton-collecte-code-smell-disabled').attr('aria-disabled', 'true');
  const total = +document.getElementById('nombre-code-smell').dataset.nombreCodeSmell;

  if (total == 0){
    showMessage('warning', "Il n'y a pas de données à collecter pour cette catégorie (Erreur 404).");
    setTimeout(() => { hideMessage(); }, 3000);
    return;
  }

  /** On récupère les résultats bindés dans la page */
  const severities = [
    { key: 'BLOCKER', value: +document.getElementById('code-smell-bloquant').dataset.nombreCodeSmellBlocker },
    { key: 'CRITICAL', value: +document.getElementById('code-smell-critique').dataset.nombreCodeSmellCritical },
    { key: 'MAJOR', value: +document.getElementById('code-smell-majeur').dataset.nombreCodeSmellMajor },
    { key: 'MINOR', value: +document.getElementById('code-smell-mineur').dataset.nombreCodeSmellMinor },
    { key: 'INFO', value: +document.getElementById('code-smell-info').dataset.nombreCodeSmellInfo }
  ];

  const timerElement = document.getElementById('js-code-smell-time');
  timerElement.dataset.timer = 0;

  let start = 0, stop = 0, counter = 0, tempo = 0;
  let accumulatedPercent = 0;

  // Trouver la dernière sévérité non vide
  const lastSeverityIndex = severities.findLastIndex(sev => sev.value !== 0);

  /** On réinitialise la barre de progression */
  updateProgressGradually(zero, zero);

  // Parcours des niveaux de sévérité
  for (let i = 0; i < severities.length; i++) {
    let statut = sessionStorage.getItem('erreur-collect-repartition');
    if (statut === 'true'){
      /**l'appel AJAX a planté on arrête */
      $('#collecte-code-smell').removeClass('bouton-collecte-code-smell-disabled').attr('aria-disabled', 'false');
      sessionStorage.setItem('erreur-collect-repartition', 'false');
      updateProgressGradually(zero, zero);
      return;
    }

    const severity = severities[i];
    if (severity.value !== 0) {
        start = stop;
        if (i === lastSeverityIndex) {
            stop = cent;
        } else {
            // Calcul normal des pourcentages
            let calculatedStop = (severity.value / total) * cent;
            stop = Math.round(accumulatedPercent + calculatedStop);
            stop = Math.min(stop, 99);
        }

        // Mise à jour du cumul
        accumulatedPercent = stop;
        counter += severity.value;
        tempo += +timerElement.dataset.timer;
        await collecte(maven_key, 'CODE_SMELL', severity.key, counter, tempo);
        updateProgressGradually(start, stop);
      }
    }
    /** On réactive le bouton à la fin */
    setTimeout(function() {
      $('#collecte-code-smell').removeClass('bouton-collecte-code-smell-disabled').attr('aria-disabled', 'false');
      $('#etape-3').css('color', '#c45d4e');
    }, 3000);

});

$('.bouton-analyse').on('click', async () =>{

  let control, phase = 0;

  control = sessionStorage.getItem('erreur-analyse');

  if (control === 'true') {
    showMessage('alert', 'Une erreur générale lors du calcul de la répartition a été rencontrée (Erreur 500).');
    return;
  } else {
    sessionStorage.setItem('erreur-analyse', 'false');
  }

  /** On lance la fonction asynchrone */
  async function fnAsync() {
    let phase = 0;
    const tabTitre=`<tr>
    <th scope="col"></th>
    <th scope="col" class="text-center"><strong>Application Présentation</strong></th>
    <th scope="col" class="text-center"><strong>Application Métier</strong></th>
    <th scope="col" class="text-center"><strong>Autre</strong></th>
    <th scope="col" class="text-center"><strong>Inconnue</strong></th>
    <th scope="col" class="text-center tool-tip-repartition"><strong>IdC</strong><span class="tool-tip-text-repartition">Indice de Confiance</span></th></tr>`;

    /** BLOCKER */
    /** On affiche le tableau */
    $('#tableau-1').removeClass('hide');
    $('#mon-bo-tableau1').html(tabTitre);

    control = sessionStorage.getItem('erreur-analyse');
    await analyse(maven_key, 'BUG', 'BLOCKER', 'texte-rouge', setup);
    await analyse(maven_key, 'BUG', 'CRITICAL', 'texte-rouge', setup);
    await analyse(maven_key, 'BUG', 'MAJOR','texte-orange', setup);
    await analyse(maven_key, 'BUG', 'MINOR', 'texte-vert', setup);
    await analyse(maven_key, 'BUG', 'INFO', 'texte-bleu', setup);
    if ( control === 'true') {
      showMessage('alert', 'Une erreur lors du calcul de la répartition pour la catégorie <strong>BUG</strong> a été rencontrée (Erreur 500).');
      return;
    } else {
      sessionStorage.setItem('erreur-analyse', 'false');
      phase++;
    }

    /** VULNERABILITY */
    $('#tableau-2').removeClass('hide');
    $('#mon-bo-tableau2').html(tabTitre);
    control = sessionStorage.getItem('erreur-analyse');
    await analyse(maven_key, 'VULNERABILITY', 'BLOCKER', 'texte-rouge', setup);
    await analyse(maven_key, 'VULNERABILITY', 'CRITICAL', 'texte-rouge', setup);
    await analyse(maven_key, 'VULNERABILITY', 'MAJOR', 'texte-orange', setup);
    await analyse(maven_key, 'VULNERABILITY', 'MINOR', 'texte-vert', setup);
    await analyse(maven_key, 'VULNERABILITY', 'INFO', 'texte-bleu', setup);
    if ( control === 'true') {
      showMessage('alert', 'Une erreur lors du calcul de la répartition pour la catégorie <strong>VULNERABILITY</strong> a été rencontrée (Erreur 500).');
      return;
    } else {
      sessionStorage.setItem('erreur-analyse', 'false');
      phase++;
    }

    /** CODE_SMELL */
    $('#tableau-3').removeClass('hide');
    $('#mon-bo-tableau3').html(tabTitre);
    control = sessionStorage.getItem('erreur-analyse');
    await analyse(maven_key, 'CODE_SMELL', 'BLOCKER', 'texte-rouge', setup);
    await analyse(maven_key, 'CODE_SMELL', 'CRITICAL', 'texte-rouge', setup);
    await analyse(maven_key, 'CODE_SMELL', 'INFO', 'texte-bleu', setup);
    await analyse(maven_key, 'CODE_SMELL', 'MAJOR', 'texte-orange', setup);
    await analyse(maven_key, 'CODE_SMELL', 'MINOR', 'texte-vert', setup);
    if ( control === 'true') {
      showMessage('alert', 'Une erreur lors du calcul de la répartition pour la catégorie <strong>CODE SMELL</strong> a été rencontrée (Erreur 500).');
    } else {
      sessionStorage.setItem('erreur-analyse', 'false');
      phase++;
    }

    // On retourne la valeur phase si besoin
    return phase;
  }

  /** On lance la fonction asynchrone */
  phase = await fnAsync();
  /** On met à jour */
  updateRepartition(phase);
  /** On réinitialise le tableau */
  analyseCollecteRepartition = [];
});
