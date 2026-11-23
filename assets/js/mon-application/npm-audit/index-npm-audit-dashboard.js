/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *
 *  Entry JS pour /npm-audit/dashboard/{group}/{artifact}/{version} ET pour
 *  /npm-audit/paquets (vue cross-projets, réutilise buildTopPackagesBar
 *  avec un title/datasetLabel différent). Lit le dataset injecté dans
 *  <script type="application/json" id="npm-audit-chart-data"> et construit
 *  les graphiques présents dans ce dataset : répartition par sévérité
 *  (pie), par origine (bar empilé), par type de dépendance (bar empilé),
 *  top paquets (bar horizontal).
 *
 *  Aligné sur les patterns de index-dependency-check-dashboard.js
 *  (buildDonut) et index-dependency-check-executive.js (buildFamilyBar,
 *  bar horizontal).
 *
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/npm-audit.css';

/** Intégration de jQuery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import { showMessage } from '../../common/messageHelper.js';

/** Chart.js + plugin datalabels */
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(...registerables);
Chart.register(ChartDataLabels);

const SEVERITY_COLORS = {
  critical: '#cc4b37',
  high:     '#f08a24',
  moderate: '#ffd200',
  low:      '#1779ba',
  info:     '#9aa3ad',
};

/**
 * [Description for buildSeverityPie]
 * Pie chart : répartition des vulnérabilités du scan par sévérité.
 * Masque le label de pourcentage sous 5% pour éviter la surcharge visuelle
 * (même règle que buildDonut côté dependency-check).
 *
 * @param mixed severity
 *
 * @return mixed
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
const buildSeverityPie = function (severity) {
  const ctx = document.getElementById('npm-dash-severity');
  if (!ctx) { return; }

  const total = severity.critical + severity.high + severity.moderate + severity.low + severity.info;
  if (total === 0) {
    ctx.getContext('2d').fillText('Aucune vulnérabilité à répartir', 10, 20);
    return;
  }

  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['CRITICAL', 'HIGH', 'MODERATE', 'LOW', 'INFO'],
      datasets: [{
        data: [severity.critical, severity.high, severity.moderate, severity.low, severity.info],
        backgroundColor: [
          SEVERITY_COLORS.critical,
          SEVERITY_COLORS.high,
          SEVERITY_COLORS.moderate,
          SEVERITY_COLORS.low,
          SEVERITY_COLORS.info,
        ],
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'right' },
        datalabels: {
          color: '#fff',
          font: { weight: 'bold' },
          formatter: (v, dctx) => {
            const sum = dctx.dataset.data.reduce((a, b) => a + b, 0);
            const pct = sum > 0 ? Math.round((v / sum) * 100) : 0;
            return pct >= 5 ? `${pct}%` : '';
          },
        },
        title: { display: true, text: 'Répartition par sévérité' },
      },
    },
  });
};

const SEVERITY_LABELS = {
  critical: 'CRITICAL',
  high:     'HIGH',
  moderate: 'MODERATE',
  low:      'LOW',
  info:     'INFO',
};

/**
 * [Description for buildStackedSeverityBar]
 * Bar chart empilé par sévérité, générique pour les graphiques "Origine" et
 * "Direct/Transitif" — même forme de données ({labels, severities}), seul
 * le canvas/titre changent.
 *
 * @param mixed canvasId
 * @param mixed chartData
 * @param mixed titleText
 *
 * @return mixed
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
const buildStackedSeverityBar = function (canvasId, chartData, titleText) {
  const ctx = document.getElementById(canvasId);
  if (!ctx || !chartData || !Array.isArray(chartData.labels) || chartData.labels.length === 0) { return; }

  const datasets = Object.keys(SEVERITY_LABELS)
    .map((sev) => ({
      label: SEVERITY_LABELS[sev],
      data: chartData.severities ? chartData.severities[sev] || [] : [],
      backgroundColor: SEVERITY_COLORS[sev],
    }))
    .filter((ds) => ds.data.some((v) => v > 0));

  if (datasets.length === 0) {
    ctx.getContext('2d').fillText('Aucune vulnérabilité à répartir', 10, 20);
    return;
  }

  new Chart(ctx, {
    type: 'bar',
    data: { labels: chartData.labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' },
        title: { display: true, text: titleText },
        datalabels: {
          color: '#fff',
          font: { weight: 'bold' },
          formatter: (v) => (v > 0 ? v : ''),
        },
      },
      scales: {
        x: { stacked: true },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
      },
    },
  });
};

/**
 * [Description for buildTopPackagesBar]
 * Bar chart horizontal : top paquets du scan par nombre de findings.
 * Pattern buildFamilyBar (index-dependency-check-executive.js).
 *
 * @param mixed chartData
 *
 * @return mixed
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
const buildTopPackagesBar = function (chartData) {
  const ctx = document.getElementById('npm-dash-top-packages');
  if (!ctx || !chartData || !Array.isArray(chartData.labels) || chartData.labels.length === 0) { return; }

  // title/datasetLabel optionnels : la page /npm-audit/paquets (cross-projets)
  // réutilise ce même bâtisseur avec un libellé différent de celui du
  // dashboard (nombre de projets touchés, pas nombre de vulnérabilités).
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartData.labels,
      datasets: [{
        label: chartData.datasetLabel || 'Vulnérabilités',
        data: chartData.counts || [],
        backgroundColor: '#00445b',
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        title: { display: true, text: chartData.title || 'Top paquets vulnérables' },
        datalabels: { anchor: 'end', align: 'right', color: '#333' },
      },
      scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  });
};

document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('npm-audit-chart-data');
  if (!el) { return; }

  let data;
  try {
    data = JSON.parse(el.textContent);
  } catch {
    showMessage('critical', 'Données du dashboard npm audit illisibles.');
    return;
  }

  if (!data) { return; }

  if (data.severity) {
    buildSeverityPie(data.severity);
  }
  if (data.origin) {
    buildStackedSeverityBar('npm-dash-origin', data.origin, 'Vulnérabilités par origine');
  }
  if (data.direct) {
    buildStackedSeverityBar('npm-dash-direct', data.direct, 'Vulnérabilités : direct vs transitif');
  }
  if (data.topPackages) {
    buildTopPackagesBar(data.topPackages);
  }
});
