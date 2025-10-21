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
import '../../../styles/mon-application/cosui.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';
import '../../common/foundation.js';
import '../../auth/details.js';

/** On importe les constantes */
import { zero, dix, vingt, trente, quarante, cinquante, soixante, soixanteDix, cent, quatreVingt } from '../../common/constante.js';

import {Chart, registerables} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels);

const oui_non = '#js-oui-non';
const upDownEqual = '.up, .down, .equal';

/**
 * The function `dessineMoiUnRadar(dataset, labels)` is responsible for drawing a radar chart using the Chart.js library.
 *
 * @function
 * @name dessineMoiUnRadar
 * @kind function
 * @param {any} dataset
 * @param {any} labels
 * @returns {void}
 */
const dessineMoiUnRadar = function (dataset1, dataset2, label1, label2){
  /** Préparation du radar */
  const data = {
    labels: ['Fiabilité','Vulnérabilité','Hotspot', 'Maintenabilité','Couverture','Dette'],  font: { size: 18 } ,

    datasets: [
    {
      label:'v'+label1,
      backgroundColor: 'rgba(255, 99, 132, 0.2)',
      borderColor: 'rgba(255, 99, 132, 0.5)',
      pointBackgroundColor: '#FF6384',
      pointBorderColor: '#fff',
      pointHoverBackgroundColor: '#fff',
      pointHoverBorderColor: '#FF6384',
      data: dataset1,
      fill: true,
      tension: 0.2 },
      {
        label:'v'+label2,
        backgroundColor: 'rgba(0, 68, 91, 0.2)',
        borderColor: 'rgba(0, 68, 91, 0.5)',
        pointBackgroundColor: '#00445b',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: '#00445b',
        data: dataset2,
        fill: true,
        tension: 0.2 } ]};

    const options = {
      //aspectRatio:2,
      //maintainAspectRatio: true,
      responsive: true,
      scales: {
        r: {
            // Taille de la police pour les labels autour du radar
            pointLabels: {
                font: {
                    size: 16
                }
            },
            // Taille de la police pour les valeurs de l'axe radial
            ticks: {
                font: {
                    size: 14
                }
            }
        }
    },
      plugins: {
        title: {
          display: true,
          text: `Comparaison version v${label1} vs v${label2}.`,
          font: { size: 18, weight: 'bold'}
        },
        tooltip: {
          enabled: true,
          callbacks: {
            label: (context) => {
              // Dictionnaire des notations en fonction des labels
              const noteMapping = {
                'Fiabilité': [
                  { value: cent, note: 'A' },
                  { value: quatreVingt, note: 'B' },
                  { value: soixante, note: 'C' },
                  { value: trente, note: 'D' },
                  { value: dix, note: 'E' },
                  { value: zero, note: 'Z' }
                ],
                'Vulnérabilité': [
                  { value: cent, note: 'A' },
                  { value: quatreVingt, note: 'B' },
                  { value: soixante, note: 'C' },
                  { value: trente, note: 'D' },
                  { value: dix, note: 'E' },
                  { value: zero, note: 'Z' }
                ],
                'Maintenabilité': [
                  { value: cent, note: 'A' },
                  { value: quatreVingt, note: 'B' },
                  { value: soixante, note: 'C' },
                  { value: trente, note: 'D' },
                  { value: dix, note: 'E' },
                  { value: zero, note: 'Z' }
                ],
                'Hotspot': [
                  { value: quatreVingt, note: 'A' },
                  { value: soixanteDix, note: 'B' },
                  { value: cinquante, note: 'C' },
                  { value: trente, note: 'D' },
                  { value: zero, note: 'E' }
                ],
                'Dette': [
                  { value: quatreVingt, note: 'A' },
                  { value: soixante, note: 'B' },
                  { value: quarante, note: 'C' },
                  { value: vingt, note: 'D' },
                  { value: zero, note: 'E' }
                ],
                'Couverture': [
                  { value: quatreVingt, note: 'A' },
                  { value: soixante, note: 'B' },
                  { value: quarante, note: 'C' },
                  { value: vingt, note: 'D' },
                  { value: zero, note: 'E' }
                ]
              };

              // Vérifier si le label existe dans le dictionnaire
              const label = context.label;
              const rValue = context.parsed.r;

              if (noteMapping[label]) {
                // On parcourt les critères de notation
                for (const { value, note } of noteMapping[label]) {
                  if (rValue >= value) {
                    return `Note : ${note}`;
                  }
                }
              } else {
                sessionStorage.setItem('ma_moulinette_error', `Oups, le label ${label} n'est pas pris en charge.`);
              }

              return ''; // Retourner une chaîne vide si aucune note n'est trouvée
            }
          }
        },
        legend: { position: 'bottom',
                  labels: { font: { size: 16, weight: 'bold' } }
                },
        datalabels: { display: true,
                      color: '#00445b' }
        }};

    const chartStatus = Chart.getChart('graphique-note');
    if (chartStatus !== undefined) {
      chartStatus.destroy();
    }

    const ctx = document.getElementById('graphique-note').getContext('2d');
    const charts = new Chart(ctx, { type: 'radar', data, options });
    if (charts === null) {
      sessionStorage.setItem('ma_moulinette_error', "c'est pour rire, il ne peut pas y avoir d'erreur ici :)");
    }

  };

/**
 * description
 * On affiche/désactive les indicateurs de variation
 * Faux positif : sonarLint(javascript:S1192)
 * @constant
 * @name oui_non
 * @type {"#js-oui-non"}
 */
$(oui_non).on('click', function () {
  if ($(oui_non).is(':checked')===true) {
    $(upDownEqual).removeClass('hide');
  }

  /** On en fait deux pour être certain de capter l’événement */
  if ( $(oui_non).is(':checked') === false && $(upDownEqual).hasClass('hide') === false ) {
      $(upDownEqual).addClass('hide');
    }
});

/**
 * On affiche les indicateurs du projet de référence
 *
 * @method
 * @name JQuery
 */
$('#js-affiche-projet-reference').on('click', function () {
  $('#modal-projet-reference').foundation('open');
});

/** Affiche le radar des indicateurs clés */
const elm=document.getElementById('js-series');
let s1=[], s2=[];

// ['Fiabilité','Vulnérabilité','Hotspot', 'Maintenabilité','Couverture','Dette']
elm.dataset.initial.split(',').forEach(element => {
  s1.push(Number(element));
});
elm.dataset.actuel.split(',').forEach(element => {
  s2.push(Number(element));
});

dessineMoiUnRadar(s1, s2, elm.dataset.labela, elm.dataset.labelb);
