// @ts-nocheck
// @ts-ignore
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
import '../../../styles/mon-application/profiling.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import '../../auth/details.js';

/* On importe les paramètres serveur. */
import {serveur} from '../../common/properties.js';
import { showMessage, hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe les constantes */
import { contentType, dateOptionsShort, http_200 } from '../../common/constante.js';

import {Chart, registerables} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import zoomPlugin from 'chartjs-plugin-zoom';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels, zoomPlugin);

/**
  * [Description for fetchJSON]
  *
  * @param mixed url
  *
  * @return json
  *
  * Created at: 14/11/2025 11:49:25 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
  */
const fetchJSON = async function(url, indicateur = '', type = 'GET') {
  const data = { indicateur };
  const options = {
    url,
    type,
    dataType: 'json',
    contentType,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
    // Ne pas convertir automatiquement les données en chaîne
    processData: false,
    data: type === 'POST' ? JSON.stringify(data) : null
  };

   // Si c'est une requête GET, ne pas envoyer de données (au lieu de 'data' = '')
  if (type === 'GET') {
    options.data = data ? new URLSearchParams(data).toString() : null;
  }

  try{
      const r = await $.ajax(options);
      return await r;
    } catch (error) {
      const trace = prepareTechnicalDetails(error);
      const message = "Une erreur inattendue s'est produite lors de la collecte des indicateurs de performances (Erreur 500).";
      showMessage('critical', message, trace);
  }
}

/**
 * colorPalette
 *
 * @var void
 */
const colorPalette = [
  '#3366CC', '#DC3912', '#FF9900', '#109618', '#990099',
  '#0099C6', '#DD4477', '#66AA00', '#B82E2E', '#316395',
  '#994499', '#22AA99', '#AAAA11', '#6633CC', '#E67300',
  '#8B0707', '#329262', '#5574A6', '#3B3EAC', '#B77322'
];

const backgroundPalette = colorPalette.map(c => c + '33'); // alpha 0.2
const colorMap = new Map();

/**
 * [Description for getColor]
 *
 * @param mixed label
 *
 * @return object
 *
 * Created at: 16/11/2025 20:29:16 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const getColor = function(label) {
  if (!label) { return colorPalette[0]; }

  if (!colorMap.has(label)) {
    const index = colorMap.size % colorPalette.length;
    colorMap.set(label, {
      border: colorPalette[index],
      background: backgroundPalette[index]
    });
  }
  return colorMap.get(label);
}

function adjustFill(color, enabled) {
    if (!enabled) return color;

    return color.replace('0.2', '0.15');
}

/**
 * Création générique d'un chart
 * @param {HTMLCanvasElement} ctx
 * @param {Object} data
 * @param {string} type 'line' | 'bar'
 * @param {string} datasetKey 'datasetsTime' | 'datasetsMemory'
 * @param {Object} options options supplémentaires { fill: true|false, suggestedMaxY }
 */
const createChart = function(ctx, data, type = 'line', datasetKey = 'datasetsTime', options = {}) {
    if (!ctx) {
        showMessage('error', 'createChart: canvas context manquant');
        return null;
    }

    const fillEnabled = options.fill === true;
    const suggestedMaxY = options.suggestedMaxY || undefined;
    const source = Array.isArray(data[datasetKey]) ? data[datasetKey] : [];

    const datasets = source.map(ds => {
        const col = getColor(ds.label);
        const values = Array.isArray(ds.data) ? ds.data : [ds.data];

        return {
            label: ds.label,
            data: values,
            borderColor: col.border,
            backgroundColor: adjustFill(col.background, fillEnabled),
            fill: fillEnabled,
            tension: options.tension ?? (type === 'line' ? 0.3 : 0),
            pointRadius: options.pointRadius ?? (type === 'line' ? 2 : 0),
            pointHoverRadius: options.pointHoverRadius ?? (type === 'line' ? 7 : 0),
            borderWidth: 2
        };
    });

    return new Chart(ctx, {
        type,
        data: {
            labels: data.labels || [],
            datasets,
        },
        options: {
            responsive: true,
            animation: {
              duration: 400,
              easing: 'easeOutQuart'
            },
            //maintainAspectRatio: true,
            plugins: {
                legend: {
                  display: source.length <= 10,
                  position: 'top',
                  labels: {
                    usePointStyle: true,
                    boxWidth: 10,
                    font: {
                      size: (ctx) => {
                          const w = ctx?.chart?.width || 800;
                          return w < 600 ? 9 : 12;
                      }
                    }
                  }
                },
                interaction: {
                  mode: 'nearest', // ou 'index'
                  intersect: false // permet au zoom de détecter l'événement
                },
                tooltip: {
                  backgroundColor: '#1e1e1e',
                  titleColor: '#fff',
                  bodyColor: '#fff',
                  borderColor: '#666',
                  borderWidth: 1,
                  padding: 8,
                  callbacks: {
                      //title: ctx => (ctx && ctx.length ? ctx[0].label : ''),
                      title(ctx) {
                        const rawLabel = ctx[0].label;
                        // Si label = date valide → format YYYY-MM-DD
                        if (!isNaN(Date.parse(rawLabel))) {
                            const d = new Date(rawLabel);
                            return d.toISOString().split('T')[0];
                        }
                        return rawLabel;
                      },

                      label(ctx) {
                        const label = ctx.dataset.label ?? '';
                        const raw = ctx.raw;

                        // pas de valeur → on ne montre pas
                        if (raw === null || raw === undefined) return null;

                        const value = Number(raw).toFixed(1);
                        const unit = ctx.dataset.label.toLowerCase().includes('mémoire')
                            ? ' Mo'
                            : ' sec';
                        return ` ${label}: ${value}${unit}`;
                      },
                    labelColor(ctx) {
                        return {
                            backgroundColor: ctx.dataset.borderColor,
                            borderColor: ctx.dataset.borderColor,
                            borderWidth: 2,
                            borderRadius: 3
                        };
                    }
                  },
                },
              zoom: {
                      wheel: { enabled: true },
                      pinch: { enabled: true },
                      drag: { enabled: true },
                      mode: 'x'
                }
            },
            decimation: {
                enabled: true,
                algorithm: 'lttb',
                samples: 200  // max points à afficher
            },
            elements: {
                line: { tension: 0.3 },
                point: { radius: 5, hoverRadius: 7 }
            },
            scales: {
                x: {
                    stacked: type === 'bar',
                  ticks: {
                    autoSkip: true,
                    maxRotation: (ctx) => {
                      const w = ctx?.chart?.width || 800;
                      return w < 500 ? 0 : 45;
                    },
                    minRotation: (ctx) => {
                        const w = ctx?.chart?.width || 800;
                        return w < 500 ? 0 : 30;
                    },
                    callback: function(value, index) {
                          const label = this.getLabelForValue(index);
                      // Si c'est un bar chart, on ne touche pas aux labels
                          if (this.chart.config.type === 'bar') {
                              return label;
                          }

                          if (!isNaN(Date.parse(label))) {
                              const d = new Date(label);
                              return d.toISOString().split('T')[0]; // YYYY-MM-DD
                          }
                          return label;
                      }
                  }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return Number(value).toFixed(0);
                        }
                    },
                    suggestedMax: (ctx) => {
                      const all = ctx.chart.data.datasets.flatMap(ds => ds.data.filter(v => v != null));
                      if (!all.length) return undefined;
                      const max = Math.max(...all);
                      return max * 1.15; // marge 15%
                    }
                }
            }
        }
    });
}

/**
 * [Description for fillActivityTable]
 *
 * @param mixed tableId
 * @param mixed rows
 *
 * @return [type]
 *
 * Created at: 16/11/2025 18:50:34 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const fillActivityTable = function(tableId, rows) {
  const tbody = document.querySelector(`#${tableId} tbody`);
  tbody.innerHTML = '';
  rows.forEach(row => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${row.portefeuille}</td>
      <td>${row.utilisateur}</td>
      <td class="text-center">${row.nbProjets}</td>
      <td class="text-center">${row.tempsMoyen.toFixed(2)}</td>
      <td class="text-center">${row.memoireMoyenne.toFixed(2)}</td>
      <td>${new Date(row.dateExecution).toLocaleString()}</td>
    `;
    tbody.appendChild(tr);
  });
}

/**
 * [Description for fillSummaryTable]
 *
 * @param mixed tableId
 * @param mixed summary
 *
 * @return [type]
 *
 * Created at: 16/11/2025 18:50:31 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const fillSummaryTable = function(tableId, summary)
{
  const tbody = document.querySelector(`#${tableId} tbody`);
  tbody.innerHTML = `
    <tr>
      <td>${summary.average_time_total}</td>
      <td>${summary.average_time_projet}</td>
      <td class="text-center">${summary.average_memory}</td>
      <td class="text-center">${summary.average_memory_peak}</td>
      <td class="text-center">${summary.average_memory_peak_max}</td>
      <td>${new Date(summary.last_execution).toLocaleString()}</td>
    </tr>`;
}

/**
 * [Description for createDonutChart]
 *
 * @param mixed ctx
 * @param mixed labels
 * @param mixed data
 * @param mixed colors
 * @param string unit
 *
 * @return [type]
 *
 * Created at: 16/11/2025 18:50:24 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const createDonutChart = function(ctx, labels, data, colors, unit = '') {
  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data,
        // Utilisation de getColor pour récupérer la couleur de chaque label
        backgroundColor: data.map((_, i) => {
          const col = getColor(labels[i]);
          return col.background;  // Couleur de fond
        }),
        borderColor: data.map((_, i) => {
          const col = getColor(labels[i]);
          return col.border;  // Couleur de bordure
        }),
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.label}: ${ctx.raw}${unit}`
          }
        }
      },
      // Augmenter l'épaisseur du donut
      cutout: '60%'
    }
  });
}

/**
 * [Description for createBarChart]
 *
 * @param mixed ctx
 * @param mixed labels
 * @param mixed data
 * @param mixed colors
 * @param string unit
 *
 * @return [type]
 *
 * Created at: 16/11/2025 18:50:19 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const createBarChart = function(ctx, labels, data, colors, unit = '') {
  return new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Valeur',
        data,
        // Utilisation de getColor pour récupérer la couleur de chaque label
        backgroundColor: data.map((_, i) => {
          const col = getColor(labels[i]);
          return col.background;  // Couleur de fond
        }),
        borderColor: data.map((_, i) => {
          const col = getColor(labels[i]);
          return col.border;  // Couleur de bordure
        }),
        borderWidth: 2
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.dataset.label}: ${ctx.raw}${unit}`
          }
        }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
}

/**
 * [Description for createUserCards]
 *
 * @param mixed data
 *
 * @return void
 *
 * Created at: 16/11/2025 18:51:57 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const createUserCards = function(data, id) {

  const container = document.querySelector(`#indicateur-${id}`);
  id = (id == 'projets') ? 'nb_projets' : id;
  id = (id == 'exec') ? 'nb_exec' : id;
  id = (id == 'execution') ? 'derniere_execution' : id;

  container.innerHTML = '';  // Vider le contenu précédent

  data.indicateur.forEach(item => {
    const card = document.createElement('div');
    card.classList.add('card');

    const title = document.createElement('h4');
    /** On converti la date en date :) */
    if (id == 'derniere_execution'){
      const date = new Date(item[id]);
      title.textContent = new Intl.DateTimeFormat('default', dateOptionsShort)
                            .format(date);
    } else {
      title.textContent = item[id];
    }
    title.classList.add('h5');

    const timeParagraph = document.createElement('p');
    timeParagraph.innerHTML = `<strong>Temps moyen :</strong> ${item.average_time} s`;

    const memoryParagraph = document.createElement('p');
    memoryParagraph.innerHTML = `<strong>Mémoire moyenne :</strong> ${item.average_memory} Mo`;

    // Ajouter les éléments dans la carte
    card.appendChild(title);
    card.appendChild(timeParagraph);
    card.appendChild(memoryParagraph);

    // Ajouter la carte dans le container
    container.appendChild(card);
  });
}

  /**
  * [Description for initDashboard]
  *
  * @return void
  *
  * Created at: 16/11/2025 20:32:22 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
  */
const initDashboard = async function() {
  try {
    let ctx;

    // Appel de la fonction avec les données récupérées
    const indicateur_granularite = await fetchJSON(`${serveur()}/api/secure/profiling/indicateur`, 'granularite', 'POST');
    if (indicateur_granularite){
      const data =  indicateur_granularite.indicateur;
      createUserCards(data, 'granularite');
    }

    const indicateur_periode = await fetchJSON(`${serveur()}/api/secure/profiling/indicateur`, 'periode', 'POST');
    if (indicateur_periode){
      const data =  indicateur_periode.indicateur;
      createUserCards(data, 'periode');
    }

    const indicateur_utilisateur = await fetchJSON(`${serveur()}/api/secure/profiling/indicateur`, 'utilisateur', 'POST');
    if (indicateur_utilisateur){
      const data =  indicateur_utilisateur.indicateur;
      createUserCards(data, 'utilisateur');
    }

    const indicateur_portefeuille = await fetchJSON(`${serveur()}/api/secure/profiling/indicateur`, 'portefeuille', 'POST');
    if (indicateur_portefeuille){
      const data =  indicateur_portefeuille.indicateur;
      createUserCards(data, 'portefeuille');
    }

    const indicateur_exec = await fetchJSON(`${serveur()}/api/secure/profiling/indicateur`, 'nb_exec', 'POST');
    if (indicateur_exec){
      const data =  indicateur_exec.indicateur;
      createUserCards(data, 'exec');
    }

    const indicateur_execution = await fetchJSON(`${serveur()}/api/secure/profiling/indicateur`, 'derniere_execution', 'POST');
    if (indicateur_execution){
      const data =  indicateur_execution.indicateur;
      createUserCards(data, 'execution');
    }

    // table summary (KPI)
    const data = await fetchJSON(`${serveur()}/api/secure/profiling/summary`, 'GET');
    if (data && data.summary){
      const summary = data.summary;
      fillSummaryTable('summaryTable', summary);

      ctx = document.getElementById('chart-kpi-time-donut');
      createDonutChart(ctx, ['Total', 'Projet'], [summary.average_time_total, summary.average_time_projet], getColor, ' Sec');

      ctx = document.getElementById('chart-kpi-memory-donut');
      createDonutChart(ctx, ['Total Time', 'Projet Time'], [summary.average_memory_peak, summary.average_memory], ' Mo');

      createBarChart(
        document.getElementById('chart-kpi-memory-bar'),
        ['Pic mémoire', 'Pic mémoire max'],
        [summary.average_memory_peak, summary.average_memory_peak_max],
        ' Mo'
      );

      // Table dernières exécutions
      const lastExec = await fetchJSON(`${serveur()}/api/secure/profiling/latest`);
      if (lastExec) {
        fillActivityTable('tableExecutions', lastExec.latest);
      }

    // Graphiques globaux
      const portefeuilleData = await fetchJSON(`${serveur()}/api/secure/profiling/portefeuille/all`);
      if (portefeuilleData) {
        createChart(document.getElementById('chartTime'), portefeuilleData.portefeuille, 'bar', 'datasetsTime');
        createChart(document.getElementById('chartMemory'), portefeuilleData.portefeuille, 'bar', 'datasetsMemory', { fill: true });
      }
    }

    // Weekly - temps et mémoire (line chart)
    const weeklyData = await fetchJSON(`${serveur()}/api/secure/profiling/weekly/all`);
    if (weeklyData) {
        createChart(document.getElementById('chartWeeklyTime'), weeklyData.weekly, 'line', 'datasetsTime');
        createChart(document.getElementById('chartWeeklyMemory'), weeklyData.weekly, 'line', 'datasetsMemory', { fill: true });
    }

    // Monthly - temps et mémoire (line chart)
    const monthlyData = await fetchJSON(`${serveur()}/api/secure/profiling/monthly/all`);
    if (monthlyData) {
        createChart(document.getElementById('chartMonthlyTime'), monthlyData.monthly, 'line', 'datasetsTime');
        createChart(document.getElementById('chartMonthlyMemory'), monthlyData.monthly, 'line', 'datasetsMemory', { fill: true });
    }

    // Users - bar chart (groupé) pour temps et mémoire
    const usersData = await fetchJSON(`${serveur()}/api/secure/profiling/users/all`);
    if (usersData) {
      createChart(document.getElementById('chartUsersTime'), usersData.user, 'bar', 'datasetsTime');
      createChart(document.getElementById('chartUsersMemory'), usersData.user, 'bar', 'datasetsMemory', { fill: true });
    }

  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur globale et inattendue s'est produite lors de la collecte des indicateurs de performances (Erreur 500).";
    showMessage('critical', message, trace);
  }
}

document.addEventListener('DOMContentLoaded',initDashboard);
