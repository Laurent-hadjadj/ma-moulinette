/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import 'datatables.net-zf/css/dataTables.foundation.min.css';

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
import { modalSafe } from '../../common/safeModal.js';

/** On importe les paramètres serveur */
import { serveur } from '../../common/properties.js';

import { showMessage, hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe le fonction de journalisation */
import { log } from '../../common/log.js';

/** On importe les constantes */
import { http_200, http_400, http_401, http_403, http_404, http_406, http_500, http_503, http_504, deuxMille, content_type, paletteCouleur, matrice, troisMille } from '../../common/constante.js';

/** On importe l'encoder */
import { encode } from '../../common/encode.js';

import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels);

/** Librairie de tirage aléatoire, c'est une chance !, remplace random */
import Chance from 'chance';

/** DataTables pour la t tableau des logger. */
import DataTable from 'datatables.net-zf'; //NOSONAR

/**
 * On importe les méthodes pour :
 * Afficher les données ;
 * Enregistrer les données ;
 */
import { remplissage, afficheHotspotDetails } from './peinture.js';
import { enregistrement } from './enregistrement.js';
import { clean_screen } from './clean-screen.js';

/***************************************************************************/
/***                                                                     ***/
/***                       Variables globales                            ***/
/***                                                                     ***/
/***************************************************************************/
/**
 * [Description for clean]
 *
 * @return void
 *
 * Created at: 01/12/2025 12:50:28 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const clean = async function (type) {
  return new Promise((resolve) => {
    clean_screen(type);
    resolve();
  });
}

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
const shuffle = function (a) {
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
const palette = function () {
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
const dessineMoiUnMouton = function (label, dataset) {
  const nouvellePalette = palette();
  const data = {
    labels: label,
    datasets: [{
      data: dataset, backgroundColor: nouvellePalette,
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
            size: w < 512 ? 12 : 14, weight: 'bold'
          };
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
  if (charts === null) {
    sessionStorage.setItem('ma_moulinette_info', 'Pour éviter une erreur SonarQube !!!');
  }
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
const ditBonjour = function () {
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
const match = function (params, data) {
  /* MODIF 2026-05-17 : $.trim supprimé en jQuery 4. */
  if ((params.term || '').trim() === '') {
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
 * http://{url}/api/secure/liste/projet
 *
 * @return void
 *
 * Created at: 19/12/2022, 22:11:56 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const selectProjet = async function () {
  const options = {
    url: `${serveur()}/api/secure/projet/liste`,
    type: 'POST',
    dataType: 'json',
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const t = await $.ajax(options);
  // 📌 Vérification des erreurs
  if (t.code !== http_200) {
    const hasTrace = !!t.trace;
    const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
    showMessage(t.type, t.message, trace);
    sessionStorage.setItem('ma_moulinette_error', "L'utilisateur n'est pas rattaché à une groupe fonctionnel.");
    return;
  }

  if (t.code === http_200) {
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

/****************************************************/
/*                Collecte des indicateurs          */
/************************************************** */

const enableButton = function (element, aria_label) {
  const $button = $(element);

  $button.removeClass('clicked-true clicked-false disabled-bouton');
  $button.attr('aria-label', aria_label);
  $button.removeAttr('aria-disabled tabindex');
}

const enableLink = function (element, aria_label) {
  const $link = $(element);

  $link.removeClass('disabled-bouton');
  $link.attr('aria-label', aria_label);
  $link.removeAttr('aria-disabled tabindex');
}

const disableButton = function (element, aria_label) {
  const $button = $(element);

  /** Réinitialisation préalable de toutes les classes liées à l'état */
  $button.removeClass('clicked-false clicked-true');

  $button.addClass('disabled-bouton clicked-true');
  $button.attr('aria-disabled', 'true');
  $button.attr('aria-label', aria_label);
  $button.attr('tabindex', '-1');
}

const errorButton = function (element, aria_label) {
  const $button = $(element);

  $button.removeClass('clicked-true').addClass('clicked-false');
  $button.attr('aria-label', aria_label);
}

/**
 * [Description for projetAnalyse]
 * Collecte les informations du projet (projet, version, date)
 * http://{url}/api/secure/collecte/information
 *
 * Phase 01
 * {mavenKey} = clé du projet
 *
 * @param string maven_key
 * @return
 *
 * Created at: 19/12/2022, 22:12:44 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetInformation = async function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/information`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 01');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_200) {
      // 📌 Vérification des erreurs
      log(` - ℹ️ (01) Collecte des informations pour la version : ${t.message.projet_version}`);
      const release = parseInt(t.message.release, 10) || 0;
      const snapshot = parseInt(t.message.snapshot, 10) || 0;
      const autre = parseInt(t.message.autre, 10) || 0;
      const nombre = release + snapshot + autre;
      log(` -     📌 Nombre de version disponible : ${nombre}`);
    } else {
      log(` - ❌ (01) Collecte des informations pour la version en échec.`);
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 01.";
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 01');
    log(` - 🔴 (01) Collecte des informations pour la version en échec.`);
    errorButton($boutonCollecteIndicateur, aria_label);
    return;
  }
};

/**
 * [Description for projetMesure]
 * Collecte des mesures clés du projet (lignes, coverage, duplication, défauts).
 * http://{url}/api/secure/collecte/mesure
 *
 * Phase 02
 * {maven_key} = clé du projet
 *
 * @param string maven_key
 * @return response
 *
 * Created at: 19/12/2022, 22:13:13 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetMesure = async function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/mesure`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 02');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }
    if (t.code === http_200) {
      log(' - ℹ️ (02) Collecte des mesures globales.');
      log(` -     📌 ${t.message.violations} problème(s) trouvé(s).`);
    } else {
      log(` - ❌ (02) Collecte des mesures en échec.`);
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 02.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 02');
    log(` - ❌ (02) Collecte des mesures en échec.`);
    errorButton($boutonCollecteIndicateur, aria_label);
    return;
  }
};

/* (v2.0) projetRating() supprime : les notes sont desormais
   collectees dans mesures en phase (02) via projetMesure(). */

/**
 * [Description for projetOwasp]
 * Récupère le top 10 OWASP et construit la vue
 * Attention une faille peut être comptée deux fois ou plus, cela dépend du tag. Donc il est
 * possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10
 * http://{url}/api/secure/collecte/owasp
 *
 * Phase 04
 * {maven_key} = clé du projet
 *
 * @param string maven_key
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:16:16 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetOwasp = async function (maven_key) {
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/owasp`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 04');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_200) {
      log(' - ℹ️ (03) Collecte des menaces OWASP.');
      if (t.owasp2021 === 'NC') {
        log(` -     📌  Le référentiel OWASP2021 n'est pas supporté par votre serveur SonarQube.`);
      }
    } else {
      log(' - ❌ (03) Collecte des menaces OWASP en échec.');
    }

    if (t.code === http_200 && t.owasp2017 === 0) {
      log(' -     ✅ Bravo aucune faille OWASP 2017 détectée.');
    }
    if (t.code === http_200 && t.owasp2021 === 0) {
      log(' -     ✅ Bravo aucune faille OWASP 2021 détectée.');
    }

    if (t.code === http_200 && t.owasp2017 !== 0) {
      let s = '';
      if (parseInt(t.owasp2017, 10) > 1) { s = 's'; }
      log(` -     ⚠️ J'ai trouvé ${t.owasp2017} faille${s}.`);
    }
    if (t.code === http_200 && t.owasp2021 !== 0 && t.owasp2021 !== 'NC') {
      let s = '';
      if (parseInt(t.owasp2021, 10) > 1) { s = 's'; }
      log(` -     ⚠️ J'ai trouvé ${t.owasp2021} faille${s}.`);
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 04.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 04');
    log(' - ❌ (03) Collecte des menaces OWASP en échec.');
    errorButton($boutonCollecteIndicateur, aria_label);
    return;
  }
};

/**
 * [Description for projetHotspot]
 * Traitement des hotspots de type owasp pour SonarQube 8.9 et >
 * On récupère les Hotspot a examiner. Les clés sont uniques
 * (i.e. on ne se base pas sur les tags).
 * http://{url}/api/secure/collecte/hotspot
 *
 * Phase 05
 * {maven_key} = clé du projet
 *
 * @param string maven_key
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:17:17 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetHotspot = async function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/hotspot`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 05');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_200) {
      log(' - ℹ️ (04) Collecte des menaces potentielles.');
    } else {
      log(' - ❌ (04) Collecte des menaces potentielles en échec.');
    }

    if (t.code === http_200 && t.nombre === 0) {
      log(' -     ✅ Bravo aucune faille potentielle détectée.');
    } else {
      let s = '';
      if (parseInt(t.nombre, 10) > 1) { s = 's'; }
      log(` -     ⚠️ J'ai trouvé ${t.nombre} faille${s} potentielle${s}.`);
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 05.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 05');
    log(' - ❌ (04) Collecte des menaces potentielles en échec.');
    errorButton($boutonCollecteIndicateur, aria_label);
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
 * {maven_key} = clé du projet
 *
 * @param string maven_key
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:13:42 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetAnomalie = async function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/anomalie`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 06');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_200) {
      log(' - ℹ️ (05) Collecte des anomalies.');
      log(` -     📌 ${t.info}`);
    } else {
      log(' - ❌ (05) Collecte des anomalies en échec.');
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 06.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 06');
    log(' - ❌ (05) Collecte des anomalies en échec.');
    errorButton($boutonCollecteIndicateur, aria_label);
    return;
  }
};

/**
 * [Description for projetAnomalieDetails]
 * On récupère pour chaque type (Bug, Vulnerability et Code Smell)
 * http://{url}/collecte/anomalie/detail
 *
 * Phase 07
 * {maven_key} = clé du projet
 *
 * @param string maven_key
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:14:11 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetAnomalieDetails = async function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/anomalie/detail`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 07');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_200) {
      log(' - ℹ️ (06) La fréquence des sévérités par type a été collectée.');
    } else {
      log(` - ❌ (06) Je n'ai pas réussi à collecter les données.`);
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 07.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 07');
    log(` - ❌ (06) Je n'ai pas réussi à collecter les données.`);
    errorButton($boutonCollecteIndicateur, aria_label);
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
 * {maven_key} = clé du projet
 * {owasp} = type d'indicateur OWASP
 *
 * @param string maven_key
 * @param string menace
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:18:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetHotspotOwasp = async function (maven_key, menace) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key, menace };
  const options = {
    url: `${serveur()}/api/secure/collecte/hotspot/owasp`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 08/09');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_200 && t.info === 'effacement') {
      log(' - ℹ️ (07) Les enregistrements ont été supprimé de la table hotspot_owasp.');
      if (t.owasp2021 === 'NC') {
        log(` - 📌 (08) Le référentiel OWASP2021 n'est pas supporté par votre serveur SonarQube.`);
      }
    }

    if (t.code === http_200 && t.owasp2017 === 0 && t.info === 'enregistrement') {
      log(` -     ✅ Bravo aucune faille OWASP2017 ${menace} potentielle détectée.`);
    }
    if (t.code === http_200 && t.owasp2021 === 0 && t.info === 'enregistrement' && t.owasp2021 != 'NC') {
      log(` -     ✅ Bravo aucune faille OWASP2021 ${menace} potentielle détectée.`);
    }

    if (t.code === http_200 && t.owasp2017 !== 0 && t.info === 'enregistrement' && t.owasp2017 != 'NC') {
      let s = '';
      if (parseInt(t.owasp2017, 10) > 1) { s = 's'; }
      log(` -     ⚠️ J'ai trouvé ${t.owasp2017} faille${s} OWASP2017 ${menace} potentielle${s}.`);
    }
    if (t.code === http_200 && t.owasp2021 !== 0 && t.owasp2021 !== 'NC' && t.info === 'enregistrement') {
      let s = '';
      if (parseInt(t.owasp2021, 10) > 1) { s = 's'; }
      log(` -     ⚠️ J'ai trouvé ${t.owasp2021} faille${s} OWASP2021 ${menace} potentielle${s}.`);
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 08/09.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 08/09');
    log(` - ❌ (08/09) Collecte des menaces potentielles Owasp en échec.`);
    errorButton($boutonCollecteIndicateur, aria_label);
    return;
  }
};

/**
 * [Description for projetHotspotOwaspDetails]
 * On enregistre le détails des hotspot owasp
 * http://{url}/api/secure/collecte/hotspot/detail
 *
 * Phase 10
 * {maven_key} = clé du projet
 *
 * @param string maven_key
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:19:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetHotspotOwaspDetails = async function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/hotspot/detail`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 10');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_200) {
      log(` - ℹ️ (09) Collecte des informations détaillées pour les hotspots.`);
      const s = parseInt(t.nombre, 10) > 1 ? 's' : '';
      log(` -     ✅ On a trouvé ${t.nombre} description${s}.`);
    } else if (t.code === http_406) {
      log(` -     📌 Aucune information n'est disponible pour les hotspots.`);
    } else {
      log(` - ❌ (09) Collecte des informations détaillées pour les hotspots en échec.`);
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 10.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 10');
    log(` - ❌ (09) Collecte des informations détaillées pour les hotspots en échec.`);
    errorButton($boutonCollecteIndicateur, aria_label);
    return;
  }
};

/**
 * [Description for projetNoSonar]
 * On récupère la liste des exclusions de code
 * http://{url}/api/secure/collete/nosonar
 *
 * Phase 11
 * {maven_key} = clé du projet
 *
 * @param string maven_key
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:19:44 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetNoSonar = async function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/nosonar`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);

    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 11');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_200) {
      log(` - ℹ️ (10) Collecte des annotations NoSonar/SuppressWarning.`);
    } else {
      log(` - ❌ (10) Collecte des annotations NoSonar/SuppressWarning en échec.`);
    }

    if (t.code === http_200 && t.nombre !== 0) {
      const s = t.nombre > 1 ? 's' : '';
      log(` -     ⚠️ J'ai trouvé ${t.nombre} exclusion${s}.`);
      log(`           📌 NoSonar Java   : ${t.historique.java_no_sonar}`);
      log(`           📌 NoSonar Python : ${t.historique.python_no_sonar}`);
      log(`           📌 NoSonar PHP    : ${t.historique.php_no_sonar}`);
      log(`           📌 @SuppressWarnings : ${t.historique.suppress_warning}`);
      log(`           📌 Checkstyle (S1315) : ${t.historique.check_style}`);
      log(`           📌 PMD (S1310)        : ${t.historique.no_pmd}`);
    } else {
      log(` -     ✅ Bravo !!! ${t.nombre} exclusion NoSonar trouvée.`);
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 11.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 11');
    log(` - ❌ (10) Collecte des annotations NoSonar/SuppressWarning en échec.`);
    errorButton($boutonCollecteIndicateur, aria_label);
    return;
  }
};

/**
 * [Description for projetTodo]
 * On récupère la liste des t_odo
 * http://{url}/api/secure/collecte/t_odo
 *
 * Phase 12
 * {maven_key} = clé du projet
 *
 * @param string maven_key
 *
 * @return response
 *
 * Created at: 10/04/2023, 15:11:30 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetTodo = async function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/todo`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 12');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_200) {
      log(` - ℹ️ (11) Collecte des commentaires Todo.`);
    } else {
      log(` - ❌ (11) Collecte des commentaires Todo.`);
    }

    if (t.code === http_200 && t.nombre !== 0) {
      let s = '';
      if (t.nombre > 1) { s = 's'; }
      log(` -     ⚠️ J'ai trouvé ${t.nombre} ToDo${s} à vérifier sur 10 000 possibles.`);
    } else {
      log(` -     ✅ Bravo !!! ${t.nombre} ToDo trouvé.`);
    }
    if (t.nombre == 10000) {
      log(` -     🤬 Le nombre de Todo présent dans l'application dépasse les 10 000.`);
    }

  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 12.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 12');
    log(` - ❌ (11) Collecte des commentaires Todo.`);
    errorButton($boutonCollecteIndicateur, aria_label);
    return;
  }
};

/**
 * [Description for projetLogger]
 * On récupère la liste des Logger
 * http://{url}/api/secure/collecte/logger
 *
 * Phase 13
 * {maven_key} = clé du projet
 *
 * @param string maven_key
 *
 * @return response
 *
 * Created at: 11/07/2024, 21:40:10 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const projetLogger = async function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    return;
  }

  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/collecte/logger`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'La collecte est interrompue.';

  try {
    const t = await $.ajax(options);
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 13');
      errorButton($boutonCollecteIndicateur, aria_label);
      return;
    }

    if (t.code === http_404) {
      log(` - 📌 (12) La collecte des LOGGERS n'a pas été activée.`);
    }

    if (t.code === http_200) {
      log(` - ℹ️ (12) Collecte des LOGGERS pour l'application JAVA.`);
      const a = Number(t.data.logger_info);
      const b = Number(t.data.logger_warn);
      const c = Number(t.data.logger_error);
      const d = Number(t.data.logger_debug);
      const nombre = a + b + c + d;
      if (nombre === 0) {
        log(` -     ⚠️ Je n'ai pas trouvé de Logger.`);
      } else {
        const s = nombre > 1 ? 's' : '';
        log(` -     ✅ J'ai trouvé ${nombre} Logger${s}.`);
      }
      /* MODIF 2026-05-15 : le breakdown level × framework
        est récupéré désormais par peinture.js (endpoint dédié
        /api/secure/peinture/projet/logger-details) et stocké sur
        #js-logger-info.dataset.loggerBreakdown — pattern aligné sur les
        autres compteurs logger_xxx. Plus de sessionStorage ici. */
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la phase 13.";
    showMessage('alert', message, trace);
    sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 13');
    log(` - ❌ (12) Collecte des Loggers en échec.`);
    errorButton($boutonCollecteIndicateur, aria_label);
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
const finCollecte = function (maven_key) {
  /** On vérifie s'il n'y a pas d'erreur lors du traitement */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  const $boutonCollecteIndicateur = $('.js-analyse');
  const aria_label = 'Lancer la collecte des indicateurs SonarQube';

  if (collecte === 'Tout va bien!') {
    log(` - ℹ️ (13) La collecte des données est terminée.`);
    showMessage('success', `<strong>${collecte}</strong> Le processus de collecte a été réalisé avec success pour le projet ${maven_key} !`);
    setTimeout(() => { hideMessage(); }, troisMille);
    enableButton($boutonCollecteIndicateur, aria_label);
  } else {
    sessionStorage.setItem('ma_moulinette_info', 'On ne devrait pas tomber dans cette branche.')
  }

}

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
  /** si la selection est null alors on ne fait rien */
  if ($('select[name="projet"]').val() === null) { return; }
  const normalizeSelectProjet = $('select[name="projet"]').val().trim();
  $('#select-result').html(`<strong>${normalizeSelectProjet}</strong>`);

  /** On enregistre la clé maven dans le session storage (utile pour la page Owasp) */
  sessionStorage.setItem('ma_moulinette_projet', normalizeSelectProjet);

  /** On supprime la clé de collecte */
  sessionStorage.setItem('ma_moulinette_collecte', 'Tout va bien!');

  /* On regarde si le projet est en favori */
  const data = { maven_key: $('#select-result').text().trim() };
  const options = {
    url: `${serveur()}/api/secure/favori/check`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  $.ajax(options).then(t => {
    // 📌 Vérification des erreurs
    const errorCodes = [http_400, http_401, http_403, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
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

  /* On débloque les boutons si besoin. */

  /** Pas la peine de réactivé tous les bouton si le bouton est déjà enable */
  const $boutonCollecteIndicateur = $('.js-analyse');
  const $boutonAfficheIndicateur = $('.js-analyse-result');
  const $boutonEnregistreIndicateur = $('.js-enregistrement');

  const $boutonOuvreRepartition = $('.js-repartition-module');
  const $boutonOuvreOwasp = $('.js-analyse-owasp');
  /* MODIF 2026-05-17 */
  const $boutonOuvreCleanCode = $('.js-analyse-clean-code');
  const $boutonOuvreCosui = $('.js-cosui');
  const $boutonOuvreSuivi = $('.js-tableau-de-bord');

  if (document.getElementById("bouton-collecte-indicateur") && !$boutonCollecteIndicateur.hasClass('disabled-bouton')) {
      $boutonCollecteIndicateur.removeClass('clicked-true clicked-false');
      return;
  }


  let aria_label;

if (document.getElementById("bouton-collecte-indicateur")){
  aria_label = 'Lancer la collecte pour ce projet.';
  enableButton($boutonCollecteIndicateur, aria_label);
}
  aria_label = 'Affiche les données collectées.';
  enableButton($boutonAfficheIndicateur, aria_label);

if (document.getElementById("bouton-collecte-indicateur")){
  aria_label = 'Enregistre les données collectées.';
  enableButton($boutonEnregistreIndicateur, aria_label);
}

if (document.getElementById("bouton-collecte-indicateur")){
  aria_label = 'Ouvre la page de calcul de la répartition.';
  enableLink($boutonOuvreRepartition, aria_label);
}

  aria_label = 'Ouvre la page COSUI.';
  enableLink($boutonOuvreCosui, aria_label);

  aria_label = 'Ouvre la page de suivi du projet.';
  enableLink($boutonOuvreSuivi, aria_label);

  aria_label = 'Ouvre la page de suivi du projet.';
  enableLink($boutonOuvreOwasp, aria_label);

  /* MODIF 2026-05-17 */
  aria_label = 'Ouvre la page Clean Code Taxonomy.';
  enableButton($boutonOuvreCleanCode, aria_label);
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
  const $boutonCollecteIndicateur = $('.js-analyse');

  if (!roles.includes('ROLE_COLLECTE') && !roles.includes('ROLE_BATCH') && !roles.includes('ROLE_GESTIONNAIRE')) {
    showMessage('alert', 'Vous devez avoir au moins le rôle COLLECTE pour lancer la collecte des données.');
    return;
  }

  /* On récupère la clé du projet qui est affichée. */
  const idProject = $('#select-result').text().trim();
  if (idProject === 'N.C') {
    log(' - ❌ Vous devez choisir un projet !!!');
    return;
  }

  /* On clean les données */
  const bob = clean('collecte');

  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (!collecte || collecte != 'Tout va bien!') {
    log(` - ❌ Une erreur s'est produite sur le projet ${idProject}.`);
    log(`      Vous ne pouvez pas relancer d'analyse pour ce projet.`);
    showMessage('primary', `<strong>${collecte}</strong> Le processus de collecte a été interrompu !<br>   💡Choisissez un autre projet.`);
  }

  log(' - ℹ️ On lance la collecte...');
  disableButton($boutonCollecteIndicateur, 'Le projet est en cours de collecte.');
  $boutonCollecteIndicateur.addClass('clicked-true');

  async function fnAsync() {
    /* Analyse du projet */
    await projetInformation(idProject);           /*(01)*/
    await projetMesure(idProject);                /*(02)*/

    /* (v2.0) Les notes (reliability/security/sqale) sont désormais
       collectées dans mesures en phase (02). Plus de phase (03). */

    /* Analyse Sécurité et Owasp. */
    await projetOwasp(idProject);                 /*(03)*/
    await projetHotspot(idProject);               /*(04)*/

    /* On récupère les infos sur les anomalies*/
    await projetAnomalie(idProject);              /*(05)*/

    /* On récupère le détails sur les anomalies*/
    await projetAnomalieDetails(idProject);       /*(06)*/

    /* On efface les traces :)*/
    await projetHotspotOwasp(idProject, 'a0');    /*(07)*/

    /* On enregistre les résultats*/
    await projetHotspotOwasp(idProject, 'a1');    /*(08)*/
    await projetHotspotOwasp(idProject, 'a2');    /*(08)*/
    await projetHotspotOwasp(idProject, 'a3');    /*(08)*/
    await projetHotspotOwasp(idProject, 'a4');    /*(08)*/
    await projetHotspotOwasp(idProject, 'a5');    /*(08)*/
    await projetHotspotOwasp(idProject, 'a6');    /*(08)*/
    await projetHotspotOwasp(idProject, 'a7');    /*(08)*/
    await projetHotspotOwasp(idProject, 'a8');    /*(08)*/
    await projetHotspotOwasp(idProject, 'a9');    /*(08)*/
    await projetHotspotOwasp(idProject, 'a10');   /*(08)*/

    /* On enregistre le détails de chaque hotspot owasp. */
    await projetHotspotOwaspDetails(idProject);   /*(09)*/

    /* Récupération des signalements noSonar et SuppressWarning. */
    await projetNoSonar(idProject);               /*(10)*/

    /* Récupération des signalements To do (TS, JAVA, XML). */
    await projetTodo(idProject);                  /*(11)*/

    /* Récupération des signalements Logger (logger_info, logger_warn, logger_error_, logger_debug). */
    await projetLogger(idProject);                /*(12)*/

    /* Renvoie le statut de fin */
    finCollecte(idProject);
  }

  /* On appelle la fonction de Collecte des indicateurs */
  fnAsync();
});

/******************************************************/
/***           Affiche les indicateurs                */
/******************************************************/

/**
 * description
 * On passe à la peinture
 */
$('.js-affiche-result').on('click', async () => {
  /* On clean les données */

  const bob = clean('peinture');

  /* On récupère la clé du projet. */
  const maven_key = $('#select-result').text().trim();

  /** On regarde si tout vas bien ! */
  const collecte = sessionStorage.getItem('ma_moulinette_collecte');
  if (collecte === undefined || collecte != 'Tout va bien!') {
    log(` - ❌ Une erreur s'est produite sur le projet ${maven_key}.`);
    log(`      Vous ne pouvez pas afficher les données collectées pour ce projet.`);
    showMessage('primary', `Le processus d'affichage des données a été interrompu !<br>Choisissez un autre projet.`);
    sessionStorage.setItem('ma_moulinette_peinture', `La collecte du projet a échoué.`);
    return;
  };

  /** On appelle la fonction de remplissage */
  let p1 = 1, p2 = 1;
  p1 = await remplissage(maven_key);
  if (p1 == 0) {
    p2 = await afficheHotspotDetails(maven_key);
  }

  /** Si le remplissage et menace n'ont pas d'erreur on affiche un message */
  if (p1 == 0 && p2 == 0) {
    showMessage('primary', "L'affichage des informations pour le projet est terminée.")
    setTimeout(() => { hideMessage() }, troisMille);
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
    showMessage('alert', `Vous devez avoir au moins le rôle COLLECTE pour lancer la commande d'enregistrement (Erreur 403).`);
    return;
  }

  /* On récupère la clé du projet. */
  const maven_key = $('#select-result').text().trim();
  // On renvoie la maven_key en miniscule pour éviter les problèmes de casse dans le batch d'enregistrement.
  enregistrement(maven_key);
});

/******************************************************/
/***           Ouvre la page de SUIVI                */
/******************************************************/

/**
 * description
 * On génère la route et on ouvre la page des tableau de suivi
 */
$('.js-tableau-de-bord').on('click', () => {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    const maven_key = $('#select-result').text().trim();
    window.location.href = `${serveur()}/suivi/set?maven_key=${maven_key}`;
  } else {
    log(' - ❌ ERROR - [SUIVI] - Vous devez choisir un projet dans la liste !! !');
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
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    const maven_key = $('#select-result').text().trim();

    /** on créé un hash avec la méthode reduce() comme clé de salt */
    const salt = maven_key.split('').reduce((hash, char) => {
      return char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
    }, 0);

    /** on créé un token pour encoder les paramètres */
    const param = `${salt}|${maven_key}`;
    const a = encode(btoa(param));
    location.href = `${serveur()}/projet/cosui?token=${a}`;
  } else {
    log(' - ❌ ERROR - [COSUI] - Vous devez choisir un projet dans la liste !! !');
  }
});

/******************************************************/
/***           Ouvre la page OWASP                    */
/******************************************************/

/**
 * description
 * On génère la route et on ouvre la page de répartition des indicateurs par module
 */
$('.js-analyse-owasp').on('click', () => {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    const maven_key = $('#select-result').text().trim();

    /* on écrase la clé maven au cas ou */
    sessionStorage.setItem('ma_moulinette_projet', maven_key);

    /** on créé un hash avec la méthode reduce() comme clé de salt */
    const salt = maven_key.split('').reduce((hash, char) => {
      return char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
    }, 0);

    /** on créé un token pour encoder les paramètres */
    const param = `${salt}|${maven_key}`;
    const a = encode(btoa(param));
    location.href = `${serveur()}/owasp?token=${a}`;
  } else {
    log(' - ❌ ERROR - [OWASP] - Vous devez choisir un projet dans la liste !! !');
  }

});

/* MODIF 2026-05-17 : navigation vers la page Clean Code Taxonomy */
$('.js-analyse-clean-code').on('click', () => {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    const maven_key = $('#select-result').text().trim();
    sessionStorage.setItem('ma_moulinette_projet', maven_key);
    const salt = maven_key.split('').reduce((hash, char) => {
      return char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
    }, 0);
    const param = `${salt}|${maven_key}`;
    const a = encode(btoa(param));
    location.href = `${serveur()}/clean-code?token=${a}`;
  } else {
    log(' - ❌ ERROR - [Clean Code] - Vous devez choisir un projet dans la liste !!');
  }
});

/**
 * description
 * On génère la route et on ouvre la page de répartition des indicateurs par module
 */
$('.js-repartition-module').on('click', () => {
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    const maven_key = $('#select-result').text().trim();
    /** on créé un hash avec la méthode reduce() comme clé de salt */
    const salt = maven_key.split('').reduce((hash, char) => {
      return char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
    }, 0);

    /** on créé un token pour encoder les paramètres */
    const param = `${salt}|${maven_key}`;
    const a = encode(btoa(param));
    location.href = `${serveur()}/repartition?token=${a}`;
  } else {
    log(' - ❌ ERROR - [Répartition] Vous devez choisir un projet dans la liste !! !');
  }
});

/******************************************************/
/***                  Les modales                     */
/******************************************************/
/**
 * description
 * On affiche la liste des types d'anomalies par sévérité.
 */
$('#js-affiche-severity').on('click', function () {
    if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
      showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
    return;
  }
    modalSafe.open('#modal-affiche-severity');
});
$('#bouton-fermer-liste-severity').on('click', function () {
  modalSafe.close('#modal-affiche-severity');
});

/**
 * description
 * On affiche la liste des violations par sévérité.
 */
$('#js-affiche-violations').on('click', function () {
    if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
      showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
    return;
  }
    modalSafe.open('#modal-affiche-violations-par-severity');
});
$('#bouton-fermer-liste-violations').on('click', function () {
  modalSafe.close('#modal-affiche-violations-par-severity');
});

/**
 * description
 * On affiche la liste des hotspots
 */
$('#js-affiche-modal-menace-potentielle').on('click', function () {
  if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
    showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
    return;
  }
  modalSafe.open('#modal-menace-potentielle');
});
$('#bouton-fermer-menace-potentielle').on('click', function () {
  modalSafe.close('#modal-menace-potentielle');
});

/**
 * description
 * Événement : Ouvre la fenêtre modale de la distribution de la dette technique.
 */
$('#js-affiche-detail-dette').on('click', () => {
  if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
    showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
    return;
  }
      modalSafe.open('#modal-dette-technique');
});
$('#bouton-fermer-dette-technique').on('click', function () {
  modalSafe.close('#modal-dette-technique');
});

/**
 * description
 * On affiche la liste des tags to do par langage.
 */
$('#js-affiche-liste-todo').on('click', function () {
  if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
    showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
    return;
  }
  modalSafe.open('#modal-affiche-liste-todo');
});
$('#bouton-fermer-liste-todo').on('click', function () {
  modalSafe.close('#modal-affiche-liste-todo');
});

/**
 * MODIF 2026-05-06 :
 * Ouverture de la modale Detail NoSonar / @SuppressWarnings / NoPMD / Checkstyle.
 */
$('#js-affiche-liste-nosonar').on('click', function () {
  if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
    showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
    return;
  }
    modalSafe.open('#modal-affiche-liste-nosonar');
});
$('#bouton-fermer-liste-nosonar').on('click', function () {
  modalSafe.close('#modal-affiche-liste-nosonar');
});

/**
 * description
 * On affiche la liste des logger par méthode
 */
$('#js-affiche-logger').on('click', function () {
  if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
    showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
    return;
  }
  modalSafe.open('#modal-affiche-liste-logger');
});
$('#bouton-fermer-liste-logger').on('click', function () {
    modalSafe.close('#modal-affiche-liste-logger');
});


/* MODIF 2026-05-25  :
 * Modale Détail Logger (matrix level × framework + liste fichiers paginée) +
 * modale Graphique Logger (Chart.js bar empilé).
 * DataTables pré-initialisé vide à DOMContentLoaded (autoWidth:false),
 * données injectées via API DataTables (.clear/.rows.add/.draw) avant ouverture.
 * Pattern identique DC dashboard — pas de recalcul largeur sur éléments cachés. */
let loggerBarChartInstance = null;
let loggerDetailDT = null;

const loggerLevelLabels = ['info', 'warn', 'error', 'debug'];
const loggerFrameworkPalette = [
  { bg: 'rgba(54, 162, 235, 0.7)', border: 'rgba(54, 162, 235, 1)' },
  { bg: 'rgba(255, 159, 64, 0.7)', border: 'rgba(255, 159, 64, 1)' },
  { bg: 'rgba(153, 102, 255, 0.7)', border: 'rgba(153, 102, 255, 1)' },
  { bg: 'rgba(75, 192, 192, 0.7)', border: 'rgba(75, 192, 192, 1)' },
  { bg: 'rgba(255, 99, 132, 0.7)', border: 'rgba(255, 99, 132, 1)' },
  { bg: 'rgba(201, 203, 207, 0.7)', border: 'rgba(201, 203, 207, 1)' }
];

const DT_LANG_FR_LOGGER = {
  search: 'Rechercher :', lengthMenu: 'Afficher _MENU_ lignes',
  info: 'Lignes _START_ à _END_ sur _TOTAL_', infoEmpty: 'Aucune ligne disponible',
  infoFiltered: '(filtré depuis _MAX_ lignes)', zeroRecords: 'Aucun résultat',
  emptyTable: 'Aucune donnée',
  paginate: { first: '«', previous: '‹', next: '›', last: '»' },
};

const readLoggerBreakdown = function () {
  const el = document.getElementById('js-affiche-logger-detail');
  if (!el || !el.dataset || !el.dataset.loggerBreakdown) { return null; }
  try {
    return JSON.parse(el.dataset.loggerBreakdown);
  } catch (e) {
    log(' - 🔴 [Logger Detail] JSON invalide sur dataset.loggerBreakdown.');
    return null;
  }
};

const collectLoggerFrameworks = function (breakdown) {
  const set = new Set();
  for (const level of loggerLevelLabels) {
    const byFw = breakdown[level] || {};
    for (const fw of Object.keys(byFw)) { set.add(fw); }
  }
  return Array.from(set).sort();
};

/* Pré-init DataTable vide au chargement du module.
 * Le module ES est différé : le DOM est prêt à ce stade.
 * autoWidth:false = pas de calculs de largeur sur l'élément encore caché. */
{
  const _tableEl = document.getElementById('js-tableau-logger-detail');
  if (_tableEl) {
    loggerDetailDT = new DataTable(_tableEl, {
      data: [],
      autoWidth: false,
      pageLength: 10,
      lengthMenu: [5, 10, 15, 20, 25],
      order: [[0, 'asc']],
      language: DT_LANG_FR_LOGGER,
    });
  }
}

/* Ré-ajustement colonnes quand la modale devient visible. */
//$(document).on('open.zf.reveal', '#modal-affiche-logger-detail', function () {
//  if (loggerDetailDT) { loggerDetailDT.columns.adjust(); }
//});

/* Ouverture modale Détail.
 * Délégation sur document : sûr même si l'élément est re-rendu après init. */
$(document).on('click', '#js-affiche-logger-detail', function () {
  if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
      showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
      return;
    }

  // 1. Matrice level × framework depuis le dataset
  const breakdown = readLoggerBreakdown();
  const $matrix = $('#js-tableau-logger-detail-matrix');
  $matrix.empty();
  if (!breakdown) {
    $matrix.append('<tr><td colspan="3" class="text-center">Aucun détail disponible. Relancez une collecte ou afficher les derniers résultats.</td></tr>');
  } else {
    let totalRows = 0;
    for (const level of loggerLevelLabels) {
      const byFw = breakdown[level] || {};
      const frameworks = Object.keys(byFw).sort();
      for (const fw of frameworks) {
        const nb = byFw[fw];
        if (nb > 0) {
          $matrix.append(
            `<tr><td><strong>${level}</strong></td>` +
            `<td class="text-center">${$('<div/>').text(fw).html()}</td>` +
            `<td class="text-right stat">${nb}</td></tr>`
          );
          totalRows += 1;
        }
      }
    }
    if (totalRows === 0) {
      $matrix.append('<tr><td colspan="3" class="text-center">Aucun logger détecté.</td></tr>');
    }
  }

  // 2. Injecter les lignes dans le DataTable via l'API (pas de innerHTML)
  const detailEl = document.getElementById('js-affiche-logger-detail');
  let details = [];
  if (detailEl && detailEl.dataset && detailEl.dataset.loggerDetails) {
    try {
      details = JSON.parse(detailEl.dataset.loggerDetails);
    } catch (e) {
      log(' - 🔴 [Logger Detail] JSON invalide sur dataset.loggerDetails.');
    }
  }

  if (loggerDetailDT) {
    loggerDetailDT.clear().rows.add(
      details.slice(0, 50).map(d => [
        d.file_path || d.file_name || '—',
        d.line_number != null ? d.line_number : '—',
        d.level     || '—',
        d.framework || '—',
      ])
    ).draw(false);
  }

  // 3. Ouvrir la modale
  modalSafe.open('#modal-affiche-logger-detail');
});

$('#bouton-fermer-logger-detail').on('click', function () {
  modalSafe.close('#modal-affiche-logger-detail');
});

/* Ouverture modale Graphique : bar empilé level x framework (Chart.js).
  Le canvas est ré-instancié à chaque ouverture (destroy puis new) pour
   éviter les fuites Chart.js entre projets. */
$('#js-affiche-graphique-logger').on('click', function () {
  if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
    showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
    return;
  }
  const breakdown = readLoggerBreakdown();
  if (!breakdown) {
    showMessage('warning', 'Aucun détail logger disponible. Relancez une collecte.', null);
    return;
  }

  const frameworks = collectLoggerFrameworks(breakdown);
  const datasets = frameworks.map((fw, idx) => {
    const palette = loggerFrameworkPalette[idx % loggerFrameworkPalette.length];
    return {
      label: fw,
      data: loggerLevelLabels.map(level => (breakdown[level] && breakdown[level][fw]) || 0),
      backgroundColor: palette.bg,
      borderColor: palette.border,
      borderWidth: 1
    };
  });

  if (loggerBarChartInstance) {
    loggerBarChartInstance.destroy();
    loggerBarChartInstance = null;
  }

  modalSafe.open('#modal-affiche-logger-graphique');
  /* tick suivant : le canvas doit être visible pour que Chart.js mesure sa taille */
  setTimeout(() => {
    const canvas = document.getElementById('logger-bar-chart');
    if (!canvas) { return; }
    loggerBarChartInstance = new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: { labels: loggerLevelLabels, datasets: datasets },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top', labels: { font: { size: 14 } } },
          datalabels: {
            display: ctx => ctx.dataset.data[ctx.dataIndex] > 0,
            color: '#fff',
            font: { weight: 'bold' }
          },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y}`
            }
          }
        },
        scales: {
          x: { stacked: true },
          y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }, 50);
});

$('#bouton-fermer-logger-graphique').on('click', function () {
  modalSafe.close('#modal-affiche-logger-graphique');
});

/**
 * description
 * Événement : on marque le projet comme favori.
 */
$('.favori-svg').on('click', () => {

  /* On regarde si le projet est déjà en favori. */
  if ($('select[name="projet"]').val() !== '' && $('select[name="projet"]').val() !== 'TheID') {
    if ($('.favori-svg').hasClass('favori-svg-select')) {
      $('.favori-svg').removeClass('favori-svg-select');
    } else {
      $('.favori-svg').addClass('favori-svg-select');
    }

    const data = { maven_key: $('#select-result').text().trim() };
    const options = {
      url: `${serveur()}/api/secure/favori`,
      type: 'POST',
      dataType: 'json',
      data: JSON.stringify(data),
      contentType: content_type,
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app'
      },
    };

    $.ajax(options).then(t => {
      // 📌 Vérification des erreurs
      const errorCodes = [http_400, http_401, http_403, http_500, http_503, http_504];
      if (errorCodes.includes(t.code)) {
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
$('#js-graphique-version').on('click', () => {
  if ($('select[name="projet"]').val() === '' || $('select[name="projet"]').val() === 'TheID') {
    showMessage('warning', 'Aucune donnée disponible. Relancez une collecte ou afficher les derniers résultats.', null);
    return;
  }

  let label, dataset;
  const version = document.getElementById('js-version-autre');

  modalSafe.open('#modal-autre-version');
  (version.dataset.label === undefined) ? { label, dataset } = 0 : { label, dataset } = version.dataset;
  const canvasId = 'graphique-autre-version';
  const canvas = document.getElementById(canvasId);
  const emptyMessage = canvas?.nextElementSibling;
  const hasNoData = !label?.length || !dataset?.length;

  // Cas sans données : on affiche le message HTML
  if (hasNoData) {
    if (canvas) canvas.style.display = 'none';
    if (emptyMessage) emptyMessage.style.display = 'flex';
    return;
  }

  // Cas avec données : on cache le message HTML
  if (canvas) canvas.style.display = 'block';
  if (emptyMessage) emptyMessage.style.display = 'none';

  // Détruit l'ancien graphique si existant
  const oldChart = Chart.getChart(canvasId);
  if (oldChart) oldChart.destroy();

  dessineMoiUnMouton(JSON.parse(label), JSON.parse(dataset));
});

$('#bouton-fermer-autre-version-graphique').on('click', () => {
  modalSafe.close('#modal-autre-version');
});

/******************************************************/
/***                  Main                            */
/******************************************************/

/** On récupère le dernier projet sélectionné */
const leDernierBidule = sessionStorage.getItem('ma_moulinette_projet');

if (leDernierBidule !== null) {
  /* On récupère le nom du projet */
  const b = leDernierBidule.split(':');
  const nom = b[1];
  const $newOption = $("<option selected='selected'></option>").val(leDernierBidule).text(nom);

  /* On  active le projet */
  $('select[name="projet"]').append($newOption).trigger('change');
}
