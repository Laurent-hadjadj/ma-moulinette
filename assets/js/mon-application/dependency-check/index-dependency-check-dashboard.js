/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-05-11 : entry JS pour le
 *  dashboard analytics DC. Lit le dataset injecte dans
 *  <script type="application/json" id="dc-dashboard-data"> et construit
 *  4 charts Chart.js : line timeseries 30j, scatter projets x CVE, stacked
 *  bar top 15 projets par sévérité, donut repartition globale.
 *
 *  Aligne sur le pattern de index-dependency-check-executive.js.
 *
 *  ---
 *  Vous pouvez obtenir une copie de la licence a l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import 'datatables.net-zf/css/dataTables.foundation.min.css';

import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/dependency-check.css';

/** Integration de jQuery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import '../../common/messageHelper.js';
import { modalSafe } from '../../common/safeModal.js';

/** MODIF 2026-05-16 : DataTables pour la cartographie projets. */
import DataTable from 'datatables.net-zf'; //NOSONAR

/** Chart.js + plugin datalabels */
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(...registerables);
Chart.register(ChartDataLabels);

const SEVERITY_COLORS = {
  CRITICAL: '#cc4b37',
  HIGH:     '#f08a24',
  MEDIUM:   '#ffd200',
  LOW:      '#1779ba',
};

const DT_LANG_FR = {
  emptyTable: "Aucune donnée disponible dans le tableau",
  search:       'Rechercher :',
  lengthMenu:   'Afficher _MENU_ lignes',
  info:         'Lignes _START_ à _END_ sur _TOTAL_',
  infoEmpty:    'Aucune ligne',
  infoFiltered: '(filtré depuis _MAX_)',
  zeroRecords:  'Aucun résultat',
  paginate:     { first: '«', previous: '‹', next: '›', last: '»' },
};

document.addEventListener('DOMContentLoaded', () => {
  /* Cartographie projets */
  const cartographie = document.getElementById('table-dc-cartographie');
  if (cartographie) {
    new DataTable(cartographie, {
      paging: true,
      info: true,
      searching: true,
      ordering: true,
      pageLength: 10,
      lengthMenu: [10, 15, 20, 25],
      order: [[1, 'desc']],
      columnDefs: [
        { orderable: false, targets: [5] },
        { type: 'num', targets: [2] },
      ],
      language: { ...DT_LANG_FR, emptyTable: 'Aucun projet à cartographier' },
    });
  }

  /* Nouvelles CVE CRITICAL (table principale) */
  const nouvelles = document.getElementById('table-dc-nouvelles-cve');
  if (nouvelles) {
    new DataTable(nouvelles, {
      paging: true,
      info: true,
      searching: true,
      ordering: true,
      pageLength: 10,
      lengthMenu: [10, 15, 20, 25],
      order: [[1, 'desc']],
      columnDefs: [
        { orderable: false, targets: [4] },
        { type: 'num', targets: [1, 2] },
      ],
      language: { ...DT_LANG_FR, emptyTable: 'Aucune nouvelle CVE CRITICAL' },
    });
  }

  /* Tables dans les modales Foundation Reveal (1 par CVE, cachées à l'init).
   * autoWidth:false évite les calculs de largeur sur éléments hidden. */
  document.querySelectorAll('[id^="table-dc-new-cve-"]').forEach(el => {
    new DataTable(el, {
      paging: true,
      info: true,
      searching: true,
      ordering: true,
      pageLength: 5,
      lengthMenu: [5, 10, 15, 20],
      order: [[4, 'desc']],
      autoWidth: false,
      columnDefs: [
        { orderable: false, targets: [5] },
      ],
      language: { ...DT_LANG_FR, emptyTable: 'Aucune application impactée' },
    });
  });

  /* Ré-ajustement des colonnes à l'ouverture de chaque modale. */
  $(document).on('open.zf.reveal', '[data-reveal]', function () {
    const modal = this;
    modal.querySelectorAll('table').forEach(t => {
      if (DataTable.isDataTable(t)) {
        new DataTable(t).columns.adjust();
      }
    });
  });

  const dataNode = document.getElementById('dc-dashboard-data');
  if (!dataNode) { return; }

  let data;
  try {
    data = JSON.parse(dataNode.textContent || '{}');
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = '[DC dashboard] dataset JSON parse error';
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_error', message);
    return;
  }

  buildTimeseries(data.timeseries || []);
  buildScatter(data.scatter || []);
  buildStacked(data.stacked || { labels: [], critical: [], high: [], medium: [], low: [] });
  buildDonut(data.donut || { critical: 0, high: 0, medium: 0, low: 0 });
});

/**
 * [Description for buildTimeseries]
 * Line chart : evolution temporelle des CVE par sévérité sur 30 jours.
 * 1 série par sévérité. Axe X = jours, Axe Y = nombre cumulé.
 *
 * @param mixed rows
 *
 * Created at: 12/05/2026 07:43:00 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildTimeseries = function(rows) {
  const ctx = document.getElementById('dc-dash-timeseries');
  if (!ctx) { return; }
  if (!rows.length) {
    ctx.getContext('2d').fillText('Aucune donnée sur les 30 derniers jours', 10, 20);
    return;
  }

  const labels = rows.map(r => r.day);
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        { label: 'CRITICAL', data: rows.map(r => r.critical), borderColor: SEVERITY_COLORS.CRITICAL, backgroundColor: SEVERITY_COLORS.CRITICAL, tension: 0.2 },
        { label: 'HIGH',     data: rows.map(r => r.high),     borderColor: SEVERITY_COLORS.HIGH,     backgroundColor: SEVERITY_COLORS.HIGH,     tension: 0.2 },
        { label: 'MEDIUM',   data: rows.map(r => r.medium),   borderColor: SEVERITY_COLORS.MEDIUM,   backgroundColor: SEVERITY_COLORS.MEDIUM,   tension: 0.2 },
        { label: 'LOW',      data: rows.map(r => r.low),      borderColor: SEVERITY_COLORS.LOW,      backgroundColor: SEVERITY_COLORS.LOW,      tension: 0.2 },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        datalabels: { display: false },
        title: { display: true, text: 'Évolution CVE par sévérité (30 derniers jours)' },
      },
      scales: {
        x: { title: { display: true, text: 'Date du scan' } },
        y: { beginAtZero: true, title: { display: true, text: 'Nb CVE détectées' } },
      },
    },
  });
}

/**
 * [Description for buildScatter]
 * Scatter chart : 1 point par projet. X = nb deps, Y = nb CVE total,
 * taille = nb CRITICAL (plus le projet a de CRITICAL, plus le point est gros).
 * Affiche un titre "Cartographie projets (X=deps, Y=CVE, taille=CRITICAL)".
 * Si aucun projet n'a de CVE, affiche "Aucun projet à positionner" au lieu du graphique.
 *
 * @param mixed points
 *
 * Created at: 12/05/2026 07:43:57 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildScatter = function(points) {
  const ctx = document.getElementById('dc-dash-scatter');
  if (!ctx) { return; }
  if (!points.length) {
    ctx.getContext('2d').fillText('Aucun projet à positionner', 10, 20);
    return;
  }

  new Chart(ctx, {
    type: 'bubble',
    data: {
      datasets: [{
        label: 'Projets',
        data: points,
        backgroundColor: 'rgba(204, 75, 55, 0.55)',
        borderColor: '#cc4b37',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        datalabels: { display: false },
        title: { display: true, text: 'Cartographie projets (X=deps, Y=CVE, taille=CRITICAL)' },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const p = ctx.raw;
              return `${p.label} - deps:${p.x} cve:${p.y}`;
            },
          },
        },
      },
      scales: {
        x: { title: { display: true, text: 'Nb dépendances' }, beginAtZero: true },
        y: { title: { display: true, text: 'Nb CVE total' }, beginAtZero: true },
      },
    },
  });
}

/**
 * [Description for buildStacked]
 * Stacked bar : top 15 projets par cve_count_total. Chaque barre empilée
 * affiche le mix CRITICAL/HIGH/MEDIUM/LOW pour ce projet. Axe X = projets, Axe Y = nb CVE.
 * Si aucun projet n'a de CVE, affiche "Aucun projet à afficher" au lieu du graphique.
 *
 * @param mixed d
 *
 * Created at: 12/05/2026 07:46:49 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildStacked = function(d) {
  const ctx = document.getElementById('dc-dash-stacked');
  if (!ctx) { return; }
  if (!d.labels.length) {
    ctx.getContext('2d').fillText('Aucun projet à afficher', 10, 20);
    return;
  }

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: d.labels,
      datasets: [
        { label: 'CRITICAL', data: d.critical, backgroundColor: SEVERITY_COLORS.CRITICAL },
        { label: 'HIGH',     data: d.high,     backgroundColor: SEVERITY_COLORS.HIGH     },
        { label: 'MEDIUM',   data: d.medium,   backgroundColor: SEVERITY_COLORS.MEDIUM   },
        { label: 'LOW',      data: d.low,      backgroundColor: SEVERITY_COLORS.LOW      },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'top' },
        datalabels: { display: false },
        title: { display: true, text: 'Top 15 projets par CVE (mix sévérité)' },
      },
      scales: {
        x: { stacked: true },
        y: { stacked: true, beginAtZero: true },
      },
    },
  });
}

/**
 * [Description for buildDonut]
 * Donut : repartition globale par sévérité sur l'ensemble du périmètre.
 * Affiche le % de chaque sévérité (ex: 25% CRITICAL, 50% HIGH, 15% MEDIUM, 10% LOW).
 * Si une sévérité représente moins de 5% du total, n'affiche pas le label pour éviter la surcharge visuelle.
 * Affiche un titre "Repartition globale par sévérité".
 *
 * @param mixed d
 *
 * Created at: 12/05/2026 07:47:18 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildDonut = function(d) {
  const ctx = document.getElementById('dc-dash-donut');
  if (!ctx) { return; }
  const total = d.critical + d.high + d.medium + d.low;
  if (total === 0) {
    ctx.getContext('2d').fillText('Aucune CVE à répartir', 10, 20);
    return;
  }

  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'],
      datasets: [{
        data: [d.critical, d.high, d.medium, d.low],
        backgroundColor: [
          SEVERITY_COLORS.CRITICAL,
          SEVERITY_COLORS.HIGH,
          SEVERITY_COLORS.MEDIUM,
          SEVERITY_COLORS.LOW,
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
          formatter: (v, ctx) => {
            const sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
            const pct = sum > 0 ? Math.round((v / sum) * 100) : 0;
            return pct >= 5 ? `${pct}%` : '';
          },
        },
        title: { display: true, text: 'Repartition globale par sévérité' },
      },
    },
  });
}

/* Ouverture / fermeture des modales "Applications impactées" (une par CVE récente) */
$('.js-ouvrir-dc-new-cve').on('click', function () {
  modalSafe.open(`#${$(this).data('modal-id')}`);
});

$('.js-fermer-dc-new-cve').on('click', function () {
  modalSafe.close(`#${$(this).data('modal-id')}`);
});

/* Ouverture de la modale "Méthode de calcul du JH" */
$('.js-ouvrir-methodology-jh').on('click', () => {
  modalSafe.open('#dc-jh-methodology-modal');
});

/* Fermeture de la modale "Méthode de calcul du JH" */
$('#bouton-fermer-methodology-jh').on('click', () => {
  modalSafe.close('#dc-jh-methodology-modal');
});
