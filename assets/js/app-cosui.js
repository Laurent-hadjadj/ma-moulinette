/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

import '../css/cosui.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';
import './app-authentification-details.js';

/** On importe les constantes */
import { zero, dix, vingt, trente, quarante, cinquante, soixante, soixanteDix, cent, quatreVingt } from './constante.js';

/** Gestion des graphiques */
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(ChartDataLabels);

const ouiNon='#js-oui-non';
const upDownEqual='.up, .down, .equal';

/**
 * The function `dessineMoiUnRadar(dataset, labels)` is responsible for drawing a radar chart using the Chart.js library.
 *
 * @function
 * @name dessineMoiUnRadar
 * @kind function
 * @param {any} dataset
 * @param {any} labels
 * @returns {void}
 */
const dessineMoiUnRadar=function dessineMoiUnRadar(dataset1, dataset2, label1, label2){
  /** Préparation du radar */
  const data = {
    labels: ['Fiabilité','Vulnérabilité','Hotspot', 'Maintenabilité','Couverture','Dette'],
    datasets: [
    {
      label:'v'+label1,
      backgroundColor: 'rgba(255, 99, 132, 0.2)',
      borderColor: 'rgba(255, 99, 132, 0.5)',
      pointBackgroundColor: '#FF6384',
      pointBorderColor: '#fff',
      pointHoverBackgroundColor: '#fff',
      pointHoverBorderColor: '#FF6384',
      data: dataset1,
      fill: true,
      tension: 0.2 },
      {
        label:'v'+label2,
        backgroundColor: 'rgba(0, 68, 91, 0.2)',
        borderColor: 'rgba(0, 68, 91, 0.5)',
        pointBackgroundColor: '#00445b',
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: '#00445b',
        data: dataset2,
        fill: true,
        tension: 0.2 } ]};

    const options = {
      aspectRatio:2,
      maintainAspectRatio: true,
      responsive: true,
      plugins: {
        tooltip: { enabled: true,
          callbacks: {
            label: context=> {
                switch (context.label) {
                  case 'Fiabilité' :
                  case 'Vulnérabilité' :
                  case 'Maintenabilité' :
                    if (context.parsed.r===cent){
                      return ' Note : A';
                    }
                    if (context.parsed.r===quatreVingt){
                      return ' Note : B';
                    }
                    if (context.parsed.r===soixante){
                      return ' Note : C';
                    }
                    if (context.parsed.r===trente){
                      return ' Note : D';
                    }
                    if (context.parsed.r===dix){
                      return ' Note : E';
                    }
                    if (context.parsed.r===zero){
                      return ' Note : Z';
                    }
                  break;
                  case 'Hotspot' :
                  if (context.parsed.r>=quatreVingt){
                    return ' Note : A';
                  }
                  if (context.parsed.r>=soixanteDix && context.parsed.r<quatreVingt){
                      return ' Note : B';
                    }
                  if (context.parsed.r>=cinquante && context.parsed.r<soixanteDix){
                      return ' Note : C';
                    }
                  if (context.parsed.r>=trente && context.parsed.r<quarante){
                      return ' Note : D';
                    }
                  if (context.parsed.r<trente){
                    return ' Note : E';
                  }
                  break;
                  case 'Dette' :
                  case 'Couverture' :
                    if (context.parsed.r>=quatreVingt){
                        return ' Note : A';
                      }
                    if (context.parsed.r>=soixante && context.parsed.r<quatreVingt){
                      return ' Note : B';
                    }
                    if (context.parsed.r>=quarante && context.parsed.r<soixante){
                      return ' Note : C';
                    }
                    if (context.parsed.r>=vingt && context.parsed.r<quarante){
                      return ' Note : D';
                    }
                    if (context.parsed.r<vingt){
                      return ' Note : E';
                    }
                  break;
                  default:
                    sessionStorage.setItem('error', `Ooups, le label ${context.label} n'est pas pris en charge.`);
                }
            }
          },
        },
        legend: { position: 'bottom' },
        datalabels: {
          display: true,
          color: '#000' } }};

    const chartStatus = Chart.getChart('graphique-note');
    if (chartStatus !== undefined) {
      chartStatus.destroy();
    }

    const ctx = document.getElementById('graphique-note').getContext('2d');
    const charts = new Chart(ctx, { type: 'radar', data, options });
    if (charts === null) {
      sessionStorage.setItem('error', "c'est pour rire, il ne peut pas y avoir d'erreur ici :)");
    }

  };

/**
 * description
 * On affiche/desactive les indicateurs de variation
 * Faux positif : sonarLint(javascript:S1192)
 * @constant
 * @name ouiNon
 * @type {"#js-oui-non"}
 */
$(ouiNon).on('click', function () {
  if ($(ouiNon).is(':checked')===true) {
    $(upDownEqual).removeClass('hide');
  }

  /** On en fait deux pour être certain de capter l'evenenent */
  if ( $(ouiNon).is(':checked') === false && $(upDownEqual).hasClass('hide') === false ) {
      $(upDownEqual).addClass('hide');
    }
});

/**
 * On affiche les indicateurs du projet de référence
 *
 * @method
 * @name JQuery
 */
$('.js-affiche-projet-reference').on('click', function () {
  $('#modal-projet-reference').foundation('open');
});

/**
 * selectVersion
 *
 * @var [type]
 */
const mouton=async function() {
  const data={ mode: 'null' };
  const options = {
    url: `${serveur()}/api/liste/version`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  const r = await $.ajax(options);
  if (r.code===http_400) {
    const message=`Une erreur s'est produite lors de l'analyse des données !`;
    $('#message-ajout-projet').html(callboxError+message+callboxFermer);
    return;
  }

  $('.js-version').select2({
    placeholder: 'Cliquez pour ouvrir la liste',
    selectOnClose: true,
    width: '100%',
    minimumResultsForSearch: 5,
    language: 'fr',
    data: r.liste
  });
  $('.analyse').removeClass('hide');
  };

/** Affiche le radar des indicateurs clés */
const elm=document.getElementById('js-series');
let s1=[], s2=[];

// ['Fiabilité','Vulnérabilité','Hotspot', 'Maintenabilité','Couverture','Dette']
elm.dataset.seriea.split(',').forEach(element => {
  s1.push(parseInt(element,10));
});
elm.dataset.serieb.split(',').forEach(element => {
  s2.push(parseInt(element,10));
});

dessineMoiUnRadar(s1, s2, elm.dataset.labela, elm.dataset.labelb);
