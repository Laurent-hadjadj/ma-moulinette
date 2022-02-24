/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

//require '/ressources/js/indexedDB.js'
Chart.register(ChartDataLabels);

const matrice = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31];

/**
 * liste des couleurs pour les courbes
 */
const CHART_COLORS = {
  rouge: 'rgb(255, 99, 132)',
  rouge_opacity: 'rgb(255, 99, 132, 0.5)',
  bleu: 'rgb(54, 162, 235)',
  bleu_opacity: 'rgb(54, 162, 235, 0.5)',
};

/**
 * description
 * Supprime un graphique
 */
function destroy(value, open) {
  let chart_status = Chart.getChart(value);
  if (chart_status === undefined) { return; }
  if (chart_status != undefined && open == 'true') { chart_status.destroy(); return; }
  if (chart_status != undefined && open == 'false') { chart_status.destroy(); $('#' + value).remove(); return; }
}

/**
 * description
 * Mélangeur de couleur
 */
function shuffle(a) {
  var j, x, i;
  for (i = a.length - 1; i > 0; i--) {
    j = Math.floor(Math.random() * (i + 1));
    x = a[i];
    a[i] = a[j];
    a[j] = x;
  }
  return a;
}

/**
 * description
 * On mélange les couleurs
 */
shuffle(matrice);

const palette_couleur = [
  '#065535', '#133337', '#000000', '#ffc0cb', '#008080', '#ff0000', '#ffd700', '#666666',
  '#ff7373', '#fa8072', '#800080', '#800000', '#003366', '#333333', '#20b2aa', '#ffc3a0',
  '#f08080', '#66cdaa', '#f6546a', '#ff6666', '#468499', '#c39797', '#bada55', '#ff7f50',
  '#660066', '#008000', '#088da5', '#808080', '#8b0000', '#0e2f44', '#3b5998', '#cc0000'
];

/**
 * description
 * Renvoie une nouvelle palette de couleur
 */
function palette() {
  let nouvelle_palette = [];
  shuffle(matrice);
  matrice.forEach((el) => { nouvelle_palette.push(palette_couleur[el]); });
  return nouvelle_palette;
}

/**
 * description
 * dessine un graph
 */
function dessineMoiUnMouton(id, type, data, options) {
  let ctx = document.getElementById(id).getContext('2d');
  let chart = new Chart(ctx, { type: type, data: data, options: options });
}

let fiabilite, securite, mainetnaibilite, dataset1 = [], dataset2 = [], dataset3 = [], dataset4 = [];
fiabilite = [2, 10, 23, 80]; //B,C,M,m d1[2,] d2[10, ] d3[23,] d4[80]
securite = [4, 2, 14, 180]; //B,C,M,m
maintenabilite = [100, 43, 225, 1000]; //B,C,M,m
dataset1[0] = fiabilite[0]; //bloquant
dataset2[0] = fiabilite[1]; //critique
dataset3[0] = fiabilite[2]; //majeur
dataset4[0] = fiabilite[3]; //mineur
dataset1[1] = securite[0]; //bloquant
dataset2[1] = securite[1]; //critique
dataset3[1] = securite[2]; //majeur
dataset4[1] = securite[3]; //mineur
dataset1[2] = maintenabilite[0]; //bloquant
dataset2[2] = maintenabilite[1]; //critique
dataset3[2] = maintenabilite[2]; //majeur
dataset4[2] = maintenabilite[3]; //mineur

function graph01() {
  let monApplication = "Lilmod-Lelamed v2.0.1-Release";
  let nouvelle_palette = palette();
  let data = {
    labels: ['Fiabilité', 'Sécurité', 'Maintenabilité'],
    datasets: [
      {
        label: 'Bloquant',
        data: dataset1,
        backgroundColor: nouvelle_palette,
        borderWidth: 1,
        datalabels: { align: 'top', anchor: 'end' }
      },
      {
        label: 'Critique',
        data: dataset2,
        backgroundColor: nouvelle_palette,
        borderWidth: 1,
        datalabels: { align: 'top', anchor: 'end' }
      },
      {
        label: 'Majeur',
        data: dataset3,
        backgroundColor: nouvelle_palette,
        borderWidth: 1,
        datalabels: { align: 'top', anchor: 'end' }
      },
      {
        label: 'Mineur',
        data: dataset4,
        backgroundColor: nouvelle_palette,
        borderWidth: 1,
        datalabels: { align: 'top', anchor: 'end' }
      },
    ]
  };
  let options = {
    maintainAspectRatio: false,
    responsive: true,
    drawOnChartArea: true,
    animations: { y: { duration: 2000, easing: 'linear', loop: false } },
    scales: {
      x: { display: true, stacked: false, title: { display: true, text: 'Critère de qualité', color: '#00445b' }, },
      y: { display: true, stacked: false, title: { display: true, text: 'Nombre de défaut', color: '#00445b' }, }
    },
    plugins: {
      title: { display: true, text: monApplication, font: { size: 24, family: 'open-sans' }, color: '#00445b' },
      tooltip: { enabled: false },
      legend: { display: false, },
      datalabels: {
        align: 'end', anchor: 'end',
        color: '#000',
        font: function (context) {
          var w = context.chart.width;
          return { size: w < 512 ? 11 : 12, weight: 'bold', };
        },
      }
    }
  };
  dessineMoiUnMouton('graph01', 'bar', data, options);
}

/**
 * Affiche le graphique des sources
 */
function graph02() {
  let monApplication = "Lilmod-Lelamed v2.0.1-Release";
  //let nouvelle_palette=palette();

  let data = {
    labels: ['Fiabilité', 'Sécurité', 'Maintenabilité'],
    datasets: [
      { data: [30, 10, 140], backgroundColor: ['#5b160a', '#8C5B53', '#AD8A84', '#CDB9B5'] },
      { data: [441, 14, 27], backgroundColor: ['#104054', '#577987', '#879FA9', '#CFD8DC'] },
      { data: [12, 18, 0], backgroundColor: ['#615b81', '#908CA6', '#B0ADC0', '#CFCDD9'] },
      { data: [520, 20, 200], backgroundColor: ['#615b81', '#908CA6', '#B0ADC0', '#CFCDD9'] },
    ]
  };

  let options = {
    animations: {
      tension: { duration: 2000, easing: 'linear', loop: false }
    },
    maintainAspectRatio: true,
    responsive: true,
    plugins: {
      title: { display: true, text: monApplication, font: { size: 24, family: 'claire-hand' }, color: '#00445b' },
      tooltip: { enabled: true },
      legend: {
        labels: {
          generateLabels: function (chart) {
            const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
            const labelsOriginal = original.call(this, chart);
            console.log('labelsOriginal: ', labelsOriginal);
            labelsOriginal[0].fillStyle = '#5b160a';
            labelsOriginal[1].fillStyle = '#104054';
            labelsOriginal[2].fillStyle = '#615b81';
            return labelsOriginal;
          },
          onClick: function (mouseEvent, legendItem, legend) {
            // toggle the visibility of the dataset from what it currently is
            legend.chart.getDatasetMeta(legendItem.datasetIndex).hidden = legend.chart.isDatasetVisible(legendItem.datasetIndex);
            legend.chart.update();
          }
        },
      },
      datalabels: {
        //align: 'end', anchor: 'center',
        color: '#888',
        font: function (context) {
          var w = context.chart.width;
          return { size: w < 512 ? 12 : 14, weight: 'bold', };
        },
      }
    }
  };

  dessineMoiUnMouton('graph02', 'doughnut', data, options);
}

/**
 * description
 * Affiche les courbes de fréquentation annuelle
 */
function graph03() {
  let data1 = {}, data2 = {};
  if (option == 'serie-1') { data1 = Object.values(response[1]); }
  if (option == 'serie-2') { data2 = Object.values(response[2]); }
  if (option == 'serie-3') { data2 = Object.values(response[3]); }
  if (option == 'all') { data1 = Object.values(response[1]); data2 = Object.values(response[2]); }

  let options = {
    responsive: true,
    scales: {
      x: {
        type: 'time',
        time: {
          unit: 'day'
        },
        display: true,
        title: { display: true, text: 'Date', color: '#00445b' },
        ticks: {
          major: { enabled: true },
          color: (context) => context.tick && context.tick.major && '#FF0000',
          font: function (context) {
            if (context.tick && context.tick.major) {
              return { weight: 'bold' };
            }
          }
        }
      },
      y: {
        display: true,
        title: { display: true, text: 'Nombre de connexion', color: '#00445b' },
        ticks: { color: '#00445b' }
      }
    },
    animations: { radius: { duration: 400, easing: 'linear', } },
    hoverRadius: 8,
    hoverBackgroundColor: 'yellow',
    interaction: { mode: 'nearest', intersect: false, axis: 'x' },
    plugins: {
      datalabels: { display: false, },
      zoom: {
        pan: { enabled: true, mode: 'x', modifierKey: 'ctrl', },
        zoom: { drag: { enabled: true }, mode: 'x', },
      },
      tooltip: { enabled: true },
      title: { display: true, text: 'Nombre de page vue.', font: { size: 24, family: 'claire-hand' }, color: '#00445b' },
      legend: { display: true },
    }
  };

  let data = {
    labels: Object.values(response[0]),
    datasets: [{
      label: 'Visiteurs',
      pointBorderColor: '#00445b',
      pointBackgroundColor: '#00445b',
      borderWidth: 2,
      radius: 0,
      data: data1,
      fill: false,
      borderColor: CHART_COLORS.rouge,
      backgroundColor: CHART_COLORS.rouge_opacity,
      tension: 0.2,
    },
    {
      label: 'Visiteur Unique',
      pointBorderColor: '#C64444',
      pointBackgroundColor: '#C64444',
      borderWidth: 2,
      radius: 0,
      data: data2,
      fill: false,
      borderColor: CHART_COLORS.bleu,
      backgroundColor: CHART_COLORS.bleu_opacity,
      tension: 0.2,
    },
    ]
  };
  destroy('tendance', 'true');
  if (!$('#tendance').length) { $('.tendance-box').append("<canvas id='tendance'> </canvas>"); }
  const ctx = document.getElementById('tendance').getContext('2d');
  const chart = new Chart(ctx, { type: 'line', data: data, options: options });
}





graph01();
graph02();