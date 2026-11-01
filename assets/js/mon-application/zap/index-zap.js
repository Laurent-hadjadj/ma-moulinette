/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-10-13 : entré JS pour la page /zap (DAST). Lit le dataset
 *  injecte dans <script type="application/json" id="zap-chart-data"> et
 *  construit, pour chaque "lane" (1 par target_url distinct détecté pour
 *  le maven_key du token — MODIF 26/10/2026, voir ZapPageController) :
 *   - 1 bar chart par référentiel OWASP présent (alertes par catégorie,
 *     dernier scan) ;
 *   - 1 line chart (évolution du risque par niveau au fil des scans).
 *
 *  Aligne sur le pattern de index-dependency-check-dashboard.js — rendu
 *  100% côté serveur pour les tableaux, JS limité à la construction des
 *  graphiques (pas d'AJAX, pas d'injection case par case).
 *
 *  ---
 *  Vous pouvez obtenir une copie de la licence a l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';

import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/zap.css';

/** Integration de jQuery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import { showMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** Chart.js + plugin datalabels (meme pattern que index-dependency-check-dashboard.js) */
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(...registerables);
Chart.register(ChartDataLabels);

const RISK_COLORS = {
  high:          '#f08a24',
  medium:        '#ffd200',
  low:           '#1779ba',
  informational: '#9aa3ad',
};

document.addEventListener('DOMContentLoaded', () => {
  const dataNode = document.getElementById('zap-chart-data');
  if (!dataNode) { return; }

  let data;
  try {
    data = JSON.parse(dataNode.textContent || '{}');
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = '[ZAP] dataset JSON parse error';
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_error', message);
    return;
  }

  (data.lanes || []).forEach((lane) => {
    buildCategoryCharts(lane.index, lane.categories || []);
    buildHistoryChart(lane.index, lane.history || []);
  });
});

/**
 * [Description for buildCategoryCharts]
 * Un bar chart par référentiel OWASP présent : 10 catégories en X, nombre
 * d'alertes (règles distinctes) en Y, sur le dernier scan uniquement d'une
 * lane (target_url) donnée.
 *
 * @param mixed laneIndex
 * @param mixed categories
 *
 * Created at: 13/10/2026 00:00:00 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildCategoryCharts = function(laneIndex, categories) {
  categories.forEach((dataset) => {
    const ctx = document.getElementById(`zap-chart-categories-${laneIndex}-${dataset.referential}`);
    if (!ctx) { return; }

    const labels = dataset.categories.map((c) => `A${c.n}`);
    const values = dataset.categories.map((c) => c.count);
    const fullLabels = dataset.categories.map((c) => `A${c.n} - ${c.label}`);

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: `OWASP ${dataset.referential}`,
          data: values,
          backgroundColor: '#1779ba',
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          datalabels: { display: false },
          title: { display: true, text: `Alertes par catégorie — OWASP ${dataset.referential}` },
          tooltip: {
            callbacks: {
              title: (items) => fullLabels[items[0].dataIndex],
            },
          },
        },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Nb alertes' } },
        },
      },
    });
  });
};

/**
 * [Description for buildHistoryChart]
 * Line chart : évolution du nombre d'alertes par niveau de risque au fil
 * des scans successifs d'une lane (target_url) donnée. 1 point = 1 scan
 * ingéré (pas un jour calendaire).
 *
 * @param mixed laneIndex
 * @param mixed history
 *
 * Created at: 13/10/2026 00:00:00 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildHistoryChart = function(laneIndex, history) {
  const ctx = document.getElementById(`zap-chart-history-${laneIndex}`);
  if (!ctx) { return; }
  if (!history.length) {
    ctx.getContext('2d').fillText('Pas assez de scans pour tracer une évolution', 10, 20);
    return;
  }

  const labels = history.map((r) => r.date);
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        { label: 'HIGH',          data: history.map((r) => r.high),          borderColor: RISK_COLORS.high,          backgroundColor: RISK_COLORS.high,          tension: 0.2 },
        { label: 'MEDIUM',        data: history.map((r) => r.medium),        borderColor: RISK_COLORS.medium,        backgroundColor: RISK_COLORS.medium,        tension: 0.2 },
        { label: 'LOW',           data: history.map((r) => r.low),           borderColor: RISK_COLORS.low,           backgroundColor: RISK_COLORS.low,           tension: 0.2 },
        { label: 'INFORMATIONAL', data: history.map((r) => r.informational), borderColor: RISK_COLORS.informational, backgroundColor: RISK_COLORS.informational, tension: 0.2 },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        datalabels: { display: false },
        title: { display: true, text: 'Évolution du risque par scan' },
      },
      scales: {
        x: { title: { display: true, text: 'Date du scan' } },
        y: { beginAtZero: true, title: { display: true, text: 'Nb alertes' } },
      },
    },
  });
};
