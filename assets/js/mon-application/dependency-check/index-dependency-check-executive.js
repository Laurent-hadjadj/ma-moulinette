/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-05-09 : entry JS pour la vue exécutive
 *  prise de décision (page /dependency-check/projet/.../executive).
 *  Lit le dataset injecté dans <script type="application/json" id="dc-exec-data">
 *  et construit 4 charts Chart.js : pie sévérité, bar familles, radar CWE,
 *  bar CVE prioritaires.
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
import '../../../styles/mon-application/dependency-check.css';

/** Intégration de jQuery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import '../../common/messageHelper.js';
import '../../common/safeModal.js';

/** Chart.js + plugin datalabels (alignement avec les autres pages chart) */
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(...registerables);
Chart.register(ChartDataLabels);

const SEVERITY_COLORS = {
  CRITICAL: '#cc4b37',
  HIGH:     '#f08a24',
  MEDIUM:   '#ffd200',
  LOW:      '#1779ba',
  INFO:     '#9aa3ad',
};

document.addEventListener('DOMContentLoaded', () => {
  const dataNode = document.getElementById('dc-exec-data');
  if (!dataNode) {
    return;
  }

  let data;
  try {
    data = JSON.parse(dataNode.textContent || '{}');
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = '[DC executive] dataset JSON parse error';
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_error', message);
    return;
  }

  buildSeverityPie(data.severity_counts || {});
  buildFamilyBar(data.family_breakdown || []);
  buildCweRadar(data.top_cwes || []);
  buildPriorityBar(data.top_priority_cves || []);
});

/**
 * [Description for buildSeverityPie]
 *  On construit le graph de répartition des sévérités
 * @param mixed counts
 *
 * Created at: 10/05/2026 22:17:43 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildSeverityPie = function(counts) {
  const ctx = document.getElementById('dc-exec-severity-pie');
  if (!ctx) { return; }
  const labels = ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'INFO'];
  const values = labels.map((k) => counts[k] || 0);
  const colors = labels.map((k) => SEVERITY_COLORS[k]);

  new Chart(ctx, {
    type: 'pie',
    data: {
      labels,
      datasets: [{ data: values, backgroundColor: colors, borderWidth: 1 }],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' },
        title: { display: true, text: 'Répartition des CVE par criticité' },
        datalabels: {
          color: '#fff',
          font: { weight: 'bold' },
          formatter: (v) => (v > 0 ? v : ''),
        },
      },
    },
  });
}

/**
 * [Description for buildFamilyBar]
 * Construction du graph baton pour les familles de jar
 *
 * @param mixed families
 *
 * Created at: 10/05/2026 22:19:22 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildFamilyBar = function(families) {
  const ctx = document.getElementById('dc-exec-family-bar');
  if (!ctx || !Array.isArray(families) || families.length === 0) { return; }
  const labels = families.map((f) => f.family);
  const values = families.map((f) => f.nb_cves);

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'CVE par famille',
        data: values,
        backgroundColor: '#00445b',
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'Vulnérabilités par famille technologique' },
        datalabels: { anchor: 'end', align: 'right', color: '#333' },
      },
      scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  });
}

/**
 * [Description for buildCweRadar]
 * Construction du radar pour les CWE
 *
 * @param mixed cwes
 *
 * Created at: 10/05/2026 22:20:26 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const buildCweRadar = function(cwes) {
  const ctx = document.getElementById('dc-exec-cwe-radar');
  if (!ctx || !Array.isArray(cwes) || cwes.length === 0) { return; }
  const labels = cwes.map((c) => c.cwe);
  const values = cwes.map((c) => c.nb);

  new Chart(ctx, {
    type: 'radar',
    data: {
      labels,
      datasets: [{
        label: 'Occurrences',
        data: values,
        backgroundColor: 'rgba(204, 75, 55, 0.2)',
        borderColor: '#cc4b37',
        pointBackgroundColor: '#cc4b37',
        pointRadius: 4,
      }],
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'Top CWE par occurrence' },
        datalabels: { display: false },
      },
      scales: { r: { beginAtZero: true, ticks: { precision: 0 } } },
    },
  });
}

/**
 * [Description for buildPriorityBar]
 * On construit le graph en baton pour les priorités de correction
 * @param mixed cves
 *
 * Created at: 10/05/2026 22:23:09 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const  buildPriorityBar = function(cves) {
  const ctx = document.getElementById('dc-exec-priority-bar');
  if (!ctx || !Array.isArray(cves) || cves.length === 0) { return; }
  const labels = cves.map((c) => c.cve_id);
  const values = cves.map((c) => (c.cvss !== null && c.cvss !== undefined ? c.cvss : 0));
  const colors = cves.map((c) => SEVERITY_COLORS[c.severity] || '#9aa3ad');

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Score CVSS',
        data: values,
        backgroundColor: colors,
      }],
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { display: false },
        title: { display: true, text: 'CVE prioritaires (CVSS décroissant)' },
        datalabels: {
          anchor: 'end',
          align: 'right',
          color: '#333',
          formatter: (v) => (v > 0 ? v : ''),
        },
      },
      scales: { x: { beginAtZero: true, max: 10, ticks: { precision: 1 } } },
    },
  });
}

/* Overture de la modale "Méthode de calcul du JH" */
$('#bouton-ouvrir-methodology-jh').on('click', () => {
  modalSafe.open('dc-jh-methodology-modal');
});

/* Fermeture de la modale "Méthode de calcul du JH" */
$('#bouton-fermer-methodology-jh').on('click', () => {
  modalSafe.close('dc-jh-methodology-modal');
});
