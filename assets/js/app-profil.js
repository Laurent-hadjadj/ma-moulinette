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
import './app-authentification-details.js';

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
import {contentType, paletteCouleur, matrice, dateOptions, dateOptionsShort} from './constante.js';

/* Construction des callbox de type success */
const callboxInformation='<div id="js-message" class="callout alert-callout-border primary" data-closable="slide-out-right" role="alert"><p class="open-sans color-bleu padding-right-1"><span class="lead">Information ! </span>';
const callboxSuccess='<div id="js-message" class="callout alert-callout-border success" data-closable="slide-out-right" role="alert"><span class="open-sans color-bleu padding-right-1"><span class="lead">Bravo ! </span> ';
const callboxWarning='<div id="js-message" class="callout alert-callout-border warning" data-closable="slide-out-right" role="alert"><span class="open-sans padding-right-1 color-bleu">span class="lead">Attention ! </strong>';
const callboxError='<div id="js-message" class="callout alert-callout-border alert" data-closable="slide-out-right"><span class="open-sans padding-right-1 color-bleu"><trong>Ooups ! </trong>';
const callboxFermer='</span><button class="close-button" aria-label="Fermer la fenêtre" type="button" data-close><span aria-hidden="true">&times;</span></button></div>';

/**
 * [Description for refreshQuality]
 *
 * @return [type]
 *
 * Created at: 07/05/2023, 21:02:59 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const refreshQuality=async function() {
  const dataRefresh = { mode:'null' };
  const optionsRefresh = {
        url: `${serveur()}/api/quality/profiles`, type: 'POST',
        dataType: 'json', data: JSON.stringify(dataRefresh), contentType };

  /** On appel l'API */
  const t = await $.ajax(optionsRefresh);
  let message='';
  switch (t.code) {
    case 200 :
      console.log('code 200');
      message='Mise à jour de la liste effectuée.';
      $('#message').html(callboxSuccess+message+callboxFermer);
      /** On efface le message flash */
      $('.js-message').remove();
      break;
    case 202:
      message=`Vous devez au moins avoir un profil déclaré sur le serveur SonarQube correspondant à la clé définie dans le fichier de propriétés de Ma-Moulinette.`;
      $('#message').html(callboxInformation+message+callboxFermer);
    break;
    case 400:
      message=`La requête n'est pas conforme (Erreur 400).`;
      $('#message').html(callboxError+message+callboxFermer);
      break;
    case 403:
      message=`Vous n'êtes pas autorisé à effectuer cette opération.`;
      $('#message').html(callboxWarning+message+callboxFermer);
      break;
    default:
      message=`Erreur lors de la mise à jour (${t.erreur}).`;
      $('#message').html(callboxError+message+`(${t.erreur}).`+callboxFermer);
  }

  let statut1 = '', statut2 = '', str = '', total = 0;

  // On efface le tableau
  $('#tableau-liste-profil').html('');
  const profils = t.listeProfil;
  profils.forEach(profil =>
  {
    if (profil.actif === 1)
    {
      statut1 = 'Oui';
      statut2 = 'O';
    } else
    {
      statut1 = 'Non';
      statut2 = 'N';
    }

    /** On construit le tableau */
    str += `<tr class="open-sans">
                <td></td>
                <td>${ profil.profil }</td>
                <td class="text-center">${ profil.langage }</td>
                <td class="text-center">
                    ${ new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(profil.regle) }
                </td>
                <td class="text-center">
                    <span class="show-for-small-only">
                      ${ new Intl.DateTimeFormat('default', dateOptionsShort).format(new Date(profil.date)) }
                    </span>
                    <span class="show-for-medium">${ new Intl.DateTimeFormat('default', dateOptions).format(new Date(profil.date)) }
                    </span>
                </td>
                <td class="text-center">
                    <span class="show-for-small-only">${ statut2 }</span>
                    <span class="show-for-medium">${ statut1 }</span>
                </td>
              </tr>`;
    total = total + profil.regle;
  });
  $('#tableau-liste-profil').html(str);
  $('.js-total').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(total));
};

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
    maintainAspectRatio: false,
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
$('.js-profil-graphique').on('click', async () => {
  const data = { mode:'null' };
  const options = {
          url: `${serveur()}/api/quality/langage`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  const t = await $.ajax(options);
  /**
   * const label = t.label;
   * const dataset = t.dataset;
   */
  const { label, dataset } = t;
  /** On appel la fonction de dessin */
  dessineMoiUnMouton(label, dataset);
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

  /** on ouvre la page de détails */
  const uri=`?language=${language}&profil=${profil}&mode=null`
  const url=`${serveur()}/profil/details${uri}`;

  $(location).prop('href', url);
});

/**
 * Evenement
 * Appel la fonction de mise à jour de la liste des référentiels
 */
$('.js-profil-refresh').on('click', ()=>{
  refreshQuality();
});
