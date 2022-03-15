/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */
/* eslint-disable jquery/no-ready */
/* eslint-disable jquery/no-class */
/* eslint-disable jquery/no-show */
/* eslint-disable jquery/no-hide */

import '../css/dash.css';

// Intégration de jquery
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
//import moment from "moment";
import "moment/locale/fr";
import "chartjs-adapter-moment";
Chart.register(ChartDataLabels);



const contentType = 'application/json; charset=utf-8';

const CHART_COLORS = {
  rouge: 'rgb(255, 99, 132)',
  rouge_opacity: 'rgb(255, 99, 132, 0.5)',
  bleu: 'rgb(54, 162, 235)',
  bleu_opacity: 'rgb(54, 162, 235, 0.5)',
  orange: 'rgb(170,	102,	51)',
  orange_opacity: 'rgb(170,	102, 51, 0.5)',
};

/**
 * Affiche le graphique des sources
 */
function dessineMoiUnMouton( labels, data1, data2, data3) {
  let data = {
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
let options = {
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
          var w = context.chart.width;
          return { size: w < 512 ? 11 : 12, weight: 'bold', };
        },
      }
    }};

  let chartStatus = Chart.getChart("graphique-anomalie");
  if (chartStatus != undefined) { chartStatus.destroy(); }

  let ctx = document.getElementById('graphique-anomalie').getContext('2d');
  let charts = new Chart(ctx, { type: 'line', data: data, options: options });
  if (charts === null) { console.log()};
}


// On récupère les datatset
const data_attribut = document.getElementById('graphique-anomalie');
const _data1= data_attribut.dataset.data1;
const _data2= data_attribut.dataset.data2;
const _data3= data_attribut.dataset.data3;
const _labels= data_attribut.dataset.label;


dessineMoiUnMouton(Object.values(JSON.parse(_labels)), Object.values(JSON.parse(_data1)), Object.values(JSON.parse(_data2)), Object.values(JSON.parse(_data3)));
