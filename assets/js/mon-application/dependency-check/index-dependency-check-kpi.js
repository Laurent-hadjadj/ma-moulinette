/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-05-13 : entry JS pour la page KPIs / pilotage SLA.
 *  Lit le dataset injecté dans <script type="application/json" id="dc-kpi-data">
 *  et construit un line chart Chart.js tendance 90j (4 séries sévérité).
 *
 *  Pattern aligné sur index-dependency-check-history.js.
 *
 *  ---
 *  Vous pouvez obtenir une copie de la licence a l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/dependency-check.css';

import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import '../../common/messageHelper';

import { Chart, registerables } from 'chart.js';
import { showMessage } from '../../common/messageHelper';
Chart.register(...registerables);

const SEVERITY_COLORS = {
  CRITICAL: '#cc4b37',
  HIGH:     '#f08a24',
  MEDIUM:   '#ffd200',
  LOW:      '#1779ba',
};

$(function () {
  const dataElement = document.getElementById('dc-kpi-data');
  if (!dataElement) {
    return;
  }

  let data;
  try {
    data = JSON.parse(dataElement.textContent);
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    const message = '[DC KPI] JSON parse error';
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_error', message);
    return;
  }

  const canvas = document.getElementById('dc-kpi-trend');
  if (!canvas) {
    return;
  }

  new Chart(canvas.getContext('2d'), {
    type: 'line',
    data: {
      labels: data.labels || [],
      datasets: [
        {
          label: 'CRITICAL',
          data: data.critical || [],
          borderColor: SEVERITY_COLORS.CRITICAL,
          backgroundColor: SEVERITY_COLORS.CRITICAL,
          tension: 0.2,
        },
        {
          label: 'HIGH',
          data: data.high || [],
          borderColor: SEVERITY_COLORS.HIGH,
          backgroundColor: SEVERITY_COLORS.HIGH,
          tension: 0.2,
        },
        {
          label: 'MEDIUM',
          data: data.medium || [],
          borderColor: SEVERITY_COLORS.MEDIUM,
          backgroundColor: SEVERITY_COLORS.MEDIUM,
          tension: 0.2,
        },
        {
          label: 'LOW',
          data: data.low || [],
          borderColor: SEVERITY_COLORS.LOW,
          backgroundColor: SEVERITY_COLORS.LOW,
          tension: 0.2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
      },
      scales: {
        y: { beginAtZero: true, title: { display: true, text: 'Nb CVE' } },
        x: { title: { display: true, text: 'Date' } },
      },
    },
  });
});
