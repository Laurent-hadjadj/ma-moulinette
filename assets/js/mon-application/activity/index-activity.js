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
import { http_200, http_400, http_401, http_403, http_404, http_500, http_504, content_type } from '../../common/constante.js';

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
              url: `${serveur()}/api/secure/activity/sauvegarde`,
              type: 'POST',
              dataType: 'json',
              contentType: content_type,
              headers: {
                'X-API-Custom-403': 'true',
                'X-Internal-Front': 'front-app'
              },
          };

        /** On appel l'API */
        const t = await $.ajax(options);

        // 📌 Vérification des erreurs
        if (t.code === http_403) {
          showMessage('warning', `Vous n'êtes pas autorisé à effectuer cette opération (Erreur 403).`);
          return;
        }
        // 204 (aucune donnée à agréger) ou 500 (échec d'écriture) : on affiche le message
        // du serveur au lieu d'un « Aucune donnée disponible » trompeur.
        if (t.code !== http_200) {
          showMessage(t.type || 'error', t.message || 'Impossible de générer les statistiques.');
          return;
        }

        /** On efface le container */
        const stats = t.listeDonnee?.liste || [];
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
                <td>${stat.failed}</td>
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
        sessionStorage.setItem('ma_moulinette_error', `Erreur lors de la récupération des données : ${JSON.stringify(error, null, 2)}`);
        showMessage('critical', 'Une erreur globale est survenue lors de la mise à jour (Erreur 500).');
      } finally {
        // Cacher le spinner
        $spinner.hide();
      }
    };

$('.js-activity-refresh').on('click', () => {
  refreshActivity();
});

/**
 * [Description for graphToto]
 * MODIF 2026-10-16 : une seule vue (analyses + projets analysés par jour),
 * plus de source à choisir — les 2 anciens boutons mono-métrique étaient
 * strictement redondants avec cette vue combinée. Remplissage d'aire
 * (fill:true), même pattern que les 2 graphiques de la page Statistique
 * Utilisateur (app-statistique-utilisateur.js, generateChart()).
 *
 * @var function
 */
const graphToto = function(donnee) {
  const $emptyMessage = $('.js-chart-empty-message');
  const $canvas = $('#graphique-langage');

  if (!donnee || donnee.length === 0) {
    $canvas.hide();
    $emptyMessage.css('display', 'flex');
    return;
  }
  $canvas.show();
  $emptyMessage.css('display', 'none');

  const la = [];
  const analyse = [];
  const projet = [];
  donnee.forEach(element => {
    la.push(element.day);
    analyse.push(element.analyse);
    projet.push(element.projet);
  });

  // date-fns (moment n'est pas importé) : « 2026-07-15 » → « 15 juil. »
  const formattedLabels = la.map(date => format(new Date(date), 'dd MMM', { locale: fr }));

  const datasets = [
    {
      label: 'Analyses',
      data: analyse,
      borderColor: 'rgb(0, 68, 91)',
      backgroundColor: 'rgba(0, 68, 91, 0.25)',
      fill: true,
      tension: 0.4,
      yAxisID: 'y',
    },
    {
      label: 'Projets analysés',
      data: projet,
      borderColor: 'rgb(240, 138, 36)',
      backgroundColor: 'rgba(240, 138, 36, 0.25)',
      fill: true,
      tension: 0.4,
      yAxisID: 'y',
    },
  ];

  const data = {
    labels: formattedLabels,
    datasets: datasets
  };

  const options = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    stacked: false,
    plugins: {
      title: {
        display: true,
        text: 'Analyses et projets analysés par jour'
      },
      // Trop de points sur une année pour afficher une valeur par point (illisible).
      datalabels: {
        display: false
      }
    },
    scales: {
      // Les labels sont des dates déjà formatées (chaînes) → axe catégoriel.
      x: {
        type: 'category',
        display: true
      },
      y: {
        type: 'linear',
        display: true,
        position: 'left',
        beginAtZero: true
      }
    }
  };

  const chartStatus = Chart.getChart('graphique-langage');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-langage').getContext('2d');
  const charts = new Chart(ctx, { type: 'line', data, options });
  if (charts === null) {
    sessionStorage.setItem('ma_moulinette_info','youpi ! charts ne peut pas être null !!!');
  }
}

/**
 * [Description for dessineGraph]
 * Récupère les données (analyses + projets par jour, année courante) et
 * trace le graphique. Appelé automatiquement au chargement de la page —
 * plus besoin de clic puisqu'il n'y a plus qu'une seule vue possible.
 *
 * @var function
 */
const dessineGraph = async function() {
  const dataRefresh = { source: 'projet_analyse' };
  const optionsRefresh = {
    url: `${serveur()}/api/secure/activity/dessin`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(dataRefresh),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
    const t = await $.ajax(optionsRefresh);

    if (t.code === http_403) {
      showMessage('warning', `Vous n'êtes pas autorisé à effectuer cette opération (Erreur 403).`);
      return;
    }
    if (t.code !== http_200 || !t.listeDonnee?.request) {
      showMessage('error', `Impossible de récupérer les données du graphique (Erreur ${t.code || 500}).`);
      return;
    }

    graphToto(t.listeDonnee.request);
  } catch (error) {
    sessionStorage.setItem('ma_moulinette_error', `Erreur graphique : ${JSON.stringify(error, null, 2)}`);
    showMessage('critical', 'Une erreur est survenue lors du tracé du graphique (Erreur 500).');
  }
}

$(dessineGraph);
