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

import '../css/suivi.css';

/* Intégration de jquery */
import $ from 'jquery';

import 'select2';
import 'select2/dist/js/i18n/fr.js';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import html2pdf from 'html2pdf.js';

import './foundation.js';
import './app-authentification-details.js';

/** On importe les paramètres serveur */
import {serveur} from './properties.js';

/* Gestion des graphiques */
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

import moment from 'moment';
import 'moment/locale/fr';
import 'chartjs-adapter-moment';
Chart.register(ChartDataLabels);


/* Initialisation de moments */
const a= moment().toString();
/* Pour éviter d'avoir une erreur sonar */
console.info(a);

/** On importe les constantes */
import { contentType, http_200, http_201, http_202, http_400, http_404, chartColors, zero, un, deux, soixante, cent } from './constante.js';

/* Construction des callbox de type success */
const callboxInformation='<div id="js-message" class="callout alert-callout-border primary" data-closable="slide-out-right" role="alert"><p class="open-sans color-bleu padding-right-1"><span class="lead"></span>Information ! </strong>';
const callboxSuccess='<div id="js-message" class="callout alert-callout-border success" data-closable="slide-out-right" role="alert"><span class="open-sans color-bleu padding-right-1"<span class="lead">Bravo ! </span>';
const callboxWarning='<div id="js-message" class="callout alert-callout-border warning" data-closable="slide-out-right" role="alert"><span class="open-sans padding-right-1 color-bleu"><span class="lead">Attention ! </span>';
const callboxError='<div id="js-message" class="callout alert-callout-border alert" data-closable="slide-out-right"><span class="open-sans padding-right-1 color-bleu"><span class="lead">Oups ! </span>';
const callboxFermer='</span><button class="close-button" aria-label="Fermer la fenêtre" type="button" data-close><span aria-hidden="true">&times;</span></button></div>';

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
  const data = {
    labels,
    datasets: [{
      label: 'Bug',
      pointBorderColor: '#00445b',
      pointBackgroundColor: '#00445b',
      borderWidth: 2,
      radius: 0,
      data: data1,
      fill: true,
      borderColor: chartColors.orange,
      backgroundColor: chartColors.orangeOpacity,
      tension: 0.2 },
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
      tension: 0.2 },
    {
      label: 'Mauvaise pratique',
      pointBorderColor: '#C64444',
      pointBackgroundColor: '#C64444',
      borderWidth: 2,
      radius: 0,
      data: data3,
      fill: true,
      borderColor: chartColors.bleu,
      backgroundColor: chartColors.bleuOpacity,
      tension: 0.2}]};
  const options = {
    aspectRatio:3,
    animations: { radius: { duration: 400, easing: 'linear' } },
    maintainAspectRatio: true,
    responsive: true,
    layout: {
        padding: { left: 20, top: 20 }},
        scales: {
        x: {
            type: 'time',
            time: {
              unit: 'day',
              unitStepSize: 1,
              displayFormats: { 'day': 'YYYY MMM DD' } },
            display: true },
        y: {
          display: true,
          type: 'logarithmic',
          position: 'right',
          title: { display: true, text: 'Violations', color: '#00445b' },
          ticks: { color: '#00445b' }}
      },
      plugins: {
        tooltip: { enabled: false },
        legend: { position: 'bottom' },
        datalabels: {
          display: true,
          align: 'end', anchor: 'right',
          color: '#000',
          font: function (context) {
            const w = context.chart.width;
            return { size: w < 512 ? 11 : 12, weight: 'bold'};
          }
        }
    }};

  const chartStatus = Chart.getChart('graphique-anomalie');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-anomalie').getContext('2d');
  const charts = new Chart(ctx, { type: 'line', data, options });
  if (charts === null) {
    console.info('null');
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

/** je ne sais pas d'ou cela sort ! */
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
    url: `${serveur()}/api/liste/version`, type: 'POST',
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
  const d1=d.split('(');
  const d2=d1[1].split(')');
  const d3=d2[0].split('+');
  const t0 = document.getElementById('date');
  t0.dataset.date=(d3[0]);

  /* On affiche la version */
  $('#version').html(d1[0]);
  /* On affiche la date */
  $('#date').html(d3[0]);

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
      const message=`La requête est incorrecte (erreur 400).`;
      $('#message-ajout-projet').html(callboxError+message+callboxFermer);
      return;
    }

    if (t.code===http_404) {
      const message=`Le projet n'existe plus sur le serveur SonarQube ! (erreur 406)`;
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
 * Ajouter/Enregistrement les données
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

  const nom_projet=nom;
  const date_version=t1.dataset.date;
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
    'maven_key':maven_key,
    'nom_projet':nom_projet,
    'version':version,
    'date_version':date_version,
    'reliability_rating':reliability_rating,
    'security_rating':security_rating,
    'sqale_rating':sqale_rating,
    'security_review_rating':security_review_rating,
    'violations':violations,
    'bugs':bugs,
    'vulnerabilities':vulnerabilities,
    'code_smells':code_smells,
    'security_hotspots':security_hotspots,
    'ncloc_language_distribution':ncloc_language_distribution,
    'files':files,
    'classes':classes,
    'functions':functions,
    'ncloc':ncloc,
    'lines':lines,
    'comment_lines':comment_lines,
    'comment_lines_density':comment_lines_density,
    'coverage':coverage,
    'duplicated_lines_density':duplicated_lines_density,
    'dette':dette,
    'sqale_debt_ratio':sqale_debt_ratio,
    'tests':tests,
    'test_success_density':test_success_density,
    'skipped_tests':skipped_tests,
    'test_errors':test_errors,
    'test_failures':test_failures,
    'initial':initial};
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
$('.lien-editer').on('click', ()=>{
  const date2 = new Date();
  const element1 = document.getElementById('element1-to-print');
  const element2 = document.getElementById('element2-to-print');
  const element3 = document.getElementById('element3-to-print');

  /* On récupère le nom */
  const n=$('#js-nom').data('maven').trim();
  const name=n.split(':');

  const opt = {
    margin:    10,
    filename:  `${name[1]}-suivi-${date2.toLocaleDateString('fr-FR')}.pdf`,
    image:      { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2 },
    putOnlyUsedFonts:true,
    pagebreak:    { mode: 'avoid-all'},
    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }};

  const debut=`<h1 class="claire-hand">Rapport de suivi des indicateurs.</h1>
                <p class="open-sans">Date : ${date2.toLocaleDateString('fr-FR')}</p><br />`;
  const fin='<br /><br /><p class="open-sans text-center" style="font-size:4rem;">* * * *</p>';
  const tempo=debut+element1.innerHTML+element2.innerHTML+element3.innerHTML+fin;
  html2pdf().set(opt).from(tempo).toPdf().get('pdf').save();
});

/**
 * description
 * On affiche la liste des projets et on nettoie le formulaire
 */
$('.js-modifier-analyse').on('click', function () {
  const poubelle=`<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewbox="0 0 512 512" class="poubelle-svg" preserveAspectRatio="xMidYMid meet">
      <g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)" stroke="none">
      <path d="M1871 5109 c-128 -25 -257 -125 -311 -241 -37 -79 -50 -146 -50 -258 l0 -88 -292 -5 c-308 -4 -329 -7 -448 -57 -171 -72 -327 -228 -400 -400 -41 -97 -51 -152 -57 -297 l-6 -143 2253 0 2253 0 -6 143 c-6 145 -16 200 -57 297-73 172 -229 328 -400 400 -119 50 -140 53 -447 57 l-293 5 0 88 c0 48 -5 111 -10 141 -34 180 -179 325 -359 359 -66 12 -1306 12 -1370 -1z m1359 -309 c60 -31 80 -78 80 -190 l0 -90 -750 0 -750 0 0 90 c0 110 20 159 78 189 36 19 60 20 670 21 615 0 634 -1 672 -20z"/> <path d="M626 3283 c3 -21 63 -684 134 -1473 136 -1518 135 -1505 194 -1599 64 -100 180 -179 295 -201 73 -14 2549 -14 2622 0 115 22 231 101 295 201 59 94 58 81 194 1599 71 789 131 1452 134 1473 l4 37 -1938 0 -1938 0 4 -37z m1134 -283 c43 -22 65 -55 74 -110 11 -69 99 -2156 92 -2185 -10 -40 -69 -93 -112 -101 -83 -15 -167 45 -178 128 -6 46 -96 2049 -96 2134 0 118 115 188 220 134z m870 0 c26 -13 47 -34 60 -60 20 -39 20 -57 20 -1130 0 -1073 0 -1091 -20 -1130 -23 -45 -80 -80 -130 -80 -50 0 -107 35 -130 80 -20 39 -20 57 -20 1130 0 1073 0 1091 20 1130 37 73 124 99 200 60z m893 -13 c66 -50 66 20 13 -1166 -26 -592 -52 -1092 -57 -1113 -18 -69 -99 -118 -174 -104 -42 8 -101 62 -111 101 -7 29 81 2116 92 2185 9 54 35 91 79 112 52 25 114 19 158 -15z"/>
      </g>
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
      $('#message').html(callboxInformation+message+callboxFermer);
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
      switchFavori='<div class="siwtch custom-switch-favori js-switch-favori">';
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
      html  =`<tr id="ligne-${ligne}">`;
      html +=`<td id="poubelle-${ligne}" headers="modifier-preference action" class="text-left">${poubelle}</td>`;
      html +=`<td id="date-${ligne}" headers="modifier-preference date" class="text-left">${version.date}</td>`;
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
    console.log(t.preference_favori);
    /* On gère le changement de favori */
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

      let favori=zero;
      if ($(`#${id}:checked`).length===un) {
        /** SQLite : 0 (false) and 1 (true). */
        favori=un;
      }
        const mavenKey=$('#js-nom').data('maven');
        /** On vérifie la clé maven */
        if (mavenKey===undefined) {
          const message=`La clé maven n'est pas valide !`;
          $('#message').html(callboxError+message+callboxFermer);
          return;
        }

        const dataFavori = { maven_key: mavenKey, favori, version, date_version: date };
        const optionsFavori = {
          url: `${serveur()}/api/suivi/version/favori`, type: 'PUT',
          dataType: 'json', data: JSON.stringify(dataFavori), contentType };
        /**
         * On appel l'API de mise à jour du favori
         */
        $.ajax(optionsFavori).then((t) => {
          if (t.code===http_200) {
            const message='Mise à jour du favori effectuée.';
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

    /* On gère le changement de reference */
    $('[id^=switch-reference-]').on('click', e=>{
      /* on récupère la version et la date */
      const id=$(e.currentTarget).attr('id');
      const l=id.split('-');
      const version=$(`#version-${l[deux]}`).text().trim();
      const date=$(`#date-${l[deux]}`).text().trim();
      const mavenKey=$('#js-nom').data('maven');

      let initial=zero;
      if ($(`#${id}:checked`).length===un){
        /** SQLite : 0 (false) and 1 (true). */
        initial=un;
      }

      /** On vérifie la clé maven */
      if (mavenKey===undefined) {
        const message=`La clé maven n'est pas valide !`;
        $('#message').html(callboxError+message+callboxFermer);
        return;
      }

      /**
       * On appel l'API de mise à jour de la version de référence
       */
      const dataReference = { maven_key: mavenKey, initial, version, date_version: date, mode:'null' };
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

      /** On vérifie que la clé maven n'est pas null */
      let mavenKey=$('#js-nom').data('maven');
      if (mavenKey===undefined || mavenKey=== null ) {
        mavenKey='null';
      }

      /**
       * On l'API de suppression de la version dans l'historique
       */
      const dataPoubelle = { 'maven_key': mavenKey, version, 'date_version':date, 'mode': 'null' };
      const optionsPoubelle = {
        url: `${serveur()}/api/suivi/version/poubelle`, type: 'PUT',
        dataType: 'json', data: JSON.stringify(dataPoubelle), contentType };

      let message;
      $.ajax(optionsPoubelle).then((t) => {
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
