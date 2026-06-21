/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-05-13 : entry JS pour
 *  la page d'historique d'une application DC. Lit le dataset injecté dans
 *  <script type="application/json" id="dc-history-data"> et construit un
 *  line chart Chart.js (4 séries : CRITICAL/HIGH/MEDIUM/LOW).
 *
 *  Pattern aligné sur index-dependency-check-dashboard.js.
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

import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const SEVERITY_COLORS = {
  CRITICAL: '#cc4b37',
  HIGH:     '#f08a24',
  MEDIUM:   '#ffd200',
  LOW:      '#1779ba',
};

$(function () {
  const dataElement = document.getElementById('dc-history-data');
  if (!dataElement) {
    return;
  }

  let data;
  try {
    data = JSON.parse(dataElement.textContent);
  } catch (e) {
    console.error('[DC History] JSON parse error', e);
    return;
  }

  const canvas = document.getElementById('dc-history-chart');
  if (!canvas) {
    return;
  }

  // Tooltip enrichi : affiche la version du projet sous la date.
  const versions = Array.isArray(data.versions) ? data.versions : [];

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
        tooltip: {
          callbacks: {
            title: (items) => {
              if (!items.length) return '';
              const idx = items[0].dataIndex;
              const date = items[0].label;
              const version = versions[idx] ? ` (v${versions[idx]})` : '';
              return `${date}${version}`;
            },
          },
        },
      },
      scales: {
        y: { beginAtZero: true, title: { display: true, text: 'Nb CVE' } },
        x: { title: { display: true, text: 'Date du scan' } },
      },
    },
  });
});
