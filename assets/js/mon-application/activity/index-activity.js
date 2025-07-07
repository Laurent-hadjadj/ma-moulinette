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
import '../../../styles/mon-application/activity.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import '../../auth/details.js';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe les constantes */
import { http_200, http_400, http_401, http_403, http_404, http_500, http_504, contentType } from '../../common/constante.js';

import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import zoomPlugin from 'chartjs-plugin-zoom';
import 'chartjs-adapter-date-fns';
import { parse, format } from 'date-fns';
import { fr } from 'date-fns/locale';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels);
Chart.register(zoomPlugin);

/**
 * refreshActivity
 * On met à jour la table des activité pour l'année courante
 *
 * @var void
 */
const refreshActivity=async function() {
    const $spinner = $('.js-spinner');
    const $dateEnregistrement = $('#js-date-enregistrement');
    const $tableauStats = $('#js-tableau-stats');
    let str = '';

  // Afficher le spinner
  $spinner.show();

  try {
        // Configuration de l'appel Ajax
        const options = {
              url: `${serveur()}/api/activity/sauvegarde`,
              type: 'POST',
              dataType: 'json',
              contentType
          };

        /** On appel l'API */
        const t = await $.ajax(options);
        // 📌 Vérification des erreurs
        if (t.code === http_403) {
          showMessage('warning', `<strong>[ACTIVITÉ]</strong> - Vous n'êtes pas autorisé à effectuer cette opération (Erreur 403).`);
          return;
        }

        /** On efface le container */
        const stats = t.listeDonnee?.request || [];
        $dateEnregistrement.html('');
        if (stats.length > 0) {
          str += `<p><strong>Dernière date d'enregistrement : </strong><span class="couleur-changement">${stats[0].date_enregistrement}</span></p>`;
          $dateEnregistrement.html(str);
          /** On vide le tableau */
          $tableauStats.html('');
          stats.forEach(stat => {
            str += `
              <tr>
                <td>${stat.year}</td>
                <td>${stat.day}</td>
                <td>${stat.analyse}</td>
                <td>${stat.analyse_average}</td>
                <td>${stat.success}</td>
                <td>${stat.fail}</td>
                <td>${stat.success_rate} %</td>
                <td>${stat.max_time}</td>
              </tr>`;
          });
          $tableauStats.html(str);
        } else {
          $tableauStats.html('<tr><td colspan="8">Aucune donnée disponible.</td></tr>');
        }
      } catch(error) {
        // Gestion des erreurs
        sessionStorage.setItem('error', `Erreur lors de la récupération des données : ${JSON.stringify(error, null, 2)}`);
        showMessage('alert', '<strong>[ACTIVITÉ]</strong> - Une erreur est survenue lors de la mise à jour.');
      } finally {
        // Cacher le spinner
        $spinner.hide();
      }
    };

$('.js-activity-refresh').on('click', () => {
  refreshActivity();
});

const graphToto = function(type, donnee, source) {
  let datasets = [];
  const la = [];

  if (source === 'projet_analyse') {
    const analyse = [];
    const projet = [];
    donnee.forEach(element => {
      la.push(element.day);
      analyse.push(element.analyse);
      projet.push(element.projet);
    });
    datasets.push({
      label: 'Analyse',
      data: analyse,
      borderColor: getRandomColor(),
      backgroundColor: transparentize(getRandomColor(), 0.5),
      yAxisID: 'y',
    });
    datasets.push({
      label: 'Projet',
      data: projet,
      borderColor: getRandomColor(),
      backgroundColor: transparentize(getRandomColor(), 0.5),
      yAxisID: 'y',
    });
  } else {
    const da = [];
    donnee.forEach(element => {
      la.push(element.day);
      da.push(element.count);
    });
    datasets.push({
      label: source,
      data: da,
      borderColor: getRandomColor(),
      backgroundColor: transparentize(getRandomColor(), 0.5),
      yAxisID: 'y',
    });
  }

  const formattedLabels = la.map(date => moment(date).format('DD-MMMM').slice(0, 6));

  function getRandomColor() {
    const r = Math.floor(Math.random() * 256);
    const g = Math.floor(Math.random() * 256);
    const b = Math.floor(Math.random() * 256);
    return `rgb(${r}, ${g}, ${b})`;
  }

  function transparentize(color, opacity) {
    const alpha = opacity === undefined ? 0.5 : 1 - opacity;
    return color.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
  }

  const data = {
    labels: formattedLabels,
    datasets: datasets
  };

  const options = {
    responsive: true,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    stacked: false,
    plugins: {
      title: {
        display: true,
        text: 'Chart.js Graphique linéaire'
      }
    },
    scales: {
      x: {
        type: 'timeseries',
        time: {
          unit: 'day',
          unitStepSize: 1,
          tooltipFormat: 'll',
          displayFormats: { 'day': 'dd/MM/yyyy' }
        },
        ticks: {
          callback: function(value, index, values) {
            // Utilise date-fns pour formater la date en français
            const formattedDate = format(new Date(value), 'dd LLL yyyy', { locale: fr });
            return formattedDate;  // Retourner la date formatée
          }
        },
        display: true
      },
      y: {
        type: 'linear',
        display: true,
        position: 'left',
      },
      y1: {
        type: 'linear',
        display: true,
        position: 'right',
        grid: {
          drawOnChartArea: false,
        },
      },
    }
  };

  const chartStatus = Chart.getChart('graphique-langage');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-langage').getContext('2d');
  const charts = new Chart(ctx, { type: 'line', data, options });
  if (charts === null) {
    sessionStorage.setItem('info','youpi ! charts ne peut pas être null !!!');
  }
}

const dessineGraph = async function(type, source) {
  const dataRefresh = { source: source };
  const optionsRefresh = {
    url: `${serveur()}/api/activity/dessin`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(dataRefresh),
    contentType
  };

  const t = await $.ajax(optionsRefresh);

  const donnee = t.listeDonnee.request;
  graphToto(type, donnee, source);
}

$('.js-show-graph').on('click', (e) => {
  const target = e.currentTarget.id;
  const elm = document.getElementById(target);
  const source = elm.dataset.source;
  const type = elm.dataset.type;
  dessineGraph(type, source);
});
