/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
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
import '../../../styles/mon-application/statistique-utilisateur.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';

/** Affiche le détails de l'utilisateur */
import '../../auth/details.js';

import {Chart, registerables} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import 'chartjs-adapter-date-fns';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels);

/**
 * [Description for darkenColor]
 *
 * @param mixed rgba
 * @param int factor
 *
 * @return string
 *
 * Created at: 18/05/2025 12:00:03 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const darkenColor = function(rgba, factor = 0.6) {
    const match = rgba.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    if (!match) return rgba;
    const [r, g, b] = match.slice(1).map(n => Math.max(0, Math.min(255, Math.floor(n * factor))));
    return `rgba(${r}, ${g}, ${b}, 1)`;
}

/**
 * [Description for generateChart]
 *
 * @param mixed canvasId
 * @param mixed plugins
 * @param mixed type
 * @param mixed labels
 * @param mixed datasets
 * @param mixed data
 * @param mixed label
 * @param mixed color
 * @param mixed extraOptions
 * @param boolean debug
 *
 * @return void
 *
 * Created at: 16/05/2025 14:16:37 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const generateChart = function({
    canvasId,
    plugins = [],
    type,
    labels = [],
    datasets = null,  // [{ label, data, backgroundColor, ... }]
    data = null,      // data simplifiée si pas de datasets[]
    label = '',
    color = null,
    extraOptions = {},
    debug = false
}) {
    const canvas = document.getElementById(canvasId);
    const emptyMessage = canvas?.nextElementSibling;

    // 📦 Fallback couleurs pour doughnut || pie
    const defaultColors = [
        '#4dc9f6', '#f67019', '#f53794', '#537bc4',
        '#acc236', '#166a8f', '#58595b', '#00a950'
    ];

    const finalDatasets = (datasets ?? [{
        label: label,
        data: data
    }]).map(ds => ({
        ...ds,
        backgroundColor: ds.backgroundColor
            ?? (type === 'doughnut' ? defaultColors : (color || 'rgba(75, 192, 192, 0.2)')),
        borderColor: ds.borderColor
            ?? ((type === 'bar' || type === 'line')
                ? (color ? darkenColor(color) : 'rgba(75, 192, 192, 1)')
                : undefined),
        borderWidth: ds.borderWidth ?? ((type === 'bar' || type === 'line') ? 2.5 : undefined),
        fill: ds.fill ?? (type === 'line'),
        tension: ds.tension ?? (type === 'line' ? 0.4 : undefined),
        cubicInterpolationMode: ds.cubicInterpolationMode ?? (type === 'line' ? 'monotone' : undefined)
    }));

    const hasNoData =
    (!labels || labels.length === 0) &&
    (!finalDatasets?.[0]?.data || finalDatasets[0].data.length === 0);

    const hasDatasetData =
    Array.isArray(finalDatasets?.[0]?.data) &&
    finalDatasets[0].data.length > 0 &&
    finalDatasets[0].data.some(p => p?.x !== undefined && p?.y !== undefined);

    // Cas sans données : on affiche le message HTML
    if (hasNoData || !hasDatasetData) {
        if (canvas) canvas.style.display = 'none';
        if (emptyMessage) emptyMessage.style.display = 'flex';
        return;
    }

    // Cas avec données : on cache le message HTML
    if (canvas) canvas.style.display = 'block';
    if (emptyMessage) emptyMessage.style.display = 'none';

    // Détruit l'ancien graphique si existant
    const oldChart = Chart.getChart(canvasId);
    if (oldChart) oldChart.destroy();

    const config = {
        type: type,
        plugins: plugins,
        data: {
            labels: labels,
            datasets: finalDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: type === 'doughnut' ? '65%' : undefined,
            plugins: {
                legend: {
                    display: true,
                    position: type === 'doughnut' ? 'bottom' : 'top',
                    labels: {
                        boxWidth: 14,
                        padding: 12
                    }
                }
            },
            ...extraOptions
        }
    };

    if (debug) {
        console.info('🧪 Chart config:', config);
    }
    const draw = new Chart(canvas, config);
    if (draw === null) {
        sessionStorage.setItem('assistant_ia_error', "c'est pour rire, il ne peut pas y avoir d'erreur ici :)");
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="period"]');
    const weekPicker = document.getElementById('week-picker');
    const monthPicker = document.getElementById('month-picker');
    const form = document.getElementById('period-form');

    function updatePickers() {
        const value = document.querySelector('input[name="period"]:checked')?.value;
        weekPicker.style.display = value === 'week' ? 'inline-block' : 'none';
        monthPicker.style.display = value === 'month' ? 'inline-block' : 'none';
    }

    radios.forEach(radio => {
        radio.addEventListener('change', () => {
        updatePickers();
        if (radio.value === 'day') {
            form.submit(); // aujourd’hui = direct
        }
    });
});

updatePickers();
});

/* ============ Affichage des graphs line ================== */
const canvasSessionDuration = document.getElementById('chart-avg-session-duration');
const canvasSessionUnique = document.getElementById('chart-nb-session-unique');

const rawData1 = canvasSessionDuration.dataset.avgSessionDuration;
const rawData2 = canvasSessionUnique.dataset.nbSessionUnique;

// => [{date: '2025-12-19', avg: 0.07}, {date: '2025-12-21', avg: 55.59}]
const sessionDurationParsed = JSON.parse(rawData1);
const sessionUniqueParsed = JSON.parse(rawData2);

// '2025-12-19', minutes
const points1 = sessionDurationParsed.map(item => ({
    x: item.date,
    y: item.avg
}));
const points2 = sessionUniqueParsed.map(item => ({
    x: item.date,
    y: item.session
}));

/* On construits la courbe des moyenne de temps de travail */
generateChart({
    canvasId: 'chart-avg-session-duration',
    type: 'line',
    labels: [],
    datasets: [{
        label: 'Durée moyenne par jour (minutes).',
        data: points1
    }],
    color: 'rgba(75, 192, 192, 0.2)',
    extraOptions: {
        maintainAspectRatio: false,
        plugins: {
            datalabels: {
                formatter: (value) => value.y,
                align: 'top',
                anchor: 'end'
            }
        },
        scales: {
        x: {
            type: 'time',
            time: { unit: 'day' }
            }
        }
    }
});

/* On construits la courbe des session de travail unique */
generateChart({
    canvasId: 'chart-nb-session-unique',
    type: 'line',
    labels: [],
    datasets: [{
        label: 'Nombre de session de travail unique.',
        data: points2
    }],
    color: 'rgba(91, 44, 111, 0.2)',
    extraOptions: {
        maintainAspectRatio: false,
        plugins: {
            datalabels: {
                formatter: (value) => value.y,
                align: 'top',
                anchor: 'end'
            }
        },
        scales: {
        x: {
            type: 'time',
            time: { unit: 'day' }
        }
    }
    }
});
