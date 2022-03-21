/*
 * Copyright (c) 2021-2022.
 * Laurent HADJADJ <laurent_h@me.com>.
 * Licensed Creative Common  CC-BY-NC-SA 4.0.
 * Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 * http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

import '../css/dash.css';

// Intégration de jquery
// eslint-disable-next-line no-unused-vars
import $ from 'jquery';

import 'select2';
import 'select2/dist/js/i18n/fr.js';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
// eslint-disable-next-line no-unused-vars
import moment from "moment";
import "moment/locale/fr";
import "chartjs-adapter-moment";
Chart.register(ChartDataLabels);

const CHART_COLORS = {
  rouge: 'rgb(255, 99, 132)',
  rouge_opacity: 'rgb(255, 99, 132, 0.5)',
  bleu: 'rgb(54, 162, 235)',
  bleu_opacity: 'rgb(54, 162, 235, 0.5)',
  orange: 'rgb(170,	102,	51)',
  orange_opacity: 'rgb(170,	102, 51, 0.5)',
};

const contentType='application/json; charset=utf-8';

/**
 * Affiche le graphique des sources
 */
function dessineMoiUnMouton( labels, data1, data2, data3) {
  const data = {
    labels: labels,
    datasets: [{
      label: 'Bug',
      pointBorderColor: '#00445b',
      pointBackgroundColor: '#00445b',
      borderWidth: 2,
      radius: 0,
      data: data1,
      fill: true,
      borderColor: CHART_COLORS.orange,
      backgroundColor: CHART_COLORS.orange_opacity,
      tension: 0.2,
    },
    {
      label: 'Vulnérabilité',
      pointBorderColor: '#C64444',
      pointBackgroundColor: '#C64444',
      borderWidth: 2,
      radius: 0,
      data: data2,
      fill: true,
      borderColor: CHART_COLORS.rouge,
      backgroundColor: CHART_COLORS.rouge_opacity,
      tension: 0.2,
    },
    {
      label: 'Mauvaise pratique',
      pointBorderColor: '#C64444',
      pointBackgroundColor: '#C64444',
      borderWidth: 2,
      radius: 0,
      data: data3,
      fill: true,
      borderColor: CHART_COLORS.bleu,
      backgroundColor: CHART_COLORS.bleu_opacity,
      tension: 0.2,
    },
    ]
  };
const options = {
  animations: { radius: { duration: 400, easing: 'linear', } },
    maintainAspectRatio: true,
    responsive: true,
    layout: {
      padding: {
          left: 20
      }},
    scales: {
      x: {
          type: 'time',
          time: {
            unit: 'day',
            unitStepSize: 1,
            displayFormats: { 'day': 'YYYY MMM DD' },
          },
          display: true,
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
      datalabels: {
        display: true,
        align: 'end', anchor: 'right',
        color: '#000',
        font: function (context) {
          const w = context.chart.width;
          return { size: w < 512 ? 11 : 12, weight: 'bold', };
        },
      }
    }};

  const chartStatus = Chart.getChart("graphique-anomalie");
  if (chartStatus !== undefined) { chartStatus.destroy(); }

  const ctx = document.getElementById('graphique-anomalie').getContext('2d');
  const charts = new Chart(ctx, { type: 'line', data: data, options: options });
  if (charts === null) { console.log(); }
}


// On récupère les datatset
const data_attribut = document.getElementById('graphique-anomalie');
const _data1= data_attribut.dataset.data1;
const _data2= data_attribut.dataset.data2;
const _data3= data_attribut.dataset.data3;
const _labels= data_attribut.dataset.label;


dessineMoiUnMouton(Object.values(JSON.parse(_labels)), Object.values(JSON.parse(_data1)), Object.values(JSON.parse(_data2)), Object.values(JSON.parse(_data3)));

/**
 * description
 * Création du selecteur de projet.
 */
 function select_version(maven_key) {
  const data={ maven_key: maven_key}
  const options = {
    url: 'http://localhost:8000/api/liste/version', type: 'GET', dataType: 'json',
    data: data, contentType: contentType }

  return $.ajax(options)
    .then(function (r) {
      $('.js-version').select2({
        placeholder: 'Cliquez pour ouvrir la liste',
        allowClear: true,
        selectOnClose: true,
        width: '100%',
        minimumResultsForSearch: 5,
        language: "fr",
        data: r.liste,
      });
      $('.analyse').removeClass('hide');
    })
}

/**
 * description
 * On affiche la liste des projets
 */
 $('.js-ajouter-analyse').on('click', function () {
 const maven_key=$("#js-nom").data('maven');
 select_version(maven_key);
 $('#modal-ajouter-analyse').foundation('open');
})

/**
 * description
 * On charge les données
 */
$('select[name="version"]').change(function () {
  // On affiche la clé
  $('#key-maven').html($("#js-nom").data('maven').trim());
  // On affiche le nom
  const n=$("#js-nom").data('maven').trim();
  const name=n.split(':');
  $('#nom').html(name[1]);

  // On récupère la date et l'a nettoie avant de l'envoyer
  const d=$('#liste-version :selected').text();
  const d1=d.split('(');
  const d2=d1[1].split(')');
  const d3=d2[0].split('+')
  // On affiche la version
  $('#version').html(d1[0]);
  // On affiche la date
  $('#date').html(d3[0]);

  const data = { maven_key: $('#key-maven').text().trim(), date:d2[0] }
  const options = {
    url: 'http://localhost:8000/api/get/version', type: 'PUT', dataType: 'json', data: JSON.stringify(data), contentType: contentType
  }

  $.ajax(options).then((t) => {
    let t_notes = ['', 'A', 'B', 'C', 'D', 'E'], couleur1, couleur2, couleur3 = '';
    if (t.note_reliability === 1 ) { couleur1 = 'vert1'; }
    if (t.note_security === 1) { couleur2 = 'vert1'; }
    if (t.note_sqale === 1) { couleur3 = 'vert1'; }

    if (t.note_reliability === 2) { couleur1 = 'vert2'; }
    if (t.note_security === 2) { couleur2 = 'vert2'; }
    if (t.note_sqale === 2) { couleur3 = 'vert2'; }

    if (t.note_reliability === 3) { couleur1 = 'jaune'; }
    if (t.note_security === 3) { couleur2 = 'jaune'; }
    if (t.note_sqale === 3) { couleur3 = 'jaune'; }

    if (t.note_reliability === 4) { couleur1 = 'orange'; }
    if (t.note_security === 4) { couleur2 = 'orange'; }
    if (t.note_sqale === 4) { couleur3 = 'orange'; }

    if (t.note_reliability === 5) { couleur1 = 'rouge'; }
    if (t.note_security === 5) { couleur2 = 'rouge'; }
    if (t.note_sqale === 5) { couleur3 = 'rouge'; }

    const note_reliability = t_notes[parseInt(t.note_reliability,10)];
    const note_security = t_notes[parseInt(t.note_security,10)];
    const note_sqale = t_notes[parseInt(t.note_sqale,10)];

    $('#note-reliability').html('<span class="' + couleur1 + '">' + note_reliability + '</span>');
    $('#note-security').html('<span class="' + couleur2 + '">' + note_security + '</span>');
    $('#note-sqale').html('<span class="' + couleur3 + '">' + note_sqale + '</span>');


  });

})
