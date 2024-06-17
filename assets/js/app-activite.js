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
import '../css/activite.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

// On importe les paramètres serveur
import {serveur} from './properties.js';

import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import zoomPlugin from 'chartjs-plugin-zoom';
Chart.register(ChartDataLabels);
Chart.register(zoomPlugin);

import moment from 'moment';
import 'moment/locale/fr';
import 'chartjs-adapter-moment';

/* Initialisation de moments */
const a= moment().toString();
/* Pour éviter d'avoir une erreur sonar */
console.info(a);

/** On importe les constantes */
import {http_200, http_202, http_400, http_403, contentType} from './constante.js';

import './foundation.js';

/* Construction des callbox de type success */
const callboxInformation='<div id="js-message" class="callout alert-callout-border primary" data-closable="slide-out-right" role="alert"><p class="open-sans color-bleu padding-right-1"><span class="lead">Information ! </span>';
const callboxSuccess='<div id="js-message" class="callout alert-callout-border success" data-closable="slide-out-right" role="alert"><span class="open-sans color-bleu padding-right-1"><span class="lead">Bravo ! </span> ';
const callboxWarning='<div id="js-message" class="callout alert-callout-border warning" data-closable="slide-out-right" role="alert"><span class="open-sans padding-right-1 color-bleu"><span class="lead">Attention ! </strong>';
const callboxError='<div id="js-message" class="callout alert-callout-border alert" data-closable="slide-out-right"><span class="open-sans padding-right-1 color-bleu"><strong>Oups ! </strong>';
const callboxFermer='</span><button class="close-button" aria-label="Fermer la fenêtre" type="button" data-close><span aria-hidden="true">&times;</span></button></div>';

const refreshActivite=async function() {
  const optionsRefresh = {
        url: `${serveur()}/api/activite/sauvegarde`, type: 'POST',
        dataType: 'json', contentType };
  /** On appel l'API */
  const t = await $.ajax(optionsRefresh);
  let message='';

  switch (t.code) {
    case http_403:
      message=`Vous n'êtes pas autorisé à effectuer cette opération.`;
      $('#message').html(callboxWarning+message+callboxFermer);
      return;
    default:
      break;
  }
  let str ='';

  /** On efface le container */
  const stats = t.listeDonnee.request;
  $('#js-date-enregistrement').html('');
  str += `<p><strong>Derniere date d'enregistrement : </strong><span class="couleur-changement">${stats[0].date_enregistrement}</span></p>`;
  $('#js-date-enregistrement').html(str);
  $('#js-tableau-stats').html('');

  str ='';

  stats.forEach(stat =>{
    str +=
    `<tr>
    <td>${ stat.annee }</td>
    <td>${ stat.nb_jour }</td>
    <td>${ stat.nb_analyse }</td>
    <td>${ stat.moyenne_analyse }</td>
    <td>${ stat.nb_reussi }</td>
    <td>${ stat.nb_echec }</td>
    <td>${ stat.taux_reussite } %</td>
    <td>${ stat.max_temps }</td>
  </tr>`;
  })
  $('#js-tableau-stats').html(str);
}

$('.js-activite-refresh').on('click', ()=>{
  refreshActivite();
});

const graphToto = function(type, donnee, source) {
  let datasets = [];
  const la = [];


  if (source === 'projet_analyse') {
    const analyse = [];
    const projet = [];
    donnee.forEach(element => {
      la.push(element.day);
      analyse.push(element.analyse);
      projet.push(element.projet);
    });
    datasets.push({
      label: 'Analyse',
      data: analyse,
      borderColor: getRandomColor(),
      backgroundColor: transparentize(getRandomColor(), 0.5),
      yAxisID: 'y',
    });
    datasets.push({
      label: 'Projet',
      data: projet,
      borderColor: getRandomColor(),
      backgroundColor: transparentize(getRandomColor(), 0.5),
      yAxisID: 'y',
    });

  } else {
    const da = [];
    donnee.forEach(element => {
      la.push(element.day);
      da.push(element.count);
    });
    datasets.push({
      label: source,
      data: da,
      borderColor: getRandomColor(),
      backgroundColor: transparentize(getRandomColor(), 0.5),
      yAxisID: 'y',
    });
  }

  const formattedLabels = la.map(date => moment(date).format('DD-MMMM').slice(0, 6));

  function getRandomColor() {
    const r = Math.floor(Math.random() * 256);
    const g = Math.floor(Math.random() * 256);
    const b = Math.floor(Math.random() * 256);
    return `rgb(${r}, ${g}, ${b})`;
  }

  function transparentize(color, opacity) {
    const alpha = opacity === undefined ? 0.5 : 1 - opacity;
    return color.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
  }

  const data = {
    labels: formattedLabels,
    datasets: datasets
  };

  const options = {
    responsive: true,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    stacked: false,
    plugins: {
      title: {
        display: true,
        text: 'Chart.js Graphique linéaire'
      }
    },
    scales: {
      y: {
        type: 'linear',
        display: true,
        position: 'left',
      },
      y1: {
        type: 'linear',
        display: true,
        position: 'right',
        grid: {
          drawOnChartArea: false,
        },
      },
    }
  };

  const chartStatus = Chart.getChart('graphique-langage');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-langage').getContext('2d');
  const charts = new Chart(ctx, { type: 'line', data, options });
  if (charts === null) {
    sessionStorage.setItem('info','youpi ! charts ne peut pas être null !!!');
  }
}

const dessineGraph = async function(type, source) {
  const dataRefresh = { source: source };
  const optionsRefresh = {
    url: `${serveur()}/api/activite/dessin`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(dataRefresh),
    contentType
  };

  const t = await $.ajax(optionsRefresh);

  const donnee = t.listeDonnee.request;
  graphToto(type, donnee, source);
}

$('.js-show-graph').on('click', (e) => {
  const target = e.currentTarget.id;
  const elm = document.getElementById(target);
  const source = elm.dataset.source;
  const type = elm.dataset.type;
  dessineGraph(type, source);
});
