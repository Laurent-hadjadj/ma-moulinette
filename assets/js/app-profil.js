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
import {serveur} from './properties.js';

import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import zoomPlugin from 'chartjs-plugin-zoom';
Chart.register(ChartDataLabels);
Chart.register(zoomPlugin);

/** On importe les constantes */
import {contentType, paletteCouleur, matrice,
        dateOptions, dateOptionsShort} from './constante.js';

/**
 * [Description for shuffle]
 * Mélangeur de couleur
 *
 * @param mixed a
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:51:11 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
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
 * [Description for palette]
 * Renvoie une nouvelle palette de couleur
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:52:05 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const palette=function() {
  const nouvellePalette = [];
  shuffle(matrice);
  matrice.forEach(el=> {
    nouvellePalette.push(paletteCouleur[el]);
  });
  return nouvellePalette;
};

/**
 * [Description for refreshQuality]
 * Met à jour la liste des référentiels
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:52:39 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const refreshQuality=function() {
  const options = {
    url: `${serveur()}/api/quality/profiles`, type: 'GET',
    dataType: 'json',  contentType };

  return $.ajax(options)
    .then( r => {
      let statut1='', statut2='', str='', total=0;

      /** On efface le tableau */
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
                      ${new Intl.DateTimeFormat('default', dateOptionsShort).format(new Date(profil.date))}
                    </span>
                    <span class="show-for-medium">${new Intl.DateTimeFormat('default', dateOptions).format(new Date(profil.date))}
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
 * [Description for dessineMoiUnMouton]
 * Affiche le graphique des sources
 *
 * @param mixed label
 * @param mixed dataset
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:53:26 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const dessineMoiUnMouton=function(label, dataset) {
  const nouvellePalette = palette();
  const data =
  {
    labels: label,
    datasets: [{
      data: dataset, backgroundColor: nouvellePalette, borderWidth: 1,
      datalabels: { align: 'center', anchor: 'center'}}]};

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

/** Création du graphique par language */
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
      /** On appel la fonction de dessin */
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
