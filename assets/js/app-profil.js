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

/** Librairie de tirage aléatoire */
import Chance from 'chance';

/** On importe les constantes */
import {contentType, paletteCouleur, matrice} from './constante.js';

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
  /** On crée un nouvel objet chance */
  const chance = new Chance();

  /** On mélange la matrice */
  for (i = a.length - 1; i > 0; i--) {
    j = chance.natural({ min: 0, max: i });
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
  matrice.forEach(el => {
    nouvellePalette.push(paletteCouleur[el]);
  });
  return nouvellePalette;
};

/**
 * [Description for chargeModification]
 *
 * @param mixed profil
 * @param mixed language
 *
 * @return [type]
 *
 * Created at: 11/03/2023, 22:59:17 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const chargeModification=function(profil, language) {
  const data = {mode: 'null', profil, language };
  const options = {
    url: `${serveur()}/api/quality/changement`, type: 'POST',
    dataType: 'json',  data: JSON.stringify(data), contentType };

    return new Promise(resolve => {
      $.ajax(options).then( t => {
          console.log(t);
      })
      resolve();
    });
  }

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
 * Appel la fonction d'affichage de la liste des modifications du profil.
 */
$('.js-profil-info').on('click', (e) => {
  /* On récupère l'id */
  const target = e.currentTarget.id;
  const elm = document.getElementById(target);

  /* On récupère le nom du profil. */
  const profil=elm.dataset.profil;
  /* On récupère le nom du profil. */
  const language=elm.dataset.language;
  chargeModification(profil,language)
});
