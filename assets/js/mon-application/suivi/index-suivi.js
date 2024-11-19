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

/** On charge la version html2pdf depuis les assets */
import { jsPDF } from 'jspdf';
import html2canvas from 'html2canvas';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import 'chartjs-adapter-date-fns';
import { parse, format } from 'date-fns';
import { fr } from 'date-fns/locale';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels);

/** On importe les constantes */
import { http_200, http_201,http_400, http_404, chartColors, zero, un,
        deux, soixante, cent, dateOptions, contentType } from '../../common/constante.js';

/* Construction des callbox de type success */
const callboxInformation='<div id="js-message" class="callout alert-callout-border primary" data-closable="slide-out-right" role="alert"><p class="open-sans color-bleu padding-right-1"><span class="lead"><strong>Information ! </strong></span>';
const callboxSuccess='<div id="js-message" class="callout alert-callout-border success" data-closable="slide-out-right" role="alert"><p class="open-sans color-bleu padding-right-1"><span class="lead"><strong>Bravo ! </strong></span>';
const callboxWarning='<div id="js-message" class="callout alert-callout-border warning" data-closable="slide-out-right" role="alert"><p class="open-sans padding-right-1 color-bleu"><span class="lead"><strong>Attention ! </strong></span>';
const callboxError='<div id="js-message" class="callout alert-callout-border alert" data-closable="slide-out-right"><p class="open-sans padding-right-1 color-bleu"><span class="lead"><strong>Oups ! </strong></span>';
const callboxFermer='</p><button class="close-button" aria-label="Fermer la fenêtre" type="button" data-close><span aria-hidden="true">&times;</span></button></div>';

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
    sessionStorage.set('info', 'chartJs is null');
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

/**
* [Description for selectVersion]
* Création de la liste des projets pour le sélecteur.
*
* @param string mavenKey
*
* @return [type]
*
* Created at: 19/12/2022, 23:00:07 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
const selectVersion=async function(mavenKey) {
  const data={ maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/liste/v2.0/version`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  const r = await $.ajax(options);
  if (r.code===http_400) {
    const message=`La requête est incorrecte (Erreur 400).`;
    $('#message-ajout-projet').html(callboxError+message+callboxFermer);
    return;
  }

  $('.js-version').select2({
    placeholder: 'Cliquez pour ouvrir la liste',
    selectOnClose: true,
    width: '100%',
    minimumResultsForSearch: 5,
    language: 'fr',
    data: r.liste
  });
  $('.analyse').removeClass('hide');
  };

/**
 * description
 * On affiche la liste des projets
 */
$('.js-ajouter-analyse').on('click', function () {
  const mavenKey=$('#js-nom').data('maven');

  /** Si la clé mavenKey n'est pas défini on ouvre pas la fenêtre modale */
  if (mavenKey===null || mavenKey==='') {
    return;
  }

  /* On charge la liste du formulaire d'ajout */
  selectVersion(mavenKey);

  /* On ouvre la fenêtre modale */
  $('#modal-ajouter-analyse').foundation('open');
});

/* On recharge la page pour mettre à jour la vue */
$('#fermer-choisir-analyse').on('click', ()=>{
  location.reload();
});

/**
 * description
 * On charge les données de la version sélectionnée depuis la fenêtre ajouté
 */
$('select[name="version"]').on('change', function () {
  /** si la valeur sélectionné est TheId alors on sort */
  if ($('select[name="version"]')==='TheId'){
    return;
  }
  /* On affiche la clé */
  $('#key-maven').html($('#js-nom').data('maven').trim());

  /* On affiche le nom */
  const n=$('#js-nom').data('maven').trim();
  const name=n.split(':');
  $('#nom').html(name[1]);
  /* On récupère la date et on l'a nettoie avant de l'envoyer */
  const d=$('#liste-version :selected').text();
  //4.2.1-RELEASE (18-09-2024 16:57:50)
  const d1=d.split('(');
  const d2=d1[1].split(')');
  const t0 = document.getElementById('date');
  t0.dataset.date=(d2[0]);

  /* On affiche la version */
  $('#version').html(d1[0]);
  /* On affiche la date */
  $('#date').html(d2);

  /**
   *  On appel l'API de récupération des versions
  */
  const data = { maven_key: $('#key-maven').text().trim(), date:d2[0] };
  const options = {
    url: `${serveur()}/api/get/version`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

    $.ajax(options).then(t => {
    /** On récupère le message
     * Si 404 --> le projet n'existe plus
     * Si 200 --> on continue
     * si <> 200 et 404, exception symfony
    */
    if (t.code===http_400 && t.data===null || t.code===http_400 && t.maven_key===null) {
      const message=`La requête est incorrecte (Erreur 400).`;
      $('#message-ajout-projet').html(callboxError+message+callboxFermer);
      return;
    }

    if (t.code===http_404) {
      const message=`Le projet n'existe plus sur le serveur SonarQube ! (Erreur 406)`;
      $('#message-ajout-projet').html(callboxError+message+callboxFermer);
      return;
    }

    /** Tout va bien. */
    if (t.code===http_200) {
      const message=`Les données pour le projet sont disponibles.`;
      $('#message-ajout-projet').html(callboxInformation+message+callboxFermer);
    }
    const tNotes1 = ['', 'a', 'b', 'c', 'd', 'e', 'z'];
    const tNotes2 = ['', 'A', 'B', 'C', 'D', 'E', 'Z'];
    const couleurReliability = tNotes1[parseInt(t.data.reliability_rating,10)];
    const couleurSecurity = tNotes1[parseInt(t.data.security_rating,10)];
    const couleurSqale = tNotes1[parseInt(t.data.sqale_rating,10)];
    const couleurHotspotsReview = tNotes1[parseInt(t.data.security_review_rating,10)];

    const reliability_rating = tNotes2[parseInt(t.data.reliability_rating,10)];
    const security_rating = tNotes2[parseInt(t.data.security_rating,10)];
    const sqale_rating = tNotes2[parseInt(t.data.sqale_rating,10)];
    const security_review_rating = tNotes2[parseInt(t.data.security_review_rating,10)];

    /*  On affiche les notes */
    $('#reliability-rating').html(`<span class="note note-${couleurReliability}">${reliability_rating}</span>`);
    $('#security-rating').html(`<span class="note note-${couleurSecurity}">${security_rating}</span>`);
    $('#sqale-rating').html(`<span class="note note-${couleurSqale}">${sqale_rating}</span>`);
    $('#security-review-rating').html(`<span class="note note-${couleurHotspotsReview}">${security_review_rating}</span>`);

    /* Historique*/
    const t1 = document.getElementById('reliability-rating');
    const t2 = document.getElementById('security-rating');
    const t3 = document.getElementById('sqale-rating');
    const t4 = document.getElementById('security-review-rating');
    t1.dataset.reliabilityRating=(reliability_rating);
    t2.dataset.securityRating=(security_rating);
    t3.dataset.sqaleRating=(sqale_rating);
    t4.dataset.securityReviewRating=(security_review_rating);

    /* On affiche le nombre de bugs, de vulnérabilités et de mauvaises pratiques. */
    $('#violations').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.violations));
    $('#bugs').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.bugs));
    $('#vulnerabilities').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.vulnerabilities));
    $('#code-smells').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.code_smells));
    const verifyHotspotsReview=t.data.security_hotspots;
    if (verifyHotspotsReview !== -1) {
      $('#security-hotspots').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(verifyHotspotsReview));
    } else {
      $('#security-hotspots').html('-');
    }

    /* historique */
    const t5 = document.getElementById('violations');
    const t5a = document.getElementById('bugs');
    const t6 = document.getElementById('vulnerabilities');
    const t7 = document.getElementById('code-smells');
    const t8 = document.getElementById('security-hotspots');
    t5.dataset.violations=(t.data.violations);
    t5a.dataset.bugs=(t.data.bugs);
    t6.dataset.vulnerabilities=(t.data.vulnerabilities);
    t7.dataset.codeSmells=(t.data.code_smells);
    t8.dataset.securityHotspots=(t.data.security_hotspots);

    /* On affiche les autres métriques */
    const liste=t.data.ncloc_language_distribution;
    // Étape 1 : Diviser la chaîne en paires clé=valeur
    const pairs = liste.split(';');

    // Étape 2 : Extraire les clés (noms des langages) de chaque paire
    const langages = pairs.map(pair => pair.split('=')[0]);

    // Étape 3 : Créer un <span> pour chaque langage et l'ajouter au conteneur #affiche
    let ponctuation=',';
    const nombreLangage=langages.length;
    let i=0;
    langages.forEach(language => {
      i++;
      if (nombreLangage === i) { ponctuation='.' }
      const span = `<span class="nasa">${language}</span>${ponctuation}&nbsp;`;
      $('#ncloc-language-distribution').append(span);
    });
    $('#files').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.files));
    $('#classes').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.classes));
    $('#functions').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.functions));

    $('#ncloc').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.ncloc));
    $('#lines').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.lines));

    $('#comment-lines').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.comment_lines));
    $('#comment-lines-density').html(new Intl.NumberFormat('fr-FR', { style: 'percent',maximumFractionDigits: 2 }).format(t.data.comment_lines_density/cent));

    $('#dette').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.sqale_index/soixante/soixante));
    $('#sqale-debt-ratio').html(new Intl.NumberFormat('fr-FR', { style: 'percent',maximumFractionDigits: 2 }).format(t.data.sqale_debt_ratio/cent));

    $('#coverage').html(new Intl.NumberFormat('fr-FR', { style: 'percent',maximumFractionDigits: 2 }).format(t.data.coverage/cent));
    $('#duplicated-lines-density').html(new Intl.NumberFormat('fr-FR', { style: 'percent',maximumFractionDigits: 2 }).format(t.data.duplicated_lines_density/cent));

    $('#tests').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.tests));
    $('#test-success-density').html(new Intl.NumberFormat('fr-FR', { style: 'percent',maximumFractionDigits: 2 }).format(t.data.test_success_density/cent));
    $('#skipped-tests').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.skipped_tests));
    $('#test-errors').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.test_errors));
    $('#test-failures').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.data.test_failures));

    /* historique */
    const t9 = document.getElementById('ncloc-language-distribution');

    const t10 = document.getElementById('files');
    const t11 = document.getElementById('classes');
    const t12 = document.getElementById('functions');

    const t13 = document.getElementById('ncloc');
    const t14 = document.getElementById('lines');

    const t15 = document.getElementById('comment-lines');
    const t16 = document.getElementById('comment-lines-density');

    const t17 = document.getElementById('coverage');
    const t18 = document.getElementById('duplicated-lines-density');

    const t19 = document.getElementById('dette');
    const t20 = document.getElementById('sqale-debt-ratio');

    const t21 = document.getElementById('tests');
    const t22 = document.getElementById('test-success-density');
    const t23 = document.getElementById('skipped-tests');
    const t24 = document.getElementById('test-errors');
    const t25 = document.getElementById('test-failures');

    t9.dataset.nclocLanguageDistribution=(t.data.ncloc_language_distribution);
    t10.dataset.files=(t.data.files);
    t11.dataset.classes=(t.data.classes);
    t12.dataset.functions=(t.data.functions);

    t13.dataset.ncloc=(t.data.ncloc);
    t14.dataset.lines=(t.data.lines);

    t15.dataset.commentLines=(t.data.comment_lines);
    t16.dataset.commentLinesDensity=(t.data.comment_lines_density);

    t17.dataset.coverage=(t.data.coverage);
    t18.dataset.duplicatedLinesDensity=(t.data.duplicated_lines_density);

    t19.dataset.dette=(t.data.sqale_index);
    t20.dataset.sqaleDebtRatio=(t.data.sqale_debt_ratio);

    t21.dataset.tests=(t.data.tests);
    t22.dataset.testSuccessDensity=(t.data.test_success_density);
    t23.dataset.skippedTests=(t.data.skipped_tests);
    t24.dataset.testErrors=(t.data.test_errors);
    t25.dataset.testFailures=(t.data.test_failures);
  });
});

/**
 * description
 * Ajouter/Enregistrement les données d'une analyse
*/
$('.js-enregistrer-analyse').on('click', ()=>{
  const selectionVersion=$('select[name="version"]').val();
  /** Si le projet n'a pas été sélectionné */
  if (selectionVersion==='TheID') {
    const message='Vous devez choisir un projet !';
    $('#message-ajout-projet').html(callboxError+message+callboxFermer);
    return;
  }

  /** Si le projet n'existe plus dans SonarQube 'error 404' */
  if ($('#js-message').hasClass('error')===true) {
    return;
  }

  const maven_key=$('#js-nom').data('maven').trim();
  const nom=$('#js-nom').text().trim();
  const version=$('#version').text().trim();

  const t1 = document.getElementById('date');
  const t2 = document.getElementById('reliability-rating');
  const t3 = document.getElementById('security-rating');
  const t4 = document.getElementById('sqale-rating');
  const t5 = document.getElementById('security-review-rating');
  const t6 = document.getElementById('violations');
  const t7 = document.getElementById('bugs');
  const t8 = document.getElementById('vulnerabilities');
  const t9 = document.getElementById('code-smells');
  const t10 = document.getElementById('security-hotspots');
  const t11 = document.getElementById('ncloc-language-distribution');
  const t12 = document.getElementById('files');
  const t13 = document.getElementById('classes');
  const t14 = document.getElementById('functions');
  const t15 = document.getElementById('ncloc');
  const t16 = document.getElementById('lines');
  const t17 = document.getElementById('comment-lines');
  const t18 = document.getElementById('comment-lines-density');
  const t19 = document.getElementById('coverage');
  const t20 = document.getElementById('duplicated-lines-density');
  const t21 = document.getElementById('dette');
  const t22 = document.getElementById('sqale-debt-ratio');
  const t23 = document.getElementById('tests');
  const t24 = document.getElementById('test-success-density');
  const t25 = document.getElementById('skipped-tests');
  const t26 = document.getElementById('test-errors');
  const t27 = document.getElementById('test-failures');

  /** On reformate la date pour avoir l'année en premier */
  const parsed_date = parse(t1.dataset.date, "dd-MM-yyyy HH:mm:ssXXX", new Date());
  const formatted_date = format(parsed_date, "yyyy-MM-dd HH:mm:ss");

  const nom_projet=nom;
  const date_version=formatted_date;
  const reliability_rating=t2.dataset.reliabilityRating;
  const security_rating=t3.dataset.securityRating;
  const sqale_rating=t4.dataset.sqaleRating;
  const security_review_rating=t5.dataset.securityReviewRating;
  const violations=t6.dataset.violations;
  const bugs=t7.dataset.bugs;
  const vulnerabilities=t8.dataset.vulnerabilities;
  const code_smells=t9.dataset.codeSmells;
  const security_hotspots=t10.dataset.securityHotspots;
  const ncloc_language_distribution=t11.dataset.nclocLanguageDistribution;
  const files=t12.dataset.files;
  const classes=t13.dataset.classes;
  const functions=t14.dataset.functions;
  const ncloc=t15.dataset.ncloc;
  const lines=t16.dataset.lines;
  const comment_lines=t17.dataset.commentLines;
  const comment_lines_density=t18.dataset.commentLinesDensity;
  const coverage=t19.dataset.coverage;
  const duplicated_lines_density=t20.dataset.duplicatedLinesDensity;
  const dette=t21.dataset.dette;
  const sqale_debt_ratio=t22.dataset.sqaleDebtRatio;
  const tests=t23.dataset.tests;
  const test_success_density=t24.dataset.testSuccessDensity;
  const skipped_tests=t25.dataset.skippedTests;
  const test_errors=t26.dataset.testErrors;
  const test_failures=t27.dataset.testFailures;
  const initial=0;

  const data={
    'maven_key': maven_key,
    'nom_projet': nom_projet,
    'version': version,
    'date_version': date_version,
    'reliability_rating': reliability_rating,
    'security_rating': security_rating,
    'sqale_rating': sqale_rating,
    'security_review_rating': security_review_rating,
    'violations': violations,
    'bugs': bugs,
    'vulnerabilities': vulnerabilities,
    'code_smells': code_smells,
    'security_hotspots': security_hotspots,
    'ncloc_language_distribution': ncloc_language_distribution,
    'files': files,
    'classes': classes,
    'functions': functions,
    'ncloc': ncloc,
    'lines': lines,
    'comment_lines': comment_lines,
    'comment_lines_density': comment_lines_density,
    'coverage': coverage,
    'duplicated_lines_density': duplicated_lines_density,
    'dette': dette,
    'sqale_debt_ratio': sqale_debt_ratio,
    'tests': tests,
    'test_success_density': test_success_density,
    'skipped_tests': skipped_tests,
    'test_errors': test_errors,
    'test_failures': test_failures,
    'initial': initial};

    /**
     * On lance l'API de mise à jour
     */
    const options = {
    url: `${serveur()}/api/suivi/mise-a-jour`, type: 'PUT',
    dataType: 'json', data: JSON.stringify(data), contentType };

    $.ajax(options).then(t => {
      let message='';
      if (t.code===http_200){
        message=`Enregistrement des informations effectué.`;
        $('#message-ajout-projet').html(callboxSuccess+message+callboxFermer);
      }

    if (t.code===23505){
      $('#message-ajout-projet').html(callboxWarning+t.message+callboxFermer);
    }
    if (t.code!==http_200 && t.code!==23505) {
        message=`Erreur lors de la mise à jour (${t.code}).`;
        $('#message-ajout-projet').html(callboxError+message);
        $('#message-ajout-projet').append(t.erreur+callboxFermer);
    }

  });
});

/**
 * description
 * Génère une edition PDF
*/
$('.lien-imprimer-pdf').on('click', async () => {
  const doc = new jsPDF({
      orientation: "portrait", // Mode paysage
      unit: "mm",
      format: "a4"
  });

  const pageWidth = 210;  // Largeur pour A4 en paysage
  const pageHeight = 297; // Hauteur pour A4 en paysage
  const margin = 10;      // Marge autour du contenu

  // En-tête
  function addHeader(pageNumber) {
      doc.setFontSize(16);
      doc.setTextColor(40);
      doc.text("Rapport de suivi des indicateurs SonarQube", margin, 20);
      doc.setFontSize(10);
      doc.setTextColor(100);
      doc.text(`Date : ${new Date().toLocaleDateString('fr-FR')}`, margin, 28);
  }

  // Pied de page
  function addFooter(pageNumber, pageCount) {
      doc.setFontSize(10);
      doc.setTextColor(100);
      doc.text(`Page ${pageNumber} / ${pageCount}`, pageWidth / 2, pageHeight - 10, { align: "center" });
  }

  // Fonction pour capturer et ajouter chaque élément
  async function captureAndAddElement(elementId, positionY) {
      const element = document.getElementById(elementId);
      if (!element) {
          sessionStorage.setItem('error', `L'élément ${elementId} n'a pas été trouvé !!!`);
          return 0; // Empêche l'appel à html2canvas si l'élément est absent
      }

      // Capture de l'élément avec html2canvas
      const canvas = await html2canvas(element);
      const imgData = canvas.toDataURL("image/png");
      const imgWidth = 195; // Largeur de la page A4 en mm
      const imgHeight = (canvas.height * imgWidth) / canvas.width;

      // Vérifier si l'image dépasse la page
      if (positionY + imgHeight > pageHeight - margin) {
          doc.addPage(); // Ajouter une nouvelle page si nécessaire
          positionY = 20; // Réinitialiser la position Y pour la nouvelle page
      }

      // Ajout de l'image capturée au PDF
      doc.addImage(imgData, "PNG", 10, positionY, imgWidth, imgHeight);
      return imgHeight;
  }


  // Séquence asynchrone pour capturer les éléments et les ajouter au PDF
  let positionY = 40; // Position de départ après l'en-tête
  addHeader(1);

  // Capture et ajout des éléments
  positionY += await captureAndAddElement('element1-to-print', positionY);
  positionY += await captureAndAddElement('element2-to-print', positionY + 10);
  positionY += await captureAndAddElement('element3-to-print', positionY + 10);
  positionY += await captureAndAddElement('element4-to-print', positionY + 10);

  // Nouvelle page pour `element5` et `element6`
  doc.addPage();
  positionY = 40; // Réinitialiser positionY pour démarrer en haut de la nouvelle page
  addHeader(2);
  positionY += await captureAndAddElement('element5-to-print', positionY);
  positionY += await captureAndAddElement('element6-to-print', positionY + 10);

  // Ajouter un texte de fin
  doc.setFontSize(14);
  doc.text("* * * *", pageWidth / 2, 280, { align: "center" });

  // Ajout de la numérotation des pages en bas de chaque page
  const pageCount = doc.getNumberOfPages();
  for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i);
      addFooter(i, pageCount);
  }

  // Récupération du nom
  const n = $('#js-nom').data('maven').trim();
  const name = n.split(':');

  // Enregistrer le PDF
  doc.save(`rapport_suivi_indicateurs_${name[1]}.pdf`);
});


/**
 * description
 * On affiche la liste des projets et on nettoie le formulaire
 */
$('.js-modifier-analyse').on('click', function () {
  const poubelle=`<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewbox="0 0 32 32"  class="poubelle-svg">
  title>Icône pour le bouton poubelle.</title>
  <desc>Supprime la version du suivi.</desc>
  <path d="M11.002.061c-.9.155-1.808.775-2.187 1.495-.26.49-.352.905-.352 1.6v.546l-2.054.03c-2.167.025-2.315.044-3.152.354C2.054 4.533.957 5.5.443 6.566c-.288.602-.359.944-.4 1.843L0 9.296H31.699l-.043-.887c-.042-.9-.112-1.24-.4-1.842-.514-1.067-1.612-2.034-2.815-2.48-.837-.31-.984-.33-3.144-.354l-2.061-.031v-.546c0-.298-.035-.688-.07-.875-.24-1.116-1.26-2.015-2.526-2.226-.464-.074-9.187-.074-9.638.006zm9.56 1.917c.423.192.563.483.563 1.178v.558H10.573v-.558c0-.682.14-.986.549-1.172.253-.118.422-.124 4.713-.13 4.327 0 4.46.006 4.728.124zM2.244 11.386c.021.13.443 4.242.943 9.135.956 9.414.95 9.333 1.364 9.916.45.62 1.267 1.11 2.076 1.247.513.087 17.931.087 18.445 0 .809-.137 1.625-.627 2.075-1.247.415-.583.408-.502 1.365-9.916.5-4.893.921-9.005.942-9.135l.029-.23H2.216zm7.977 1.755c.303.136.458.34.521.682.077.428.696 13.37.647 13.55-.07.249-.485.577-.788.627-.584.093-1.174-.28-1.252-.794-.042-.285-.675-12.707-.675-13.234 0-.732.809-1.166 1.547-.831zm6.12 0c.184.08.331.21.423.372.14.242.14.353.14 7.008 0 6.654 0 6.766-.14 7.008-.162.279-.563.496-.915.496-.351 0-.752-.217-.914-.496-.14-.242-.14-.354-.14-7.008 0-6.655 0-6.766.14-7.008.26-.453.872-.614 1.407-.372zm6.283.08c.464.31.464-.124.091 7.231-.183 3.672-.366 6.773-.4 6.903-.127.428-.697.732-1.225.645-.295-.05-.71-.385-.78-.626-.05-.18.57-13.123.647-13.551a.884.884 0 01.555-.695 1.192 1.192 0 011.112.093z"/>
  </svg>`;

  /* On récupère la clé maven */
  const data = { maven_key: $('#js-nom').data('maven') };

  const options = {
    url: `${serveur()}/api/suivi/version/liste`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  $.ajax(options).then(t => {

    /* On gère le résultat de la requête */
    if (t.code===http_200) {
      const message=`La liste des versions a été chargée correctement.`;
      $('#message').html(callboxInformation + message + callboxFermer);
    }

    if (t.code===http_400) {
      const message=`Je n'ai pas réussi à charger la liste des versions (${t.code}).`;
      $('#message').html(callboxError+message+callboxFermer);
    }

    /* On boucle pour construire le tableau */
    let ligne=0, html='', switchFavori='', switchReference='';
    $('#tableau-liste-version').html(html);
    t.versions.forEach(version => {
      ligne++;
      /* On défini le switch pour le favori */
      switchFavori='<div class="switch custom-switch-favori js-switch-favori">';
      switchFavori+=`<input class="switch-input" id="switch-favori-${ligne}" type="checkbox" name="switch-favori-${ligne}">`;
      switchFavori+=`<label class="switch-paddle" for="switch-favori-${ligne}">`;
      switchFavori+='<span class="show-for-sr">Projet favori</span>';
      switchFavori+='</label></div>';

      /* On défini le switch pour la référence */
      switchReference='<div class="switch custom-switch-reference js-switch-reference">';
      switchReference+=`<input class="switch-input" id="switch-reference-${ligne}" type="radio" name="switch-reference">`;
      switchReference+=`<label class="switch-paddle" for="switch-reference-${ligne}">`;
      switchReference+='<span class="show-for-sr">Projet de référence</span>';
      switchReference+='</label></div>';

      /*  On construit le tableau */
      const date_version=new Intl.DateTimeFormat('default', dateOptions).format(new Date(version.date));

      html  =`<tr id="ligne-${ligne}">`;
      html +=`<td id="poubelle-${ligne}" headers="modifier-preference action" class="text-left">${poubelle}</td>`;
      html +=`<td id="date-${ligne}" headers="modifier-preference date" class="text-left">${date_version}</td>`;
      html +=`<td id="version-${ligne}" headers="modifier-preference version" class="text-left">${version.version}</td>`;
      html +=`<td id="favori-${ligne}" headers="modifier-preference favori" class="text-left">${switchFavori}</td>`;
      html +=`<td id="reference-${ligne}" headers="modifier-preference reference" class="text-left">${switchReference}</td>`;
      html +='</tr>';

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
    });

    /* On gère le changement de favori pour la version du projet */
    $('[id^=switch-favori-]').on('click', e =>{
      if (t.preference_favori===false) {
        const message=`Vous n'avez pas activé les favoris dans vos préférences.`;
        $('#message').html(callboxWarning+message+callboxFermer);
        return;
      }

      /** on récupère la version et la date */
      const id=$(e.currentTarget).attr('id');
      const l=id.split('-');
      const version = $(`#version-${l[deux]}`).text().trim();
      const date = $(`#date-${l[deux]}`).text().trim();

      /** On reformate la date */
      const parsed_date = parse(date, "dd/MM/yyyy HH:mm:ss", new Date());
      const formatted_date = format(parsed_date, "yyyy-MM-dd HH:mm:ss");

      let favori=zero;
      if ($(`#${id}:checked`).length===un) {
        /** 0 (false) and 1 (true). */
        favori=un;
      }
        const maven_key=$('#js-nom').data('maven');
        /** On vérifie la clé maven */
        if (maven_key===undefined) {
          const message=`La clé maven n'est pas valide !`;
          $('#message').html(callboxError+message+callboxFermer);
          return;
        }

        const dataFavori = { maven_key, favori, version, date_version: formatted_date };

        const optionsFavori = {
          url: `${serveur()}/api/suivi/version/favori`, type: 'PUT',
          dataType: 'json', data: JSON.stringify(dataFavori), contentType };

        /**
         * On appel l'API de mise à jour du favori
         */
        $.ajax(optionsFavori).then((t) => {
          if (t.code===http_200) {
            const message=`Mise à jour du favori effectuée.`;
            $('#message').html(callboxSuccess+message+callboxFermer);
          } else if (t.code===http_201) {
            const message=`Cette version a été supprimé des favoris.`;
            $('#message').html(callboxWarning+message+callboxFermer);
          } else {
            const message=`Erreur lors de la mise à jour (${t.erreur}).`;
            $('#message').html(callboxError+message+callboxFermer);
          }
        });
    });

    /* On gère le changement de version de reference */
    $('[id^=switch-reference-]').on('click', e=>{
      /* on récupère la version et la date */
      const id=$(e.currentTarget).attr('id');
      const l=id.split('-');
      const version=$(`#version-${l[deux]}`).text().trim();
      const date=$(`#date-${l[deux]}`).text().trim();
      /** On reformate la date */
      const parsed_date = parse(date, "dd/MM/yyyy HH:mm:ss", new Date());
      const formatted_date = format(parsed_date, "yyyy-MM-dd HH:mm:ss");

      const maven_key=$('#js-nom').data('maven');

      let initial=zero;
      if ($(`#${id}:checked`).length===un){
        /** 0 (false) and 1 (true). */
        initial=un;
      }

      /** On vérifie la clé maven */
      if (maven_key===undefined) {
        const message=`La clé maven n'est pas valide !`;
        $('#message').html(callboxError+message+callboxFermer);
        return;
      }

      /**
       * On appel l'API de mise à jour de la version de référence
       */
      const dataReference = { maven_key, initial, version, date_version: formatted_date };
      const optionsReference = {
        url: `${serveur()}/api/suivi/version/reference`, type: 'PUT',
        dataType: 'json', data: JSON.stringify(dataReference), contentType };

      $.ajax(optionsReference).then((t) => {
      if (t.code===200) {
          const message='Mise à jour de la version de référence.';
          $('#message').html(callboxSuccess+message+callboxFermer);
        } else if (t.code===403) {
          const message=`Vous n'êtes pas autorisé à effectuer cette opération.`;
          $('#message').html(callboxWarning+message+callboxFermer);
        } else {
          const message=`Erreur lors de la mise à jour (${t.erreur}).`;
          $('#message').html(callboxError+message+callboxFermer);
        }
      });
    });

    /** On supprime la version du projet en table et on masque la ligne */
    $('[id^=poubelle-]').on('click', e=>{
      /* On récupère la version et la date */
      const id=$(e.currentTarget).attr('id');
      const l=id.split('-');
      const  version = $(`#version-${l[1]}`).text().trim();
      const  date = $(`#date-${l[1]}`).text().trim();
      /** On reformate la date */
      const parsed_date = parse(date, "dd/MM/yyyy HH:mm:ss", new Date());
      const formatted_date = format(parsed_date, "yyyy-MM-dd HH:mm:ss");

      /** On vérifie que la clé maven n'est pas null */
      let maven_key=$('#js-nom').data('maven');
      if (maven_key===undefined || maven_key=== null ) {
        maven_key='null';
      }

      /**
       * On l'API de suppression de la version dans l'historique
       */
      const dataPoubelle = { maven_key, version, 'date_version': formatted_date };
      const optionsPoubelle = {
        url: `${serveur()}/api/suivi/version/poubelle`, type: 'PUT',
        dataType: 'json', data: JSON.stringify(dataPoubelle), contentType };

      let message;
      $.ajax(optionsPoubelle).then((t) => {
        console.log(t);
        switch (t.code) {
          case 200 :
            message=`Le projet a été correctement supprimé. ${t.message}`;
            $('#message').html(callboxSuccess+message+callboxFermer);
            // On masque la ligne
            $('#ligne-'+l[1]).hide();
            break;
          case 202:
            message=`Le projet n'a pas été supprimé ! `;
            $('#message').html(callboxSuccess+message+`(${t.erreur}).`+callboxFermer);
          break;
          case 400:
            message='La clé maven est vide !';
            $('#message').html(callboxError+message+callboxFermer);
            break;
          case 403:
            message=`Vous n'êtes pas autorisé à effectuer cette opération.`;
            $('#message').html(callboxWarning+message+callboxFermer);
            break;
          default:
            message=`Le projet n'a pas été supprimé ! `;
            $('#message').html(callboxError+message+`(${t.erreur}).`+callboxFermer);
        }
      });
    });
  });

  /** fin de la méthode on ouvre la fenêtre */
  $('#modal-modifier-analyse').foundation('open');
});
