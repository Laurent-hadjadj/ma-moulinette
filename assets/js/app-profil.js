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

import '../css/profil.css';

// Intégration de jquery
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

// On importe les paramètres serveur
import {serveur} from "./properties.js";

import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import zoomPlugin from 'chartjs-plugin-zoom';
Chart.register(ChartDataLabels);
Chart.register(zoomPlugin);

const contentType = 'application/json; charset=utf-8';
const matrice = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
  16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31];
const paletteCouleur = [
  '#065535', '#133337', '#000000', '#ffc0cb', '#008080', '#ff0000', '#ffd700', '#666666',
  '#ff7373', '#fa8072', '#800080', '#800000', '#003366', '#333333', '#20b2aa', '#ffc3a0',
  '#f08080', '#66cdaa', '#f6546a', '#ff6666', '#468499', '#c39797', '#bada55', '#ff7f50',
  '#660066', '#008000', '#088da5', '#808080', '#8b0000', '#0e2f44', '#3b5998', '#cc0000' ];

/**
 * description
 * Mélangeur de couleur
 *
 * @param {*} a
 * @returns
 */
const shuffle=function(a) {
  let j, x, i;
  for (i = a.length - 1; i > 0; i--) {
    j = Math.floor(Math.random() * (i + 1));
    x = a[i];
    a[i] = a[j];
    a[j] = x;
  }
  return a;
};

/**
 * description
 * Renvoie une nouvelle palette de couleur
 *
 * @returns
 */
const palette=function() {
  const nouvellePalette = [];
  shuffle(matrice);
  matrice.forEach(el=> {
    nouvellePalette.push(paletteCouleur[el]);
  });
  return nouvellePalette;
};

const dateOptions1 = {year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric',
                      second: 'numeric', hour12: false };
const dateOptions2 = {year: 'numeric', month: 'numeric', day: 'numeric' };

/**
 * Description
 * Met à jour la liste des référentiels
 * @returns
 */
const refreshQuality=function() {
  const options = {
    url: `${serveur()}/api/quality/profiles`, type: 'GET',
    dataType: 'json',  contentType };

  return $.ajax(options)
    .then( r => {
      let statut1='', statut2='', str='', total=0;

      // On efface le tableau
      $('#tableau-liste-profil').html('');
      const profils=r.listeProfil;

      profils.forEach( profil => {
        if (profil.actif === 1) {
          statut1='Oui';
          statut2='O';
        } else {
            statut1='Non';
            statut2='N';
        }

        str +=`<tr class="open-sans">
                <td>${profil.profil}</td>
                <td class="text-center">${profil.langage}</td>
                <td class="text-center">${profil.langage}
                    ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(profil.regle)}</td>
                <td class="text-center">
                    <span class="show-for-small-only">
                      ${new Intl.DateTimeFormat('default', dateOptions2).format(new Date(profil.date))}
                    </span>
                    <span class="show-for-medium">${new Intl.DateTimeFormat('default', dateOptions1).format(new Date(profil.date))}
                    </span>
                </td>
                <td class="text-center">
                    <span class="show-for-small-only">${statut2}</span>
                    <span class="show-for-medium">${statut1}</span>
                </td>
              </tr>`;
        total = total + profil.regle;
      });

      $('#tableau-liste-profil').html(str);
      $('.js-total').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(total));
    });
};

/**
 * Description
 * Affiche le graphique des sources
 *
 * @param {*} label
 * @param {*} dataset
 */
const dessineMoiUnMouton=function(label, dataset) {

  const nouvellePalette = palette();
  const data =
  {
    labels: label,
    datasets: [{
      data: dataset, backgroundColor: nouvellePalette, borderWidth: 1,
      datalabels: { align: 'center', anchor: 'center'}}]
  };

  const options = {
    animations: { tension: { duration: 2000, easing: 'linear', loop: false } },
    maintainAspectRatio: true,
    responsive: true,
    plugins: {
      title: { display: false },
      tooltip: { enabled: true },
      legend: {},
      datalabels: {
        color: '#fff',
        font: function (context) {
          const w = context.chart.width;
          return {
            size: w < 512 ? 12 : 14, weight: 'bold'};
        }
      }
    }
  };

  const chartStatus = Chart.getChart('graphique-langage');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-langage').getContext('2d');
  const charts = new Chart(ctx, { type: 'doughnut', data, options });
  if (charts === null) {
    console.info('youpi ! charts ne peut pas être null !!!');
  }
};

// Création du graphique par language
$('.graphique-langage').on('click', () => {
    const options = {
          url: `${serveur()}/api/quality/langage`, type: 'GET',
          dataType: 'json', contentType };

  return $.ajax(options)
    .then(t => {
      /*
       * const label = t.label;
       * const dataset = t.dataset;
       */

      const {label, dataset} = t;
      // On appel la fonction de dessin
    dessineMoiUnMouton(label, dataset);
    });
});

/**
 * Evenement
 * Appel la fonction de mise à jour de la liste des référentiels
 */
$('.refresh').on('click', ()=>{
  refreshQuality();
});
