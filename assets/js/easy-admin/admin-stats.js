/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../styles/common/common.css';
import '../../styles/common/police.css';
import '../../styles/mon-application/admin-metrics.css';

import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../common/foundation.js';

import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

$(function () {
    const el = document.getElementById('admin-stats-data');
    if (!el) { return; }

    let stats;
    try {
        stats = JSON.parse(el.textContent);
    } catch (e) {
        console.error('[admin-stats] JSON parse error', e);
        return;
    }

    /* ---- Doughnut : répartition des anomalies ---- */
    const ctxAnomalies = document.getElementById('chart-anomalies');
    if (ctxAnomalies && stats.anomalies) {
        new Chart(ctxAnomalies, {
            type: 'doughnut',
            data: {
                labels: stats.anomalies.labels,
                datasets: [{
                    data: stats.anomalies.data,
                    backgroundColor: stats.anomalies.colors,
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: {
                        display: true,
                        text: 'Répartition des anomalies',
                        font: { size: 14 },
                    },
                },
            },
        });
    }

    /* ---- Bar : métriques projets ---- */
    const ctxProjets = document.getElementById('chart-projets');
    if (ctxProjets && stats.projets) {
        new Chart(ctxProjets, {
            type: 'bar',
            data: {
                labels: stats.projets.labels,
                datasets: [{
                    label: 'Nombre',
                    data: stats.projets.data,
                    backgroundColor: stats.projets.colors,
                    borderRadius: 4,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Métriques SonarQube',
                        font: { size: 14 },
                    },
                },
                scales: {
                    x: { beginAtZero: true },
                },
            },
        });
    }
});
