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

import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/common/select2.min.css';
import '../../../styles/mon-application/suivi.css';

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

/* MODIF 2026-05-06 : retrait des imports jsPDF / html2canvas
 * et du listener .lien-imprimer-pdf (génère maintenant cote serveur via Dompdf
 * sur la route /suivi/rapport/pdf). Le bouton est devenu un <a> dans _menu_outils.html.twig. */

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import 'chartjs-adapter-date-fns';
import { parse, format } from 'date-fns';
import { fr } from 'date-fns/locale';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels);

/** On importe les constantes */
import { http_200, http_201, http_400, http_401, http_403, http_404, http_500, http_503, http_504, chartColors, zero, un, deux, soixante, cent, dateOptions, content_type } from '../../common/constante.js';
import { modalSafe } from '../../common/safeModal';

/**
 * [Description for dessineMoiUnMouton]
 * Affiche le graphique des sources *
 * @param array labels
 * @param array data1
 * @param array data2
 * @param array data3
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 22:58:54 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */

const dessineMoiUnMouton= function( labels, data1, data2, data3) {
  // Convertir les labels en objets Date
  const convertedLabels = labels.map(date => new Date(date));
  const data = {
    labels: convertedLabels,
    datasets: [
      {
        label: 'Bug',
        pointBorderColor: chartColors.orange,
        pointBackgroundColor: chartColors.orange,
        borderWidth: 2,
        radius: 0,
        data: data1,
        fill: true,
        borderColor: chartColors.orange,
        backgroundColor: chartColors.orangeOpacity,
        tension: 0.2,
        pointRadius: 4
      },
      {
        label: 'Vulnérabilité',
        pointBorderColor: '#C64444',
        pointBackgroundColor: '#C64444',
        borderWidth: 2,
        radius: 0,
        data: data2,
        fill: true,
        borderColor: chartColors.rouge,
        backgroundColor: chartColors.rougeOpacity,
        tension: 0.2,
        pointRadius: 4
      },
      {
        label: 'Mauvaise pratique',
        pointBorderColor: chartColors.bleu,
        pointBackgroundColor: chartColors.bleu,
        borderWidth: 2,
        radius: 0,
        data: data3,
        fill: true,
        borderColor: chartColors.bleu,
        backgroundColor: chartColors.bleuOpacity,
        tension: 0.2,
        pointRadius: 4,
      }
    ]
  };

  const options = {
    animations: { radius: { duration: 400, easing: 'linear' } },
    aspectRatio: 3,
    maintainAspectRatio: true,
    responsive: true,
    layout: {
      padding: { left: 64, right: 32, top: 32, bottom: 16}
    },
    scales: {
      x: {
        type: 'timeseries',
        time: {
          unit: 'day',
          unitStepSize: 1,
          tooltipFormat: 'll',
          displayFormats: { 'day': 'dd/MM/yyyy' }
        },
        ticks: {
          callback: function(value, index, values) {
            // Utilise date-fns pour formater la date en français
            const formattedDate = format(new Date(value), 'dd LLL yyyy', { locale: fr });
            return formattedDate;  // Retourner la date formatée
          }
        },
        display: true
      },
      y: {
        display: true,
        type: 'logarithmic',
        position: 'right',
        title: { display: true, text: 'Violations', color: '#00445b' },
        ticks: { color: '#00445b' }
      }
    },
    plugins: {
      tooltip: { enabled: false },
      legend: { position: 'bottom' },
      datalabels: {
        display: true,
        align: 'end',
        anchor: 'right',
        color: '#000',
        font: function(context) {
          /** Ajuste la taille de la police en fonction de la largeur du media. */
          const w = context.chart.width;
          return { size: w < 512 ? 10 : 14, weight: 'bold' };
        }
      }
    }
  };

  const chartStatus = Chart.getChart('graphique-anomalie');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-anomalie').getContext('2d');
  const charts = new Chart(ctx, { type: 'line', data, options });
  if (charts === null) {
    sessionStorage.setItem('ma_moulinette_info', 'chartJs is null');
  }
};

/* On récupère les dataset */
const dataAttribut1 = document.getElementById('graphique-anomalie-data1');
const dataAttribut2 = document.getElementById('graphique-anomalie-data2');
const dataAttribut3 = document.getElementById('graphique-anomalie-data3');
const dataAttribut4 = document.getElementById('graphique-anomalie-labels');
const _data1= dataAttribut1.dataset.data1;
const _data2= dataAttribut2.dataset.data2;
const _data3= dataAttribut3.dataset.data3;
const _labels= dataAttribut4.dataset.label;

dessineMoiUnMouton(
  Object.values(JSON.parse(_labels)),
  Object.values(JSON.parse(_data1)),
  Object.values(JSON.parse(_data2)),
  Object.values(JSON.parse(_data3))
);

/* MODIF 2026-05-06 : bar chart empile pour
 * la distribution langage par version. Datasets prepares cote serveur par
 * LanguageDistributionService::buildDistributionMatrix (1 dataset par langage,
 * une valeur par version). */
const langueLabelsEl = document.getElementById('graphique-langage-labels');
const langueDatasetsEl = document.getElementById('graphique-langage-datasets');
const langueCanvas = document.getElementById('graphique-langage');

if (langueLabelsEl && langueDatasetsEl && langueCanvas) {
  try {
    const langueLabels = JSON.parse(langueLabelsEl.dataset.labels);
    const langueDatasets = JSON.parse(langueDatasetsEl.dataset.datasets);

    if (Array.isArray(langueDatasets) && langueDatasets.length > 0) {
      const ctx = langueCanvas.getContext('2d');
      const previous = Chart.getChart('graphique-langage');
      if (previous !== undefined) {
        previous.destroy();
      }
      // eslint-disable-next-line no-new
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: langueLabels,
          datasets: langueDatasets
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: { stacked: true, title: { display: true, text: 'Version', color: '#00445b' } },
            y: { stacked: true, title: { display: true, text: 'Lignes de code', color: '#00445b' }, beginAtZero: true }
          },
          plugins: {
            legend: { position: 'bottom' },
            datalabels: { display: false },
            tooltip: { enabled: true }
          }
        }
      });
    }
  } catch (e) {
    sessionStorage.setItem('ma_moulinette_info', 'graphique-langage parse error: ' + e.message);
  }
}

/**
 * Drapeau de validité des donnees chargées pour la modale "ajouter une analyse".
 * Mis a true uniquement si getVersion a abouti (code 200 + data).
 * Empêche l'enregistrement si l'utilisateur n'a pas sélectionné / si la version est invalide.
 */
let donneesAjoutValides = false;

/**
 * Snapshot des dernières métriques renvoyées par /api/secure/get/version
 * (sortie de BuildMapHistoryService::metricsRebuild côté backend, ~50 fields
 * typés). Mémorisé pour round-trip vers /api/secure/suivi/mise-a-jour sans
 * dupliquer chaque champ en dataset.*. Reset à chaque ouverture de modale.
 */
let dernieresMetriques = {};

/**
 * [Description for versionSonarQubeListe]
 * Charge la liste des versions disponibles depuis SonarQube pour la clé maven donnee.
 * POST /api/secure/liste/v2.0/version
 *
 * @param string maven_key
 * @return object|null
 */
const versionSonarQubeListe = async function (maven_key) {
  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/liste/v2.0/version`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    }
  };

  let t;
  try {
    t = await $.ajax(options);

    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return null;
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors du chargement des versions SonarQube (Erreur 500).";
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_critical', trace);
    return null;
  }

  return t;
};

/**
 * description
 * On affiche la liste des projets
 */
$('.js-ajouter-analyse').on('click', async function () {
  const mavenKey = $('#titre-js-nom').data('maven');

  /** Si la clé mavenKey n'est pas definie on n'ouvre pas la fenêtre modale */
  if (mavenKey === undefined || mavenKey === null || mavenKey === '') {
    showMessage('warning', "La clé maven n'est pas valide !", null);
    return;
  }

  /** On réinitialise le drapeau a chaque ouverture */
  donneesAjoutValides = false;

  /* On charge la liste du formulaire d'ajout */
  const t = await versionSonarQubeListe(mavenKey);
  if (!t) return;

  $('.js-version').select2({
    placeholder: 'Cliquez pour ouvrir la liste',
    selectOnClose: true,
    width: '100%',
    minimumResultsForSearch: 5,
    language: 'fr',
    data: t.liste
  });
  $('.analyse').removeClass('hide');

  /* On ouvre la fenetre modale */
  modalSafe.open('#modal-ajouter-analyse');
});
/** On ferme proprement la modale */
$('#bouton-fermer-ajouter-analyse').on('click', function() {
  modalSafe.close('#modal-ajouter-analyse');
});

/* On recharge la page pour mettre à jour la vue */
$('#fermer-modifier-analyse').on('click', ()=>{
  location.reload();
});

/**
 * [Description for versionDataGet]
 * Récupère les métriques d'une version pour la clé maven et la date donnees.
 * POST /api/secure/get/version
 *
 * @param string maven_key
 * @param string date
 * @return object|null
 */
const versionDataGet = async function (maven_key, date) {
  const data = { maven_key, date };
  const options = {
    url: `${serveur()}/api/secure/get/version`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    }
  };

  let t;
  try {
    t = await $.ajax(options);

    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return null;
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors du chargement des metriques (Erreur 500).";
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_critical', trace);
    return null;
  }

  return t;
};

/**
 * [Description for displayMetric]
 * Helper d'affichage des métriques. Si la valeur est null/undefined (métrique
 * absente côté SonarQube — selon la version, certaines ne sont pas remontées),
 * on affiche '-'. Sinon on applique la transformation `mapper` puis le formatter.
 *
 * @param mixed value
 * @param object formatter Intl.NumberFormat
 * @param function|null mapper Transformation optionnelle (ex: v => v / 100)
 * @return string
 */
const displayMetric = function (value, formatter, mapper = null) {
  if (value === null || value === undefined) {
    return '-';
  }
  const v = mapper ? mapper(value) : value;
  return formatter.format(v);
};

/**
 * [Description for displayRating]
 * Helper pour les notes A-E. Accepte deux formats :
 *  - lettre directe ('A','B','C','D','E','--') sortie de
 *    BuildMapHistoryService::metricsRebuild → ratingToLetter() côté PHP.
 *  - nombre 1-5 (legacy, avant la refacto getVersion).
 * null/undefined/'--' ou hors plage → '-'.
 *
 * @param mixed value
 * @return object {couleur, lettre}
 */
const displayRating = function (value) {
  if (value === null || value === undefined || value === '--') {
    return { couleur: '', lettre: '-' };
  }
  // Format lettre (A-E) — sortie courante de metricsRebuild
  if (typeof value === 'string' && /^[A-E]$/i.test(value)) {
    const upper = value.toUpperCase();
    return { couleur: upper.toLowerCase(), lettre: upper };
  }
  // Format numérique 1-5 — fallback legacy
  const tNotes1 = ['', 'a', 'b', 'c', 'd', 'e', 'z'];
  const tNotes2 = ['', 'A', 'B', 'C', 'D', 'E', 'Z'];
  const idx = Number(value);
  return { couleur: tNotes1[idx] ?? '', lettre: tNotes2[idx] ?? '-' };
};

/**
 * [Description for populerMetriques]
 * Affiche les métriques récupérées dans la modale "ajouter une analyse".
 * Stocke aussi les valeurs en data-* pour les récupérer a l'enregistrement.
 *
 * @param object data
 */
const populerMetriques = function (data) {
  /** Snapshot complet pour round-trip vers suiviMiseAJour. */
  dernieresMetriques = data;

  const reliability = displayRating(data.reliability_rating);
  const security = displayRating(data.security_rating);
  const sqale = displayRating(data.sqale_rating);
  const hotspotsReview = displayRating(data.security_review_rating);

  /* On affiche les notes */
  $('#reliability-rating').html(`<span class="note note-${reliability.couleur}">${reliability.lettre}</span>`);
  $('#security-rating').html(`<span class="note note-${security.couleur}">${security.lettre}</span>`);
  $('#sqale-rating').html(`<span class="note note-${sqale.couleur}">${sqale.lettre}</span>`);
  $('#security-review-rating').html(`<span class="note note-${hotspotsReview.couleur}">${hotspotsReview.lettre}</span>`);

  /* Historique des notes via data-* — on stocke la valeur BRUTE de
   * metricsRebuild ('A'-'E' ou null), pas le résultat formaté '-' du helper.
   * Permet à la normalisation backend de convertir 'null' string → null PHP. */
  document.getElementById('reliability-rating').dataset.reliabilityRating = data.reliability_rating;
  document.getElementById('security-rating').dataset.securityRating = data.security_rating;
  document.getElementById('sqale-rating').dataset.sqaleRating = data.sqale_rating;
  document.getElementById('security-review-rating').dataset.securityReviewRating = data.security_review_rating;

  /* Ratings dérivés calculés par metricsRebuild (lettres A-E ou null) */
  const ratingDerives = [
    ['coverage-rating', data.coverage_rating],
    ['duplicated-lines-rating', data.duplicated_lines_rating],
    ['comment-lines-rating', data.comment_lines_rating],
    ['complexity-rating', data.complexity_rating],
    ['cognitive-complexity-rating', data.cognitive_complexity_rating],
  ];
  ratingDerives.forEach(([id, value]) => {
    const r = displayRating(value);
    $(`#${id}`).html(`<span class="note note-${r.couleur}">${r.lettre}</span>`);
  });

  /* Quality Gate : 'OK' / 'ERROR' / 'WARN' / 'UNKNOWN' / null → icône + libellé */
  const alertMap = {
    OK:      '<span class="note note-a">✓ OK</span>',
    ERROR:   '<span class="note note-e">✗ ERROR</span>',
    WARN:    '<span class="note note-c">⚠ WARN</span>',
    WARNING: '<span class="note note-c">⚠ WARN</span>',
  };
  $('#alert-status').html(alertMap[data.alert_status] ?? '<span>—</span>');

  /* Bugs / vulnerabilites / mauvaises pratiques */
  const fmtDecimal = new Intl.NumberFormat('fr-FR', { style: 'decimal' });
  const fmtPercent = new Intl.NumberFormat('fr-FR', { style: 'percent', maximumFractionDigits: 2 });

  $('#violations').html(displayMetric(data.violations, fmtDecimal));
  $('#bugs').html(displayMetric(data.bugs, fmtDecimal));
  $('#vulnerabilities').html(displayMetric(data.vulnerabilities, fmtDecimal));
  $('#code-smells').html(displayMetric(data.code_smells, fmtDecimal));
  $('#security-hotspots').html(displayMetric(data.security_hotspots, fmtDecimal));

  /* Historique */
  document.getElementById('violations').dataset.violations = data.violations;
  document.getElementById('bugs').dataset.bugs = data.bugs;
  document.getElementById('vulnerabilities').dataset.vulnerabilities = data.vulnerabilities;
  document.getElementById('code-smells').dataset.codeSmells = data.code_smells;
  document.getElementById('security-hotspots').dataset.securityHotspots = data.security_hotspots;

  /* Repartition des langages */
  $('#ncloc-language-distribution').empty();
  const liste = data.ncloc_language_distribution;
  if (typeof liste === 'string' && liste.length > 0) {
    const langages = liste.split(';').map(pair => pair.split('=')[0]);
    const nombreLangage = langages.length;
    langages.forEach((language, idx) => {
      const ponctuation = (idx + un === nombreLangage) ? '.' : ',';
      $('#ncloc-language-distribution').append(`<span class="nasa">${language}</span>${ponctuation}&nbsp;`);
    });
  }

  $('#files').html(displayMetric(data.files, fmtDecimal));
  $('#classes').html(displayMetric(data.classes, fmtDecimal));
  $('#functions').html(displayMetric(data.functions, fmtDecimal));
  $('#ncloc').html(displayMetric(data.ncloc, fmtDecimal));
  $('#lines').html(displayMetric(data.lines, fmtDecimal));
  $('#comment-lines').html(displayMetric(data.comment_lines, fmtDecimal));
  $('#comment-lines-density').html(displayMetric(data.comment_lines_density, fmtPercent, v => v / cent));
  $('#dette').html(displayMetric(data.sqale_index, fmtDecimal, v => v / soixante / soixante));
  $('#sqale-debt-ratio').html(displayMetric(data.sqale_debt_ratio, fmtPercent, v => v / cent));
  $('#coverage').html(displayMetric(data.coverage, fmtPercent, v => v / cent));
  $('#duplicated-lines-density').html(displayMetric(data.duplicated_lines_density, fmtPercent, v => v / cent));
  $('#tests').html(displayMetric(data.tests, fmtDecimal));
  $('#test-success-density').html(displayMetric(data.test_success_density, fmtPercent, v => v / cent));
  $('#skipped-tests').html(displayMetric(data.skipped_tests, fmtDecimal));
  $('#test-errors').html(displayMetric(data.test_errors, fmtDecimal));
  $('#test-failures').html(displayMetric(data.test_failures, fmtDecimal));

  /* Complexité cyclomatique + cognitive (valeurs brutes + ratios calculés par metricsRebuild) */
  $('#complexity').html(displayMetric(data.complexity, fmtDecimal));
  $('#cognitive-complexity').html(displayMetric(data.cognitive_complexity, fmtDecimal));
  $('#complexity-ratio').html(displayMetric(data.complexity_ratio, fmtDecimal));
  $('#cognitive-complexity-ratio').html(displayMetric(data.cognitive_complexity_ratio, fmtDecimal));

  /* Historique */
  document.getElementById('ncloc-language-distribution').dataset.nclocLanguageDistribution = data.ncloc_language_distribution;
  document.getElementById('files').dataset.files = data.files;
  document.getElementById('classes').dataset.classes = data.classes;
  document.getElementById('functions').dataset.functions = data.functions;
  document.getElementById('ncloc').dataset.ncloc = data.ncloc;
  document.getElementById('lines').dataset.lines = data.lines;
  document.getElementById('comment-lines').dataset.commentLines = data.comment_lines;
  document.getElementById('comment-lines-density').dataset.commentLinesDensity = data.comment_lines_density;
  document.getElementById('coverage').dataset.coverage = data.coverage;
  document.getElementById('duplicated-lines-density').dataset.duplicatedLinesDensity = data.duplicated_lines_density;
  document.getElementById('dette').dataset.dette = data.sqale_index;
  document.getElementById('sqale-debt-ratio').dataset.sqaleDebtRatio = data.sqale_debt_ratio;
  document.getElementById('tests').dataset.tests = data.tests;
  document.getElementById('test-success-density').dataset.testSuccessDensity = data.test_success_density;
  document.getElementById('skipped-tests').dataset.skippedTests = data.skipped_tests;
  document.getElementById('test-errors').dataset.testErrors = data.test_errors;
  document.getElementById('test-failures').dataset.testFailures = data.test_failures;
};

/**
 * description
 * On charge les donnees de la version selectionnee depuis la fenetre "ajouter".
 */
$('select[name="version"]').on('change', async function () {
  /** Si la valeur selectionnee est TheID alors on sort */
  const selection = $('select[name="version"]').val();
  if (selection === 'TheID' || selection === undefined) {
    return;
  }

  /** A chaque changement de selection, on invalide les donnees jusqu'a confirmation */
  donneesAjoutValides = false;

  /* On affiche la clé */
  const mavenKey = $('#titre-js-nom').data('maven').trim();
  $('#key-maven').html(mavenKey);

  /* On affiche le nom */
  const name = mavenKey.split(':');
  $('#nom').html(name[un]);

  /* On recupere la date depuis le texte du select : "4.2.1-RELEASE (18-09-2024 16:57:50)" */
  const texteVersion = $('#liste-version :selected').text();
  const apresParenthese = texteVersion.split('(')[un];
  const dateBrute = apresParenthese ? apresParenthese.split(')')[zero] : '';

  /* On stocke la date pour l'enregistrement */
  document.getElementById('date').dataset.date = dateBrute;

  /* On affiche la version et la date */
  $('#version').html(texteVersion.split('(')[zero]);
  $('#date').html(dateBrute);

  /* On appelle l'API de recuperation des metriques */
  const t = await versionDataGet(mavenKey, dateBrute);
  if (!t) return;

  if (t.code !== http_200) {
    showMessage(t.type ?? 'warning', t.message ?? `Erreur ${t.code} lors du chargement des metriques.`, null);
    return;
  }

  /** Tout va bien : on populent les metriques et on autorise l'enregistrement. */
  populerMetriques(t.data);

  /** On enrichit dernieresMetriques avec les meta-données SonarQube
   *  attachées à l'option select2 — analyse_key et compteurs cumulés
   *  version_release/snapshot/autre calculés par SonarAnalysisFetcherService.
   *  Ces champs ne viennent pas de getVersion (search_history) mais de
   *  listeVersionV2 (project_analyses/search) — ils sont donc injectés ici
   *  pour propagation jusqu'à suiviMiseAJour. */
  const selectedOption = $('#liste-version').select2('data')[zero] || {};
  dernieresMetriques.analyse_key = selectedOption.analyse_key ?? null;
  dernieresMetriques.version_release = selectedOption.version_release ?? null;
  dernieresMetriques.version_snapshot = selectedOption.version_snapshot ?? null;
  dernieresMetriques.version_autre = selectedOption.version_autre ?? null;

  donneesAjoutValides = true;
  showMessage('info', "Les donnees du projet sont disponibles.", null);
  setTimeout(() => { hideMessage(); }, 3000);
});

/**
 * [Description for historiqueAjouter]
 * Enregistre une version dans l'historique.
 * PUT /api/secure/suivi/mise-a-jour
 *
 * @param object data
 * @return object|null
 */
const historiqueAjouter = async function (data) {
  const options = {
    url: `${serveur()}/api/secure/suivi/mise-a-jour`,
    type: 'PUT',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    }
  };

  let t;
  try {
    t = await $.ajax(options);

    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return null;
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de l'enregistrement de la version (Erreur 500).";
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_critical', trace);
    return null;
  }

  return t;
};

/**
 * description
 * Ajouter/enregistrer les donnees d'une analyse.
*/
$('.js-enregistrer-analyse').on('click', async () => {
  const selectionVersion = $('select[name="version"]').val();

  /** Si le projet n'a pas ete selectionne */
  if (selectionVersion === undefined || selectionVersion === 'TheID') {
    showMessage('warning', 'Vous devez choisir une version !', null);
    return;
  }

  /** Si les donnees n'ont pas ete chargees correctement (404, erreur API, ...) */
  if (donneesAjoutValides !== true) {
    showMessage('warning', "Les donnees de la version ne sont pas disponibles, l'enregistrement est impossible.", null);
    return;
  }

  /** On reformate la date pour avoir l'annee en premier.
   *  PHP format 'O' produit "+0200" (sans ':') → token date-fns "XX".
   *  Avant : "XXX" attendait "+02:00" → parse échouait avec RangeError. */
  const dateBrute = document.getElementById('date').dataset.date;
  const parsed_date = parse(dateBrute, "dd-MM-yyyy HH:mm:ssXX", new Date());
  const formatted_date = format(parsed_date, "yyyy-MM-dd HH:mm:ss");

  /** Payload aligné sur les clés SonarQube (= colonnes DB historique).
   *  project_name = dernier segment du mavenKey (ex: "fr.acme:paddpm" → "paddpm").
   *  On spread `dernieresMetriques` (snapshot complet de getVersion → ~50
   *  fields enrichis par metricsRebuild) puis on override avec les méta de
   *  la version sélectionnée. Le backend ne retient que les colonnes connues
   *  via la whitelist du repo. */
  const mavenKey = $('#titre-js-nom').data('maven').trim();
  const data = {
    ...dernieresMetriques,
    maven_key: mavenKey,
    project_name: mavenKey.split(':').pop(),
    version: $('#version').text().trim(),
    date_version: formatted_date,
    initial: zero,
  };

  const t = await historiqueAjouter(data);
  if (!t) return;

  /** Doublon : warning explicite (la version existe deja). */
  if (t.code === 23505) {
    showMessage(t.type ?? 'warning', t.message ?? 'Cette version est deja présente dans l\'historique.', null);
    return;
  }

  if (t.code === http_200) {
    showMessage(t.type ?? 'success', t.message ?? 'Enregistrement effectué.', null);
    setTimeout(() => { hideMessage(); }, 5000);
    /** On bloque un nouvel enregistrement de la meme version sans recharger */
    donneesAjoutValides = false;
    return;
  }

  /** Autres codes inattendus */
  const trace = t.trace ? prepareTechnicalDetails(t.trace) : null;
  showMessage(t.type ?? 'error', t.message ?? `Erreur lors de la mise a jour (${t.code}).`, trace);
});

/* MODIF 2026-05-06 : ancien générateur jsPDF + html2canvas
 * supprime. Le rapport PDF est maintenant cote serveur (Dompdf) via la route
 * /suivi/rapport/pdf, declenchee par <a class="js-imprimer-analyse"> dans
 * _menu_outils.html.twig (target="_blank"). */

/**
 * [Description for versionListe]
 *
 *  return json
 *
 * Created at: 30/04/2026 20:19:16 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const versionListe = async function(){

  const poubelle=`<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewbox="0 0 32 32"  class="poubelle-svg">
  title>Icône pour le bouton poubelle.</title>
  <desc>Supprime la version du suivi.</desc>
  <path d="M11.002.061c-.9.155-1.808.775-2.187 1.495-.26.49-.352.905-.352 1.6v.546l-2.054.03c-2.167.025-2.315.044-3.152.354C2.054 4.533.957 5.5.443 6.566c-.288.602-.359.944-.4 1.843L0 9.296H31.699l-.043-.887c-.042-.9-.112-1.24-.4-1.842-.514-1.067-1.612-2.034-2.815-2.48-.837-.31-.984-.33-3.144-.354l-2.061-.031v-.546c0-.298-.035-.688-.07-.875-.24-1.116-1.26-2.015-2.526-2.226-.464-.074-9.187-.074-9.638.006zm9.56 1.917c.423.192.563.483.563 1.178v.558H10.573v-.558c0-.682.14-.986.549-1.172.253-.118.422-.124 4.713-.13 4.327 0 4.46.006 4.728.124zM2.244 11.386c.021.13.443 4.242.943 9.135.956 9.414.95 9.333 1.364 9.916.45.62 1.267 1.11 2.076 1.247.513.087 17.931.087 18.445 0 .809-.137 1.625-.627 2.075-1.247.415-.583.408-.502 1.365-9.916.5-4.893.921-9.005.942-9.135l.029-.23H2.216zm7.977 1.755c.303.136.458.34.521.682.077.428.696 13.37.647 13.55-.07.249-.485.577-.788.627-.584.093-1.174-.28-1.252-.794-.042-.285-.675-12.707-.675-13.234 0-.732.809-1.166 1.547-.831zm6.12 0c.184.08.331.21.423.372.14.242.14.353.14 7.008 0 6.654 0 6.766-.14 7.008-.162.279-.563.496-.915.496-.351 0-.752-.217-.914-.496-.14-.242-.14-.354-.14-7.008 0-6.655 0-6.766.14-7.008.26-.453.872-.614 1.407-.372zm6.283.08c.464.31.464-.124.091 7.231-.183 3.672-.366 6.773-.4 6.903-.127.428-.697.732-1.225.645-.295-.05-.71-.385-.78-.626-.05-.18.57-13.123.647-13.551a.884.884 0 01.555-.695 1.192 1.192 0 011.112.093z"/>
  </svg>`;

  /* On récupère la clé maven */
  const data = { maven_key: $('#titre-js-nom').data('maven') };

  const options = {
    url: `${serveur()}/api/secure/suivi/version/liste`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    }
  };

  let t;
  try {
        t = await $.ajax(options);

        // 📌 Vérification des erreurs
        const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
        if (errorCodes.includes(t.code)){
            const hasTrace = !!t.trace;
            const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
            showMessage(t.type, t.message, trace);
          return
        }
  } catch(error) {
        const trace = prepareTechnicalDetails(error);
        const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur les versions (Erreur 500).";
        showMessage('critical', message, trace);
        sessionStorage.setItem('ma_moulinette_critical', trace);
        return;
  }

  // On affiche un bo message.
  showMessage(t.type, t.message, null);
  setTimeout(() => { hideMessage(); }, 3000);

  /* On boucle pour construire le tableau */
  // MODIF 2026-06-11 [suivi-version-selection] : compteur suivi courant
  let ligne = 0 , html = '', switchFavori = '', switchReference = '', switchSuivi = '';
  let compteurSuivi = t.suivi_version_count ?? zero;
  $('#compteur-suivi').text(`(${compteurSuivi}/15)`);

  $('#tableau-liste-version').html(html);

  t.versions.forEach(version => {
    ligne++;
    /* On défini le switch pour le favori */
    switchFavori = '<div class="switch custom-switch-favori js-switch-favori">';
    switchFavori += `<input class="switch-input" id="switch-favori-${ligne}" type="checkbox" name="switch-favori-${ligne}">`;
    switchFavori += `<label class="switch-paddle" for="switch-favori-${ligne}">`;
    switchFavori += '<span class="show-for-sr">Projet favori</span>';
    switchFavori += '</label></div>';

    /* On défini le switch pour la référence (checkbox : type="radio" empêchait le toggle off → "renvoie toujours true") */
    switchReference = '<div class="switch custom-switch-reference js-switch-reference">';
    switchReference += `<input class="switch-input" id="switch-reference-${ligne}" type="checkbox" name="switch-reference-${ligne}">`;
    switchReference += `<label class="switch-paddle" for="switch-reference-${ligne}">`;
    switchReference += '<span class="show-for-sr">Projet de référence</span>';
    switchReference += '</label></div>';

    /* MODIF 2026-06-11 : switch Suivi */
    switchSuivi = '<div class="switch custom-switch-suivi js-switch-suivi">';
    switchSuivi += `<input class="switch-input" id="switch-suivi-${ligne}" type="checkbox" name="switch-suivi-${ligne}">`;
    switchSuivi += `<label class="switch-paddle" for="switch-suivi-${ligne}">`;
    switchSuivi += '<span class="show-for-sr">Version à suivre</span>';
    switchSuivi += '</label></div>';

    /*  On construit le tableau */
    const date_version=new Intl.DateTimeFormat('default', dateOptions).format(new Date(version.date));

    html = `<tr id="ligne-${ligne}">`;
    html += `<td id="poubelle-${ligne}" headers="modifier-preference action" class="text-left">${poubelle}</td>`;
    html += `<td id="date-${ligne}" data-date="${version.date}" headers="modifier-preference date" class="text-left">${date_version}</td>`;
    html += `<td id="version-${ligne}" headers="modifier-preference version" class="text-left">${version.version}</td>`;
    html += `<td id="favori-${ligne}" headers="modifier-preference favori" class="text-left">${switchFavori}</td>`;
    html += `<td id="reference-${ligne}" headers="modifier-preference reference" class="text-left">${switchReference}</td>`;
    html += `<td id="suivi-${ligne}" headers="modifier-preference suivi" class="text-left">${switchSuivi}</td>`;
    html += '</tr>';

    /* On ajoute la ligne */
    $('#tableau-liste-version').append(html);

    /**
      * Favori|reference enable
      * Il faut que l'utilisateur ait activé les favoris dans ces préférences.
      */
    if (version.favori===true) {
      $(`#switch-favori-${ligne}`).trigger('click');
    }

    if (version.initial===true) {
      $(`#switch-reference-${ligne}`).trigger('click');
    }

    /* MODIF 2026-06-11 : pré-cocher si la version est en suivi */
    if (version.suivi === true) {
      $(`#switch-suivi-${ligne}`).prop('checked', true);
    }
  });

  return t;
};

/**
 * [Description for versionFavoriUpdate]
 * Met a jour le statut "favori" d'une version d'un projet.
 * PUT /api/secure/suivi/version/favori
 */
const versionFavoriUpdate = async function (maven_key, favori, version, date_version) {
  const data = { maven_key, favori, version, date_version };
  const options = {
    url: `${serveur()}/api/secure/suivi/version/favori`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    }
  };

  let t;
  try {
    t = await $.ajax(options);

    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return null;
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la mise a jour du favori (Erreur 500).";
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_critical', trace);
    return null;
  }

  return t;
};

/**
 * [Description for versionReferenceUpdate]
 * Met a jour la version de reference d'un projet.
 * PUT /api/secure/suivi/version/reference
 */
const versionReferenceUpdate = async function (maven_key, initial, version, date_version) {
  const data = { maven_key, initial, version, date_version };
  const options = {
    url: `${serveur()}/api/secure/suivi/version/reference`,
    type: 'PUT',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    }
  };

  let t;
  try {
    t = await $.ajax(options);

    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return null;
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la mise a jour de la version de reference (Erreur 500).";
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_critical', trace);
    return null;
  }

  return t;
};

/**
 * [Description for versionPoubelleDelete]
 * Supprime une version d'un projet de l'historique.
 * PUT /api/secure/suivi/version/poubelle
 */
const versionPoubelleDelete = async function (maven_key, version, date_version) {
  const data = { maven_key, version, date_version };
  const options = {
    url: `${serveur()}/api/secure/suivi/version/poubelle`,
    type: 'PUT',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    }
  };

  let t;
  try {
    t = await $.ajax(options);

    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return null;
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la suppression de la version (Erreur 500).";
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_critical', trace);
    return null;
  }

  return t;
};

/**
 * [Description for versionSuiviUpdate]
 * Ajoute ou supprime une version de la liste de suivi personnalisée.
 * POST /api/secure/suivi/version/suivi
 * MODIF 2026-06-11 [suivi-version-selection]
 */
const versionSuiviUpdate = async function (maven_key, version, suivi) {
  const data = { maven_key, version, suivi };
  const options = {
    url: `${serveur()}/api/secure/suivi/version/suivi`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    }
  };

  let t;
  try {
    t = await $.ajax(options);

    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)) {
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return null;
    }
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors de la mise a jour du suivi (Erreur 500).";
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_critical', trace);
    return null;
  }

  return t;
};

/**
 * description
 * On charge la liste des versions, puis on bind les sous-events sur les
 * elements construits par versionListe (favori, reference, poubelle).
 */
$('.js-modifier-analyse').on('click', async function () {
  const result = await versionListe();
  if (!result) {
    /** versionListe a deja affiche le message d'erreur */
    return;
  }
  const preferenceFavori = !!result.preference_favori;

  /** On gère le changement de favori pour la version du projet */
  $('[id^=switch-favori-]').on('click', async function (e) {
    if (preferenceFavori === false) {
      const message = "Vous n'avez pas active les favoris dans vos preferences.";
      showMessage('warning', message, null);
      return;
    }

    /** on récupéré la version et la date */
    const id = $(e.currentTarget).attr('id');
    const l = id.split('-');
    const version = $(`#version-${l[deux]}`).text().trim();
    /** On lit la date brute (TIMESTAMPTZ PG) depuis data-date pour eviter les pieges de format/locale */
    const formatted_date = $(`#date-${l[deux]}`).data('date');

    /** 0 (false) and 1 (true). */
    const favori = $(`#${id}:checked`).length === un ? un : zero;

    const maven_key = $('#titre-js-nom').data('maven');
    if (maven_key === undefined) {
      showMessage('warning', "La clé maven n'est pas valide !", null);
      return;
    }

    const t = await versionFavoriUpdate(maven_key, favori, version, formatted_date);
    if (!t) return;

    if (t.code === http_200) {
      showMessage('info', "Mise a jour du favori effectuée.", null);
      setTimeout(() => { hideMessage(); }, 3000);
    } else if (t.code === http_201) {
      showMessage('warning', "Cette version a ete supprimée des favoris.", null);
    }
  });

  /** On gère le changement de version de reference */
  $('[id^=switch-reference-]').on('click', async function (e) {
    /** on récupère la version et la date */
    const id = $(e.currentTarget).attr('id');
    const l = id.split('-');
    const version = $(`#version-${l[deux]}`).text().trim();

    /** On lit la date brute (TIMESTAMPTZ PG) depuis data-date pour éviter les pièges de format/locale */
    const formatted_date = $(`#date-${l[deux]}`).data('date');

    const maven_key = $('#titre-js-nom').data('maven');
    if (maven_key === undefined) {
      showMessage('warning', "La clé maven n'est pas valide !", null);
      return;
    }

    /** 0 (false) and 1 (true). */
    const initial = $(`#${id}:checked`).length === un ? un : zero;

    // MODIF 2026-06-02 : mémoriser l'ancienne
    // référence AVANT la mutation UI pour pouvoir la restaurer en cas d'erreur.
    const previousRef = $('[id^=switch-reference-]:checked').not(`#${id}`).attr('id') ?? null;

    /** Exclusion mutuelle UI : une seule version peut être référence. Si on en
       *  coche une, on décoche visuellement toutes les autres (le serveur fait
       *  déjà UPDATE initial=false WHERE maven_key=...). */
    if (initial === un) {
      $('[id^=switch-reference-]').not(`#${id}`).prop('checked', false);
    }

    const t = await versionReferenceUpdate(maven_key, initial, version, formatted_date);

    // MODIF 2026-06-02 : rollback UI si le
    // serveur a refusé (null = erreur déjà affichée par versionReferenceUpdate).
    if (!t) {
      $(`#${id}`).prop('checked', initial === zero);
      if (previousRef) {
        $(`#${previousRef}`).prop('checked', true);
      }
      return;
    }

    if (t.code === http_200) {
      showMessage('info', "Mise a jour de la version de reference effectuée.", null);
      setTimeout(() => { hideMessage(); }, 3000);
      return;
    }

    showMessage(t.type ?? 'error', t.message ?? "Le changement de version de référence a échoué !", null);
  });

  /** MODIF 2026-06-11 : toggle suivi d'une version */
  $('[id^=switch-suivi-]').on('click', async function (e) {
    const id = $(e.currentTarget).attr('id');
    const l = id.split('-');
    const version = $(`#version-${l[deux]}`).text().trim();
    const maven_key = $('#titre-js-nom').data('maven');

    if (maven_key === undefined) {
      showMessage('warning', "La clé maven n'est pas valide !", null);
      return;
    }

    const suivi = $(`#${id}:checked`).length === un ? un : zero;

    // Bloquer l'ajout si on dépasse 15
    if (suivi === un) {
      const compteurTexte = $('#compteur-suivi').text(); // "(N/15)"
      const match = compteurTexte.match(/\((\d+)\/15\)/);
      const current = match ? parseInt(match[1], 10) : zero;
      if (current >= 15) {
        showMessage('warning', "La limite de 15 versions pour le suivi est atteinte.", null);
        $(`#${id}`).prop('checked', false);
        return;
      }
    }

    const t = await versionSuiviUpdate(maven_key, version, suivi);
    if (!t) {
      // erreur : annuler le toggle visuel
      $(`#${id}`).prop('checked', suivi === zero);
      return;
    }

    if (t.code === http_200) {
      // Mettre à jour le compteur
      $('#compteur-suivi').text(`(${t.suivi_version_count ?? zero}/15)`);
      showMessage('info', "Mise a jour du suivi effectuée.", null);
      setTimeout(() => { hideMessage(); }, 3000);
    } else {
      showMessage(t.type ?? 'error', t.message ?? "Le changement de suivi a échoué !", null);
      $(`#${id}`).prop('checked', suivi === zero);
    }
  });

  /** On supprime la version du projet en table et on masque la ligne */
  $('[id^=poubelle-]').on('click', async function (e) {
    /** On récupère la version et la date */
    const id = $(e.currentTarget).attr('id');
    const l = id.split('-');
    const version = $(`#version-${l[un]}`).text().trim();
    /** On lit la date brute (TIMESTAMPTZ PG) depuis data-date pour éviter les pièges de format/locale */
    const formatted_date = $(`#date-${l[un]}`).data('date');

    let maven_key = $('#titre-js-nom').data('maven');
    if (maven_key === undefined || maven_key === null) {
      maven_key = 'null';
    }

    const t = await versionPoubelleDelete(maven_key, version, formatted_date);
    if (!t) return;

    if (t.code === http_200) {
      const tail = t.message ? ` ${t.message}` : '';
      showMessage('info', `Le projet a été correctement supprimé. ${tail}`, null);
      setTimeout(() => { hideMessage(); }, 3000);
      $(`#ligne-${l[un]}`).hide();
      return;
    }

    if (t.code === http_403) {
      showMessage('warning', t.message ?? "Le projet n'a pas été supprimé !", null);
    } else {
      const trace = t.erreur ? prepareTechnicalDetails(t.erreur) : null;
      showMessage(t.type ?? 'error', t.message ?? "Le projet n'a pas été supprimé !", trace);
    }
  });

  /** fin de la méthode on ouvre la fenêtre */
  modalSafe.open('#modal-modifier-analyse');
});

/** on ferme proprement la modale */
$('#bouton-fermer-modifier-analyse').on('click', function() {
  modalSafe.close('#modal-modifier-analyse');
});
