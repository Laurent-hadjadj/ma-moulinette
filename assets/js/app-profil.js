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

import {encode} from './core/encode.js'

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
import {http_200, http_202, http_400, http_403, contentType, paletteCouleur, matrice, dateOptions, dateOptionsShort} from './constante.js';

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
    case http_200 :
      message='Mise à jour de la liste effectuée.';
      $('#message').html(callboxSuccess+message+callboxFermer);
      /** On efface le message flash */
      $('.js-message').remove();
      break;
    case http_202:
      message=`Vous devez au moins avoir un profil déclaré sur le serveur SonarQube correspondant à la clé définie dans le fichier de propriétés de Ma-Moulinette.`;
      $('#message').html(callboxInformation+message+callboxFermer);
    break;
    case http_400:
      message=`La requête n'est pas conforme (Erreur 400).`;
      $('#message').html(callboxError+message+callboxFermer);
      break;
    case http_403:
      message=`Vous n'êtes pas autorisé à effectuer cette opération.`;
      $('#message').html(callboxWarning+message+callboxFermer);
      break;
    default:
      message=`Erreur lors de la mise à jour (${t.erreur}).`;
      $('#message').html(callboxError+message+`(${t.erreur}).`+callboxFermer);
  }

  let id= 0, str = '', total = 0;

  /** On efface le container */
  $('#js-container-langage').html('');
  /** on recréé le cointainer */
  const profils = t.listeProfil;
  profils.forEach(profil =>
  {
    id=id+1;
      str +=
      `<div class="callout secondary small-12 medium-6 langage-6 cell box-langage">
        <h3 class="h5">${profil.langage }</h3>
        <table class="hover">
          <thead>
            <tr>
              <th scope="col" class="open-sans text-center"></th>
              <th scope="col" class="open-sans text-center">Version</th>
              <th scope="col" class="open-sans text-center">Règle</th>
              <th scope="col" class="open-sans text-center">Date</th>
            </tr>
          </thead>
          <tbody>
            <tr class="open-sans">
              <td id="profil-${id}" class="js-profil-info" data-profil="{{profil.profil}}" data-language="${profil.langage}">
                <svg id="i-${id}" version="1.1" xmlns="http://www.w3.org/2000/svg" viewbox="0 0 512 512" class="profil-info-svg">
                  <path d="M168.6 1.6c-17.2 4.2-33.3 19.4-38.2 36.1l-1.5 5.3h-18.2c-15.3 0-18.6.3-20.6 1.6C86.9 46.9 85 52 85 58.5V64H66.3C38.4 64 28.9 67.1 16 80 8.6 87.4 3.7 95.8 1.5 105.1c-2.2 9.7-2.2 292.1 0 301.8C5 421.7 16.3 435.8 30 442.2c4.1 2 9.9 4.1 12.8 4.7 3.6.7 30.7 1.1 83.8 1.1 71.5 0 78.8-.1 81.4-1.7 5.5-3.2 6.7-11.2 2.6-16l-2.4-2.8-80.9-.5-80.8-.5-6.7-3.3c-7.6-3.7-12-8.2-15.7-16.2l-2.6-5.5v-291l3.3-6.7c3.7-7.6 8.2-12 16.2-15.7 5.2-2.5 6.5-2.6 24.8-2.9l19.2-.4v6.1c0 12.9 3.4 21.5 11.2 28.8 8.9 8.2 5.1 7.8 85.8 7.8h71.5l5.7-2.8c6.2-3.1 11.6-8.4 14.9-14.9 1.7-3.3 2.4-6.7 2.8-14.3l.6-10H297c19 0 19.6.1 25.1 2.6 6.9 3.3 12.7 8.9 16 15.5 2.4 4.9 2.4 5.2 2.9 42.9.5 41 .6 41.2 6 44 3.7 1.9 6.3 1.9 10.1-.1 5.6-2.8 5.9-5 5.9-41.1 0-38.4-.6-43.9-5.9-54.8-6.4-13.4-15.8-22-29.7-27.3-6.5-2.4-8.2-2.6-28.6-3l-21.8-.4v-7.3c0-6.4-.3-7.8-2.4-10.2l-2.4-2.8-19.3-.5-19.2-.5-1.2-4.1C224.6 11.5 195.9-5 168.6 1.6zm26 22.8c10.2 4.7 16.9 14.2 18.4 26 1.6 12.8 3.1 13.6 26 13.6h17v17c0 19.3-.8 22.2-6.5 24.6-5 2.1-130.9 2.1-135.9 0-6.2-2.6-6.6-4-6.6-23.8V64h17.1c22.3 0 23.8-.8 25.4-13.5 1.3-10.2 7.2-19.5 15.3-24.2 9.8-5.8 19.9-6.4 29.8-1.9z"/>
                  <path d="M69.3 172.2c-5.7 2.8-6.7 12.2-1.7 16.9l2.6 2.4 109.9.3c122.3.3 114.5.7 117.5-6.5 1.9-4.3.5-9.3-3.3-12.3-2.5-2-3.7-2-112.7-1.9-74.1 0-110.8.4-112.3 1.1zM69.3 236.2c-5.7 2.8-6.7 12.2-1.7 16.9l2.6 2.4 77.9.3c86.8.3 82.6.6 85.5-6.5 1.9-4.3.5-9.3-3.3-12.3-2.5-2-3.8-2-80.7-1.9-51.9 0-78.8.4-80.3 1.1zM351 236.4c-60.7 10.8-106.5 58-115.1 118.5-1.5 10.6-.7 36.1 1.5 46.1 5.8 26.5 17.9 49.2 36.7 68.5 20.1 20.7 43.1 33.4 71.7 39.7 14.5 3.2 40.7 3.2 55.1 0 28.5-6.4 50.2-18.2 70.2-38.1 19.9-20 31.7-41.7 38.1-70.2 3.2-14.4 3.2-40.6 0-55.1-3-13.7-6.6-23.7-12.7-35.6-7.4-14.3-15.1-24.5-27-36.1-19.1-18.6-41.5-30.7-68-36.6-9.7-2.2-40.5-2.9-50.5-1.1zm48 22.5c42.9 9.8 77.1 43.1 88 85.7 10.2 39.6-1.7 82.2-30.9 111.5-40.3 40.3-101.8 45.8-149.6 13.5-14.1-9.5-30.2-28.1-38.4-44.3-9.6-19.2-13.9-44.2-11.1-65.9 6.5-52 46.3-93.5 97.8-101.9 11.5-1.9 32.8-1.2 44.2 1.4z"/>
                  <path d="M365.9 301.9c-4.9 5-3.6 13.3 2.6 16.6 6.3 3.5 14-.4 15.2-7.8 1.5-9.7-10.8-15.8-17.8-8.8zM369.4 342.4c-6.6 2.9-6.4 1.3-6.4 51.8 0 31.5.3 46.5 1.1 48.2 2.8 6 12.2 7.1 17 2l2.4-2.6.3-45.9c.3-51.1.3-50.7-6.3-53.5-4.1-1.7-4.2-1.7-8.1 0zM69.3 300.2c-5.7 2.8-6.7 12.2-1.7 16.9l2.6 2.4h115.6l2.6-2.4c5.1-4.8 4-14.2-2-17-3.3-1.5-113.9-1.4-117.1.1z"/>
                </svg>
              </td>
              <td class="text-left">${ profil.profil }</td>
              <td class="text-center">${ new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(profil.regle) }</td>
              <td class="text-center">
                <span class="show-for-small-only"> ${ new Intl.DateTimeFormat('default', dateOptionsShort).format(new Date(profil.date)) }</span>
                <span class="show-for-medium">${ new Intl.DateTimeFormat('default', dateOptions).format(new Date(profil.date)) }</span>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="small-12 medium-6 large-6 cell">
							<p class="button expanded float-center bouton-profil-refresh js-bouton-autre-profil" data-language="${profil.langage}" id="language-${profil.langage}}">
								<button data-open="fenetre-modal" class="fenetre-modal">Afficher les autres profils</button>
							</p>
						</div>
					</div>
					<div class="reveal" id="fenetre-modal" data-reveal>
					</div>
      </div>`;
    total = total + profil.regle; });

  /** Affiche le container */
  $('#js-container-langage').html(str);
  $('.js-total').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(total));

  $('.js-bouton-autre-profil').on('click', (e)=>{
  });
};


const recupereProfilNonActif=async function(langage){
  /** Construction de la requete */
  const dataRefresh = { mode:'null', langage: langage};
  const optionsRefresh = {
        url: `${serveur()}/api/quality/off`, type: 'POST',
        dataType: 'json', data: JSON.stringify(dataRefresh), contentType };
  /** On appel l'API */
  const t = await $.ajax(optionsRefresh);

  let id= 0, str = '';

  $('#toto').html('');
  const profils = t.listeProfil;
  const nombreProfils = t.countProfil;
  // En tête du tableau
  console.log(nombreProfils.request.length);
  console.log(nombreProfils.request);

  if (nombreProfils.request[0].total > 1){
    str += `<h2 class="h5 claire-hand">Il y a ${ nombreProfils.request[0].total } profils diponibles dans Sonarqube</h2>`
  }else{
    str += `<h2 class="h5 claire-hand">Il y a ${ nombreProfils.request[0].total } profil diponible dans Sonarqube</h2>`
  }
  str += `<table class="hover">
  <thead>
    <tr>
      <th scope="col" class="open-sans text-center"></th>
      <th scope="col" class="open-sans text-center">Version</th>
      <th scope="col" class="open-sans text-center">Règle</th>
      <th scope="col" class="open-sans text-center">Date</th>
    </tr>
  </thead>`

  // Bloucle ur le profil pour construire le tablea
  profils.forEach(profil =>
    {
      id=id+1;
      str +=
      `   <tbody>
            <tr class="open-sans">
              <td></td>
              <td class="text-left">${ profil.profil }</td>
              <td class="text-center">${ new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(profil.regle) }</td>
              <td class="text-center">
                <span class="show-for-small-only"> ${ new Intl.DateTimeFormat('default', dateOptionsShort).format(new Date(profil.date)) }</span>
                <span class="show-for-medium">${ new Intl.DateTimeFormat('default', dateOptions).format(new Date(profil.date)) }</span>
              </td>
            </tr>
      </div>`;
    }
  )
  // Fin du tableau avec le boutton pour fermer la page
  str += `  </tbody>
          </table>
          <button class="close-button" data-close aria-label="Close reveal" type="button">
          <span aria-hidden="true">&times;</span>
          </button>`;

  $('#toto').html(str);
  $('#fenetre-modal').foundation('open');
}

$('.js-bouton-autre-profil').on('click', (e)=>{

  /* On récupère l'id */
  const target = e.currentTarget.id;
  const elm = document.getElementById(target);
  /* On récupère le nom du langage. */
  const language=elm.dataset.language;
  // Methode qui appel l'api et qui envoie le tableau qui ce trouveras dans la fenetre modal
  recupereProfilNonActif(language);
});

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

  /* on passe le mode à null */
  const mode='null';
  /* On récupère le nom du langage. */
  const language=elm.dataset.language;
  /* On récupère le nom du profil. */
  const profil=elm.dataset.profil;

  /** on créé un token pour encoder les paramètres */
  const parametre=`${mode}|${language}|${profil}`;
  const a=encode(btoa(parametre));
  location.href=`${serveur()}/profil/details?token=${a}`;
});

/**
 * Evenement
 * Appel la fonction de mise à jour de la liste des référentiels
 */
$('.js-profil-refresh').on('click', ()=>{
  refreshQuality();
});
