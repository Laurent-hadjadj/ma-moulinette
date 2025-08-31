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

import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe les constantes */
import { dateOptions, contentType, zero, cent, mille, http_200, http_201, http_202, http_404} from '../../common/constante.js';

/** On récupère la clé maven de la clé de l'application. */
const maven_key = $('#titre-repartition').data('application');
const setup = $('#js-setup').text().trim();

/** En tête des tableaux d'analyse */
const tabCategory =
  `<tr>
    <th scope="col"></th>
    <th scope="col" class="text-center"><strong>Application Présentation</strong></th>
    <th scope="col" class="text-center"><strong>Application Métier</strong></th>
    <th scope="col" class="text-center"><strong>Autre</strong></th>
    <th scope="col" class="text-center"><strong>Inconnue</strong></th>
    <th scope="col" class="text-center tool-tip-repartition"><strong>IdC</strong><span class="tool-tip-text-repartition">Indice de Confiance</span></th>
  </tr>`;

const tabGlobal =
  `<tr>
    <th scope="col" class="text-center"><strong>Application Présentation</strong></th>
    <th scope="col" class="text-center"><strong>Application Métier</strong></th>
    <th scope="col" class="text-center"><strong>Autre</strong></th>
    <th scope="col" class="text-center"><strong>Inconnue</strong></th>
  </tr>`;

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

  const severities = [
    { css: 'texte-rouge', severity: 'blocker' },
    { css: 'texte-rouge', severity: 'critical' },
    { css: 'texte-orange', severity: 'major' },
    { css: 'texte-vert', severity: 'minor' },
    { css: 'texte-bleu', severity: 'info' }
  ];

/** On initialise le tableau de résultats pour l'analyse */
/** category, severity, frontend, backend, autre, inconnu, idc */
let analyseCollecteRepartition = [];

/** On initialise le control des erreurs à l'ouverture de la page  */
sessionStorage.setItem('ma_moulinette_erreur-collecte-repartition', 'false');
sessionStorage.setItem('ma_moulinette_erreur-analyse-repartition', 'false');

/**
 * [Description for buildTabHistorique]
 *
 * @param mixed css
 * @param mixed severity
 * @param mixed frontend
 * @param mixed backend
 * @param mixed autre
 * @param mixed inconnu
 * @param mixed alertClass
 * @param mixed idc
 *
 * @return [type]
 *
 * Created at: 29/08/2025 12:56:41 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildTabHistorique = function(css, severity, frontend, backend, autre, inconnu, calculIdc) {
    let idc = '---';
    if (calculIdc !== 0) {
        idc = new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(calculIdc);
    }
    const alertClass = (calculIdc * cent != 100 && idc.trim() !== '---') ? 'texte-rouge' : 'texte-vert';

    return `
    <tr>
        <td class="${css}"><strong>${severity}</strong></td>
        <td class="text-center">${frontend}</td>
        <td class="text-center">${backend}</td>
        <td class="text-center">${autre}</td>
        <td class="text-center">${inconnu}</td>
        <td class="text-center ${alertClass}">${idc}</td>
    </tr>`;
}


/**
 * [Description for calculateIdc]
 *
 * @param mixed data
 * @param mixed category
 * @param mixed severity
 * @param mixed elements
 *
 * @return number
 *
 * Created at: 29/08/2025 16:01:49 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const calculateIdc = (data, category, severity, elements) => {
    const customCategory = (category !== 'code_smell') ? category : 'codeSmell';

    const total = +data[`frontend_${category}_${severity}`] + +data[`backend_${category}_${severity}`] +
                    +data[`autre_${category}_${severity}`] + +data[`inconnu_${category}_${severity}`];
    return total !== 0 ? elements[category.toUpperCase()][severity.toUpperCase()].dataset[`nombre${customCategory.charAt(0).toUpperCase() + customCategory.slice(1)}${severity.charAt(0).toUpperCase() + severity.slice(1)}`] / total : 0;
}

/**
 * [Description for generateTableRow]
 *
 * @param mixed css
 * @param mixed category
 * @param mixed severity
 * @param mixed data
 * @param mixed elements
 *
 * @return string
 *
 * Created at: 29/08/2025 16:00:51 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const generateTableRow = (css, category, severity, data, elements) => {
    const calculIdc = calculateIdc(data, category, severity, elements);
    return buildTabHistorique(css, severity.toUpperCase(), data[`frontend_${category}_${severity}`],
                              data[`backend_${category}_${severity}`], data[`autre_${category}_${severity}`],
                              data[`inconnu_${category}_${severity}`], calculIdc);
}

/**
 * analyse
 * On lance le service d'analyse des données pour le projet
 *
 * @param string mavenKey
 * @param string category
 * @param string severity
 * @param string  css
 * @param string  setup
 *
 * @returns void
 *
 * Created at: 19/12/2022, 22:46:29 (Europe/Paris)
 * @author Laurent HADJADJ <laurent_h@me.com>
 */
const analyse = async function (maven_key, category, severity, css, setup) {
  /**
   * 🏁 Animation de démarrage et de fin
   */
  const startAnalyse = () => {
    $('#analyse-animation').addClass('sp-volume');
  };

  const stopAnalyse = () => {
    $('#analyse-animation').removeClass('sp-volume');
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
console.log(t);
  // 📌 Vérification des erreurs
  if ((t.code ? t.code : null) !== http_200 &&
      (t.code ? t.code : null) !== http_201 &&
      (t.code ? t.code : null) !== http_202 &&
      (t.code ? t.code : null) !== http_404){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_erreur-analyse-repartition', 'true');
        return 99;
  }

  if (t.code === http_201){
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_erreur-analyse-repartition', 'false');
      return t; // On affiche les résultats seulement.
  }

  /** On lance l'analyse */
  if (t.code === http_202 && t.category === 'CHECK'){
    return 100;
  }

  /** On a pas de données pour ce setup, il faut lancer une collecte */
  if (t.code === http_404){
    const message = "[Répartition-Analyse] Il n'y a pas de données disponibles pour ce setup. Vous devez lancer une collecte avant de lancer une analyse (Erreur 404).";
    showMessage('warning', message);
    return 404;
  }

  /** On calcule le total des anomalies analysé ? */
  const nombreTotalModule = +t.total
  let idc = '---', calculIdc = 0, lowerCategory, capitalizeCategory;
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
      const alertClass = (calculIdc * cent != 100 && idc.trim() !== '---') ? 'texte-rouge' : 'texte-vert';

      let tableId;

      //'const tableId = category === 'BUG' ? 'mon-bo-tableau1' : category === 'VULNERABILITY' ? 'mon-bo-tableau2' : 'mon-bo-tableau3';
      if (category === 'BUG') {
          tableId = 'mon-bo-tableau-1';
      } else if (category === 'VULNERABILITY') {
          tableId = 'mon-bo-tableau-2';
      } else {
          tableId = 'mon-bo-tableau-3';
      }

      // 📌 Construction du tableau dynamique
      const row = `
        <tr>
            <td class="${css}"><strong>${severity}</strong></td>
            <td class="text-center">${t.frontend}</td>
            <td class="text-center">${t.backend}</td>
            <td class="text-center">${t.autre}</td>
            <td class="text-center">${t.inconnu}</td>
            <td class="text-center ${alertClass}">${idc}</td>
        </tr>`;

      $(`#${tableId}`).append(row);

      /** On enregistre les informations dans le tableau de résultat.*/
      /** category, severity, frontend, backend, autre, inconnu, inconnu */
      analyseCollecteRepartition.push([category, severity, t.frontend, t.backend, t.autre, t.inconnu]);
  }
  return 0;
};

/**
 * [Description for collecte]
 * On lance la collecte et on affiche la répartition
 *
 * @param string maven_key
 * @param string category
 * @param string severity
 * @param integer counter
 * @param integer timer
 *
 * @returns void
 *
 * Created at: 19/12/2022, 22:46:29 (Europe/Paris)
 * @author Laurent HADJADJ <laurent_h@me.com>
 */
const collecte = async function (maven_key, category, severity, counter, timer) {
  // Vérification immédiate pour éviter les traitements inutiles
  if (maven_key === 'NaN' || category === 'NaN') {
      showMessage('warning', "Les paramètres pour récupérer les données sont incorrects (Erreur 400).");
      return;
  }

  const $collecteAnimation1 = $('#collecte-animation1');

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
  const changeTimer = timer => {
      const minute = Math.floor(timer / 60);
      const restSeconds = timer % 60;
      $(`#js-${categoryTimeLower}-time`).html(`${minute}.${restSeconds}`);
  };

  // Configuration de la requête AJAX
  sessionStorage.setItem('ma_moulinette_erreur-collect-repartition', 'false');
  const data = JSON.stringify({ maven_key, category, severity, setup });

  const options = {
      url: `${serveur()}/api/repartition/collecte`,
      type: 'PUT',
      dataType: 'json',
      data: data,
      contentType,
      beforeSend: function () {
          setTimeout(() => {
              $collecteAnimation1.addClass('sp-volume');
          }, mille);
      },
      complete: function () {
          setTimeout(() => {
              $collecteAnimation1.removeClass('sp-volume');
              changeTimer(timer);
          }, mille);
      }
  };

  // Exécution de la requête AJAX
  try {
      const response = await $.ajax(options);

      if (response.code === http_201){
        showMessage(response.type, response.message);
        $collecteAnimation1.removeClass('sp-volume');
        setTimeout(() => { hideMessage() }, 3000);
        return 99;
      }

      if ((response.code ? response.code : null) !== http_200){
        // 📌 Vérification des erreurs
        const hasTrace = !!response.trace;
        const trace = hasTrace ? prepareTechnicalDetails(response.trace) : null;
        showMessage(response.type, response.message, trace);
        sessionStorage.setItem('ma_moulinette_erreur-collect-repartition', 'true')
        $collecteAnimation1.removeClass('sp-volume');
        return 99;
      }

      // Mise à jour du nombre d'anomalies
      $('#nombre-anomalie').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(counter));
      // Mise à jour du timer dataset
      timerElement.dataset.timer = response.temps;
  } catch(error) {
      sessionStorage.setItem('ma_moulinette_erreur-collect-repartition', 'true');
      $collecteAnimation1.removeClass('sp-volume');
      const message = `Une erreur inconnue s'est produite (Erreur 500).`;
      const techDetails = prepareTechnicalDetails(error);
      showMessage('alert', message, techDetails);
      sessionStorage.setItem('ma_moulinette_erreur-collect-repartition', `Erreur lors de la collecte [${category}, ${severity}] : ${error.message}`);
      return 99;
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

    // 📌 Vérification des erreurs
    if ((t.code ? t.code : null) !== http_200){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
      return 1;
    }
}

/********************    Évènement    ************************/
$('#bouton-collecte-bug').on('click', async () => {
  const $boutonCollecteBug = $('#bouton-collecte-bug');
  const $jsCollecteBug = $('#js-collecte-bug');

  /** On désactive le bouton */
  $jsCollecteBug.addClass('disabled-wrapper');
  $boutonCollecteBug.addClass('disabled-bouton clicked-true');
  $boutonCollecteBug.attr('aria-disabled', 'true');
  $boutonCollecteBug.attr('aria-label', "Collecte indisponible pour le moment.");
  $boutonCollecteBug.attr('tabindex', '-1');

  /** On récupère le total et le setup */
  const total = +document.getElementById('nombre-bug').dataset.nombreBug;

  if (total == 0){
    showMessage('primary', "Il n'y a pas de données à collecter pour la catégorie <strong>Bug</strong>.");
    setTimeout(() => { hideMessage(); }, 3000);
    $jsCollecteBug.removeClass('disabled-wrapper');
    $boutonCollecteBug.removeClass('disabled-wrapper clicked-true');
    $boutonCollecteBug.attr('aria-disabled', 'false');
    return;
  }

  // Récupération des valeurs depuis le DOM
  const severities = [
      { key: 'BLOCKER', value: +document.getElementById('bug-bloquant').dataset.nombreBugBlocker },
      { key: 'CRITICAL', value: +document.getElementById('bug-critique').dataset.nombreBugCritical },
      { key: 'MAJOR', value: +document.getElementById('bug-majeur').dataset.nombreBugMajor },
      { key: 'MINOR', value: +document.getElementById('bug-mineur').dataset.nombreBugMinor },
      { key: 'INFO', value: +document.getElementById('bug-info').dataset.nombreBugInfo }
  ];

  if (isNaN(severities[0]['value']) ||
      isNaN(severities[1]['value']) ||
      isNaN(severities[2]['value']) ||
      isNaN(severities[3]['value']) ||
      isNaN(severities[4]['value'])) {
        showMessage('alert', "Impossible de récupérer les informations concernant cette catégorie.");
        $boutonCollecteBug.removeClass('clicked-true').addClass('clicked-false');
      return;
    }

  // Initialisation du timer
  const timerElement = document.getElementById('js-bug-time');
  timerElement.dataset.timer = 0;

  let counter = 0, tempo = 0;

  // Trouver la dernière sévérité non vide
  const lastSeverityIndex = severities.findLastIndex(sev => sev.value !== 0);

  // Parcours des niveaux de sévérité
  for (let i = 0; i < severities.length; i++) {
    let statut = sessionStorage.getItem('ma_moulinette_erreur-collect-repartition');
    if (statut === 'true'){
      /**l'appel AJAX a planté on arrête */
      $boutonCollecteBug.removeClass('clicked-true').addClass('clicked-false');
      sessionStorage.setItem('ma_moulinette_erreur-collect-repartition', 'false');
      return;
    }

      const severity = severities[i];
      counter += severity.value;
      tempo += +timerElement.dataset.timer;
      const result = await collecte(maven_key, 'BUG', severity.key, counter, tempo);
  }

  /** On réactive le bouton à la fin */
  setTimeout(function() {
        $boutonCollecteBug.removeClass('clicked-true disabled-bouton');
        $boutonCollecteBug.attr('aria-label', "Lance la collecte des anomalies SonarQube de type fiabilité.");
        $boutonCollecteBug.removeAttr('aria-disabled tabindex');
        $('#etape-1').css('color', '#c45d4e');
  }, 3000);

});

/** On lance la collecte pour les VULNERABILITY */
$('#bouton-collecte-vulnerability').on('click', async () => {
  const $boutonCollecteVulnerability = $('#bouton-collecte-vulnerability');
  const $jsCollecteVulnerability = $('#js-collecte-vulnerability');

  /** On désactive le bouton */
  $jsCollecteVulnerability.addClass('disabled-wrapper');
  $boutonCollecteVulnerability.addClass('disabled-bouton clicked-true');
  $boutonCollecteVulnerability.attr('aria-disabled', 'true');
  $boutonCollecteVulnerability.attr('aria-label', "Collecte indisponible pour le moment.");
  $boutonCollecteVulnerability.attr('tabindex', '-1');

  const total = +document.getElementById('nombre-vulnerability').dataset.nombreVulnerability;

  if (total == 0){
    showMessage('primary', "Il n'y a pas de données à collecter pour la catégorie <strong>Vulnerability</strong>.");
    setTimeout(() => { hideMessage(); }, 3000);
    $jsCollecteVulnerability.removeClass('disabled-wrapper');
    $boutonCollecteVulnerability.removeClass('disabled-bouton clicked-true');
    $boutonCollecteVulnerability.attr('aria-disabled', 'false');
    return;
  }

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
        $boutonCollecteVulnerability.removeClass('clicked-true').addClass('clicked-false');
      return;
    }

  const timerElement = document.getElementById('js-vulnerability-time');
  timerElement.dataset.timer = 0;

  let counter = 0, tempo = 0;

  // Calcul du total restant pour ajuster le dernier élément
  const lastSeverityIndex = severities.findLastIndex(sev => sev.value !== 0);

  // Parcours des niveaux de sévérité
  for (let i = 0; i < severities.length; i++) {
    let statut = sessionStorage.getItem('ma_moulinette_erreur-collect-repartition');
    if (statut === 'true'){
      /**l'appel AJAX a planté on arrête */
      $boutonCollecteVulnerability.removeClass('clicked-true').addClass('clicked-false');
      sessionStorage.setItem('ma_moulinette_erreur-collect-repartition', 'false');
      return;
    }

    const severity = severities[i];
    if (severity.value !== 0) {
        counter += severity.value;
        tempo += +timerElement.dataset.timer;
        const result = await collecte(maven_key, 'VULNERABILITY', severity.key, counter, tempo);
      }
    }

    /** On réactive le bouton à la fin */
    setTimeout(function() {
        $boutonCollecteVulnerability.removeClass('clicked-true disabled-bouton');
        $boutonCollecteVulnerability.attr('aria-label', "Lance la collecte des anomalies SonarQube de type vulnérabilité.");
        $boutonCollecteVulnerability.removeAttr('aria-disabled tabindex');
      $('#etape-2').css('color', '#c45d4e');
    }, 3000);

});

/** On lance la collecte pour les CODE_SMELL */
$('#bouton-collecte-code-smell').on('click', async () => {
  const $boutonCollecteCodeSmell = $('#bouton-collecte-code-smell');
  const $jsCollecteCodeSmell = $('#js-collecte-code-smell');

  /** On désactive le bouton */
  $jsCollecteCodeSmell.addClass('disabled-wrapper');
  $boutonCollecteCodeSmell.addClass('disabled-bouton clicked-true');
  $boutonCollecteCodeSmell.attr('aria-disabled', 'true');
  $boutonCollecteCodeSmell.attr('aria-label', "Collecte indisponible pour le moment.");
  $boutonCollecteCodeSmell.attr('tabindex', '-1');

  const total = +document.getElementById('nombre-code-smell').dataset.nombreCodeSmell;

  if (total == 0){
    showMessage('primary', "Il n'y a pas de données à collecter pour la catégorie <strong>Code_Smell</strong>.");
    setTimeout(() => { hideMessage(); }, 3000);
    $jsCollecteCodeSmell.removeClass('disabled-wrapper');
    $boutonCollecteCodeSmell.removeClass('disabled-bouton clicked-true');
    $boutonCollecteCodeSmell.attr('aria-disabled', 'false');
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

  if (isNaN(severities[0]['value']) ||
      isNaN(severities[1]['value']) ||
      isNaN(severities[2]['value']) ||
      isNaN(severities[3]['value']) ||
      isNaN(severities[4]['value'])) {
        showMessage('alert', "Impossible de récupérer les informations concernant cette catégorie.");
        $boutonCollecteCodeSmell.removeClass('clicked-true').addClass('clicked-false');
        return;
    }

  const timerElement = document.getElementById('js-code-smell-time');
  timerElement.dataset.timer = 0;

  let counter = 0, tempo = 0;

  // Trouver la dernière sévérité non vide
  const lastSeverityIndex = severities.findLastIndex(sev => sev.value !== 0);

  // Parcours des niveaux de sévérité
  for (let i = 0; i < severities.length; i++) {
    let statut = sessionStorage.getItem('ma_moulinette_erreur-collect-repartition');
    if (statut === 'true'){
      /**l'appel AJAX a planté on arrête */
      $boutonCollecteCodeSmell.removeClass('clicked-true').addClass('clicked-false');
      $boutonCollecteCodeSmell.attr('aria-disabled', 'false');
      sessionStorage.setItem('ma_moulinette_erreur-collect-repartition', 'false');
      return;
    }

    const severity = severities[i];
    if (severity.value !== 0) {
        // Mise à jour du cumul
        counter += severity.value;
        tempo += +timerElement.dataset.timer;
        const result = await collecte(maven_key, 'CODE_SMELL', severity.key, counter, tempo);
      }
    }
    /** On réactive le bouton à la fin */
    setTimeout(function() {
        $boutonCollecteCodeSmell.removeClass('clicked-true disabled-bouton');
        $boutonCollecteCodeSmell.attr('aria-label', "Lance la collecte des anomalies SonarQube de type mauvaise-pratique.");
        $boutonCollecteCodeSmell.removeAttr('aria-disabled tabindex');
      $('#etape-3').css('color', '#c45d4e');
    }, 3000);
});

$('#bouton-analyse').on('click', async () =>{
  let control, phase = 0;

  control = sessionStorage.getItem('ma_moulinette_erreur-analyse-repartition');

  if (control === 'true') {
    showMessage('alert', 'Une erreur générale lors du calcul de la répartition a été rencontrée (Erreur 500).');
    return 99;
  } else {
    sessionStorage.setItem('ma_moulinette_erreur-analyse-repartition', 'false');
  }

  /** On lance la fonction asynchrone d'analyse pour la category*/
  async function analyseCategory(category) {
    const a1 = await analyse(maven_key, category, 'BLOCKER', 'texte-rouge', setup);
    const a2 = await analyse(maven_key, category, 'CRITICAL', 'texte-rouge', setup);
    const a3 = await analyse(maven_key, category, 'MAJOR','texte-orange', setup);
    const a4 = await analyse(maven_key, category, 'MINOR', 'texte-vert', setup);
    const a5 = await analyse(maven_key, category, 'INFO', 'texte-bleu', setup);
    return (+a1 + +a2 + +a3 + +a4 + +a5);
  }

  /** On vérifie so on a déjà une analyse complète pour ce projet  */
  const checkIfExist = await analyse(maven_key, 'CHECK', 'INFO', 'texte-vert', setup);
  setTimeout(function() { hideMessage();  }, 5000);

  /** On affiche les données historisée ou on lance l'analyse*/
  if (typeof checkIfExist === 'object' && checkIfExist !== null) {
    if (checkIfExist.code !== http_200 && checkIfExist.code != http_201 && checkIfExist.code != http_202){
      // 📌 Vérification des erreurs
      const hasTrace = !!response.trace;
      const trace = hasTrace ? prepareTechnicalDetails(checkIfExist.trace) : null;
      showMessage(checkIfExist.type, checkIfExist.message, trace);
      sessionStorage.setItem('ma_moulinette_erreur-collect-repartition', 'true')
      return 99;
    }

    /** On affiche les données historisées */
    if (checkIfExist.code === http_201){
      /** On affiche le tableau */
      $('#js-analyse-mode').html(checkIfExist.mode);
      $('#js-analyse-setup').html(checkIfExist.setup);
      $('#js-analyse-date').html(new Intl.DateTimeFormat('default', dateOptions).format(new Date(checkIfExist.date_enregistrement)));

      $('#tableau-0').removeClass('hide');
      $('#mon-bo-tableau-thead-0').html(tabGlobal);
      // 📌 Construction du tableau dynamique
      const row = `
      <tr>
        <td class="text-center stat-mini color-noir">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(checkIfExist.data.frontend)}</td>
        <td class="text-center stat-mini color-noir">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(checkIfExist.data.backend)}</td>
        <td class="text-center stat-mini color-noir">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(checkIfExist.data.autre)}</td>
        <td class="text-center stat-mini color-noir">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(checkIfExist.data.inconnu)}</td>
      </tr>`;

      $(`#mon-bo-tableau-0`).html('');
      $(`#mon-bo-tableau-0`).append(row);

      let r = '';

      /** On affiche le tableau */
      $('#tableau-1').removeClass('hide');
      $('#mon-bo-tableau-thead-1').html(tabCategory);
      $(`#mon-bo-tableau-1`).html('');

      severities.forEach(sev => {
        r += generateTableRow(sev.css, 'bug', sev.severity, checkIfExist.data, elements);
      });
      $(`#mon-bo-tableau-1`).html(r);

      /** VULNERABILITY */
      $('#tableau-2').removeClass('hide');
      $('#mon-bo-tableau-2').html(tabCategory);
      $(`#mon-bo-tableau-2`).html('');

      r = '';
      severities.forEach(sev => {
        r += generateTableRow(sev.css, 'vulnerability', sev.severity, checkIfExist.data, elements);
      });
      $(`#mon-bo-tableau-2`).html(r);

      /** CODE_SMELL */
      $('#tableau-3').removeClass('hide');
      $('#mon-bo-tableau-3').html(tabCategory);
      $('#mon-bo-tableau-3').html('');

      r = '';
      severities.forEach(sev => {
        r += generateTableRow(sev.css, 'code_smell', sev.severity, checkIfExist.data, elements);
      });
      $(`#mon-bo-tableau-3`).html(r);
      return 0;
    }
  }

  /** On lance l'analyse par catégorie et sévérité */
  if (checkIfExist === 100){
    control = sessionStorage.getItem('ma_moulinette_erreur-analyse-repartition');
    // On sort si on a une erreur
    if ( control == 'true') {
      sessionStorage.setItem('ma_moulinette_erreur-analyse-repartition', 'false');
      return 99;
    }

    /** BUG */
    const r1 = await analyseCategory('BUG');
    if (r1 === 0){
      $('#tableau-1').removeClass('hide');
      $('#mon-bo-tableau-1').html(tabCategory);
      phase += 1;
    }

    /** VULNERABILITY */
    const r2 = await analyseCategory('VULNERABILITY');
    if (r2 === 0) {
      $('#tableau-2').removeClass('hide');
      $('#mon-bo-tableau-2').html(tabCategory);
      phase += 1;
    }

    /** CODE_SMELL */
    const r3 = await analyseCategory('CODE_SMELL');
    if ( r3 === 0){
      $('#tableau-3').removeClass('hide');
      $('#mon-bo-tableau-3').html(tabCategory);
      phase += 1;
    }
  }

  /** On met à jour */
  if (phase != 0){
    updateRepartition(phase);
    showMessage('success', 'Mise à jour de la répartition des anomalies par module effectuée.');
    setTimeout(function() { hideMessage();  }, 5000);
  }

  /** On réinitialise le tableau */
  analyseCollecteRepartition = [];
});
