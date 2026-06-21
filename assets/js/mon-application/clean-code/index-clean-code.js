/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-05-17 : entry JS pour la page
 *  Clean Code Taxonomy. Construit 2 charts Chart.js depuis les données
 *  JSON embarquées dans la page : donut catégories + bar sévérités.
 *
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/clean-code.css';

import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';
import '../../common/foundation.js';

import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

Chart.register(...registerables);
Chart.register(ChartDataLabels);

/* Palette cohérente avec le reste de l'app. */
const CATEGORY_COLORS = [
    '#1779ba', /* CONSISTENT  — bleu   */
    '#3adb76', /* INTENTIONAL — vert   */
    '#ffae00', /* ADAPTABLE   — orange */
    '#cc4b37', /* RESPONSIBLE — rouge  */
];

const SEVERITY_COLORS = [
    '#8b0000', /* BLOCKER — bordeaux */
    '#cc4b37', /* HIGH    — rouge    */
    '#f08a24', /* MEDIUM  — orange   */
    '#1779ba', /* LOW     — bleu     */
    '#777777', /* INFO    — gris     */
];

document.addEventListener('DOMContentLoaded', () => {
    const node = document.getElementById('clean-code-chart-data');
    if (!node) { return; }

    let data;
    try {
        data = JSON.parse(node.textContent || '{}');
    } catch (e) {
        console.error('[Clean Code] JSON parse error', e);
        return;
    }

    /* ── Donut : catégories clean code ──────────────────────────── */
    const catCtx = document.getElementById('cc-categories-chart');
    if (catCtx && data.categories?.values?.length) {
        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: data.categories.labels,
                datasets: [{
                    data: data.categories.values,
                    backgroundColor: CATEGORY_COLORS,
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
                            return pct >= 3 ? `${pct}%` : '';
                        },
                    },
                    title: { display: false },
                },
            },
        });
    }

    /* ── Bar horizontal : sévérités impact ──────────────────────── */
    const sevCtx = document.getElementById('cc-severities-chart');
    if (sevCtx && data.severities?.values?.length) {
        new Chart(sevCtx, {
            type: 'bar',
            data: {
                labels: data.severities.labels,
                datasets: [{
                    label: 'Nb issues',
                    data: data.severities.values,
                    backgroundColor: SEVERITY_COLORS,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#333',
                        font: { size: 11 },
                        formatter: v => v > 0 ? v.toLocaleString('fr-FR') : '',
                    },
                },
                scales: {
                    x: { beginAtZero: true },
                },
            },
        });
    }

    /* MODIF 2026-05-19 : line chart évolution temporelle */
    const evoCtx    = document.getElementById('cc-evolution-chart');
    const evoNodata = document.getElementById('cc-evolution-nodata');
    const evoCanvas = document.getElementById('cc-evolution-canvas');
    if (evoNodata && evoCanvas) {
        if (data.evolution?.labels?.length >= 2) {
            evoNodata.setAttribute('hidden', '');
            evoCanvas.removeAttribute('hidden');
        }
    }
    if (evoCtx && data.evolution?.labels?.length >= 2) {
        new Chart(evoCtx, {
            type: 'line',
            data: {
                labels: data.evolution.labels,
                datasets: [
                    {
                        label: 'Score de risque (/4)',
                        data: data.evolution.risk,
                        borderColor: '#cc4b37',
                        backgroundColor: 'rgba(204,75,55,0.08)',
                        tension: 0.3,
                        yAxisID: 'yRisk',
                    },
                    {
                        label: 'RESPONSIBLE %',
                        data: data.evolution.resp,
                        borderColor: '#f08a24',
                        backgroundColor: 'rgba(240,138,36,0.08)',
                        tension: 0.3,
                        yAxisID: 'yPct',
                    },
                    {
                        label: 'Sécurité %',
                        data: data.evolution.security,
                        borderColor: '#1779ba',
                        backgroundColor: 'rgba(23,121,186,0.08)',
                        tension: 0.3,
                        yAxisID: 'yPct',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    datalabels: { display: false },
                },
                scales: {
                    yRisk: {
                        type: 'linear',
                        position: 'left',
                        min: 0,
                        max: 4,
                        title: { display: true, text: 'Score (/4)' },
                    },
                    yPct: {
                        type: 'linear',
                        position: 'right',
                        min: 0,
                        title: { display: true, text: '% issues' },
                        grid: { drawOnChartArea: false },
                    },
                },
            },
        });
    }
});
