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

import '../css/projet.css';

// Intégration de jquery
import $ from 'jquery';

import 'select2';
import 'select2/dist/js/i18n/fr.js';

import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(ChartDataLabels);

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

import {remplissage} from './app-projet-peinture.js';
import {enregistrement} from './app-projet-enregistrement.js';

const dateOptions = {
  year: 'numeric', month: 'numeric', day: 'numeric',
  hour: 'numeric', minute:'numeric', second: 'numeric',
  hour12: false};

const contentType='application/json; charset=utf-8';
const matrice = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17,
                 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31];
const paletteCouleur = [
  '#065535', '#133337', '#000000', '#ffc0cb', '#008080', '#ff0000', '#ffd700', '#666666',
  '#ff7373', '#fa8072', '#800080', '#800000', '#003366', '#333333', '#20b2aa', '#ffc3a0',
  '#f08080', '#66cdaa', '#f6546a', '#ff6666', '#468499', '#c39797', '#bada55', '#ff7f50',
  '#660066', '#008000', '#088da5', '#808080', '#8b0000', '#0e2f44', '#3b5998', '#cc0000'];

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
  matrice.forEach(el => {
    nouvellePalette.push(paletteCouleur[el]);
  });
  return nouvellePalette;
};

/**
 * description
 * Affiche le graphique des sources
 *
 * @param {*} label
 * @param {*} dataset
 */
const dessineMoiUnMouton=function(label, dataset) {
  const nouvellePalette = palette();
  const data = { labels: label,
                 datasets: [{ data: dataset, backgroundColor: nouvellePalette,
                              borderWidth: 1,
                              datalabels: { align: 'center', anchor: 'center' }
                           }]
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
        },
      }
    }
  };

  const chartStatus = Chart.getChart('graphique-autre-version');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-autre-version').getContext('2d');
  const charts = new Chart(ctx, { type: 'doughnut', data, options });
  if (charts===null){
    console.info('Pour éviter une erreur sonar !!!');
  }
};

/**
 * description
 * Affiche la log.
 *
 * @param {*} txt
 */
const log=function(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += `${new Intl.DateTimeFormat('default',
  dateOptions).format(new Date())} ${txt}\n`;
};

/**
 * description
 * Initialisation de la log.
 */
const ditBonjour=function() {
  log(' - Initialisation de la log...');
};

/**
 * description
 * Active la gomme pour nettoyer la log.
 */
$('.gomme-svg').on('click', function () {
  $('.log').val('');
});

/**
 * description
 * Active le spinner.
 */
const startSpinner=function() {
  if ($('#loader').hasClass('loader-disabled')) {
    $('#loader').removeClass('loader-disabled');
    $('#loader').addClass('loader-enabled');
  }
};

/**
 * description
 * Désactive le spinner.
 */
const stopSpinner=function() {
  if ($('#loader').hasClass('loader-enabled')) {
    $('#loader').removeClass('loader-enabled');
    $('#loader').addClass('loader-disabled');
  }
};

/**
 * description
 * Active la gomme pour nettoyer la log.
 */
$('.gomme-svg').on('click', function () {
  $('.log').val('');
});

/**
 * description
 * Propriétés du selecteur de recherche.
 *
 * @param {*} params
 * @param {*} data
 * @returns
 */
const match=function(params, data) {
  if ($.trim(params.term) === '') {
    return data;
  }
  if (typeof data.text === 'undefined') {
    return null;
  }

  if (data.text.indexOf(params.term) > -1) {
    const modifiedData = $.extend({}, data, true);
    modifiedData.text += ' (trouvé)';
    return modifiedData;
  }
  return null;
};

/**
 * description
 * Création du selecteur de projet.
 *
 * @returns
 */
const selectProjet=function() {
  const options = {
    url: 'http://localhost:8000/api/liste/projet', type: 'GET',
    dataType: 'json', contentType };

  return $.ajax(options)
    .then(function (data) {
      log(' - INFO : construction de la liste.');
      $('.js-projet').select2({
        matcher: match,
        placeholder: 'Cliquez pour ouvrir la liste',
        allowClear: true,
        width: '100%',
        minimumInputLength: 2,
        minimumResultsForSearch: 20,
        language: 'fr',
        data: data.liste});
      $('.analyse').removeClass('hide');
    });
};

/**
 * description
 * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version)
 *
 * @param {*} mavenKey
 * @returns
 */
const projetAnalyse=function(mavenKey) {
  const data = { mavenKey };
  const options = {
    url: 'http://localhost:8000/api/projet/analyses', type: 'GET',
    dataType: 'json', data, contentType };

  return $.ajax(options).then(t => {
      log(` - INFO : (1) Nombre de version disponible : ${t.nombreVersion}`);
    });
};

/**
 * description
 * Met à jour les indicateurs du projet (lignes, couvertures, duplication, défauts).
 *
 * @param {*} mavenKey
 * @returns
 */
 const projetMesure=function(mavenKey) {
  const data = { mavenKey };
  const options = {
    url: 'http://localhost:8000/api/projet/mesures', type: 'GET',
    dataType: 'json', data, contentType };
  return $.ajax(options).then(
    () => { log(' - INFO : (2) Ajout des mesures.'); });
};

/*
 * description
* Fonction à deux balles pour ajouter une tempotisation entre les appels
* de traitement des anomalies quand le nombre atteint 10000 !!!
*/
const notifyUser=function(info) {
  log(info);
};

/**
 * description
 * On récupère le nombre total des défauts (BUG, VULNERABILITY, CODE_SMELL),
 * la répartition par dossier la répartition par severity et la dette technique total.
 * Arguements : mavenKey = clé du projet,
 *
 * @param {*} mavenKey
 * @returns
 */
const projetAnomalie=function(mavenKey) {
  const data = { mavenKey };
  const options = {
    url: 'http://localhost:8000/api/projet/anomalie', type: 'GET',
    dataType: 'json', data, contentType };

  return $.ajax(options).then(t => {
      /* On temporise pour éviter que les appels asynchronnes se lance tous en même temps.
       * Temporisation : 8 secondes.
      */
      setTimeout(() => {
        notifyUser(` - INFO : (8) ${t.info}`);
        }, 8000);
    });
};

/**
 * description
 * On récupère pour chaque type (Bug, Vulnerability et Code Smell) le nombre de violation par type.
 * Arguements : mavenKey = clé du projet,
 *
 * @param {*} mavenKey
 * @returns
 */
const projetAnomalieDetails=function(mavenKey) {
  const data = { mavenKey };
  const options = {
    url: 'http://localhost:8000/api/projet/anomalie/details', type: 'GET',
    dataType: 'json', data, contentType };

  return $.ajax(options).then(
    t => {
      if (t.code==='OK'){
        setTimeout(() => {
           notifyUser(' - INFO : (9) Le frequence des sévérités par type a été collectée.');
          }, 4000);
      } else {
          log(` - ERROR : (9) Je n'ai pas réussi à collecter les données (${t.code}).`);
      }
    });
};

/**
 * description
 * Récupère la note pour la fiabilité, la sécurité et les mauvaises pratiques.
 * http://{url}'/api/projet/historique/note
 * {mavenKey} = clé du projet
 * {type} = reliability, security, sqale
 *
 * @param {*} mavenKey
 * @param {*} type
 * @returns
 */
const projetRating=function(mavenKey, type) {
  const data = { mavenKey, type };
  const options = {
    url: 'http://localhost:8000/api/projet/historique/note', type: 'GET',
    dataType: 'json', data, contentType };

  return $.ajax(options).then(t => {
      log(` - INFO : (3) Reprise des notes pour le type : ${t.type}`);
      log(`              : ${t.nombre} résultats.`);
    });
};

/**
* description
* Récupère le top 10 OWASP et construit la vue
* http://{url}/api/issues/search?componentKeys={key}&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10
* Attention une faille peut être comptée deux fois ou plus, cela dépend du tag. Donc il est
* possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10
*
* @param {*} mavenKey
* @returns
*/
const projetOwasp=function(mavenKey) {
  const data = { mavenKey };
  const options = {
    url: 'http://localhost:8000/api/projet/issues/owasp', type: 'GET',
    dataType: 'json', data, contentType };

  return $.ajax(options).then(t=> {
    if (t.owasp===0) {
      log(' - INFO : (4) Bravo aucune faille OWASP détectée.');
    } else {
      log(` - WARN : (4) J'ai trouvé ${t.owasp} faille(s).`);
    }
  });
};


/**
 * description
 * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
 * http://{url}/api/hotspots/search?projectKey={key}&ps=500&p=1
 * On récupère les Hotspot a examiner. Les clés sont uniques (i.e. on ne se base pas sur les tags).
 *
 * @param {*} mavenKey
 * @returns
 */
const projetHotspot=function(mavenKey) {
  const data = { mavenKey };
  const options = {
    url: 'http://localhost:8000/api/projet/hotspot', type: 'GET',
    dataType: 'json', data, contentType };

  return $.ajax(options).then(t=> {
    if (t.hotspots === 0) {
      log(' - INFO : (5) Bravo aucune faille potentielle détectée.');
    } else {
      log(` - WARN : (5) J'ai trouvé ${t.hotspots} faille(s) potentielle(s).`);
    }
  });
};

/**
 * description
 * Traitement des hotspot de type owasp pour sonarqube 8.9 et >
 * http://{url}/api/hotspots/search?projectKey={key}{owasp}&ps=500&p=1
 * Pour chaque faille OWASP on récupère les information. Il est possible d'avoir des doublons (i.e. a cause des tags).
 *
 * @param {*} mavenKey
 * @param {*} owasp
 * @returns
 */
const projetHotspotOwasp=function(mavenKey, owasp) {
  const data = { mavenKey, owasp };
  const options = {
    url: 'http://localhost:8000/api/projet/hotspot/owasp', type: 'GET',
    dataType: 'json', data, contentType };

  return $.ajax(options).then(t=> {
    if (t.info==='effacement') {
      log(' - INFO : (10) Les enregistrements ont été supprimé de la table hostspot_owasp.');
    }
    if (t.hotspots === 0 && t.info==='enregistrement') {
      log(` - INFO : (11) Bravo aucune faille OWASP ${owasp} potentielle détectée.`);
    }
    if (t.hotspots !== 0 && t.info==='enregistrement') {
      log(` - WARN : (10) J'ai trouvé ${t.hotspots} faille(s) OWASP ${owasp} potentielle(s).`);
    }
  });
};

/**
 * description
 * On enregistre le détails des hostspot owasp
 * http://{url}/api/projet/hotspot/details{mavenKey}
 *
 * @param {*} mavenKey
 * @returns
 */
const projetHotspotOwaspDetails=function(mavenKey) {
  const data = { mavenKey };
  const options = {
    url: 'http://localhost:8000/api/projet/hotspot/details', type: 'GET',
    dataType: 'json', data, contentType };

  return $.ajax(options).then(t=> {
    if (t.code === 406) {
      log(' - INFO : (12) Aucun détails n\'est disponible pour les hotspots.');
      return;
    }
    // On a trouvé des hotspots OWASP
    log(` - INFO : (12) On a trouvé ${t.ligne} descriptions.`);
  });
};

/**
 * description
 * On récupére la liste des exclusions de code
 * http://{url}/api/projet/nosonar/details
 *
 * @param {*} mavenKey
 */
const projetNosonarDetails=function(mavenKey){
  const data = { mavenKey };
  const options = {
    url: 'http://localhost:8000/api/projet/nosonar/details', type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(options).then(t=> {
    if (t.hotspots !== 0) {
      log(` - WARM : (13) J'ai trouvé ${t.nosonar} exclusion(s) NoSonar.`);
    } else {
      log(` - INFO : (13) Bravo !!! ${t.nosonar} exclusion NoSonar trouvée.`);
    }
    });
};

/**
 * description
 * On récupére la liste des projets et des favori
 * http://{url}/api/projet/favori
*/
const afficheProjetFavori=function() {
  const options = {
    url: 'http://localhost:8000/api/projet/favori', type: 'GET',
    dataType: 'json', contentType };
  $.ajax(options).then(t=> {
    let str, favori, i, liste=[], checkFavori;
    if (t.code !== 200) {
      log(' - ERROR : La liste des projets n\'a pas été trouvée.');
      return;
    }

    /* on efface les données.*/
    $('#tableau-liste-projet').html('');

    i=0;
    const favoriSvg=`<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 512 512" class="favori-liste-svg">
    <g transform="translate(0 512) scale(.1 -.1)">
    <path d="m1215 4984c-531-89-963-456-1139-966-52-151-69-268-70-463 0-144 4-203 22-297 99-527 412-1085 962-1713 365-418 941-950 1436-1326l134-102 134 102c495 376 1071 908 1436 1326 550 628 863 1186 962 1713 31 167 31 432-1 582-109 513-470 924-951 1084-162 54-239 67-420 73-139 4-183 2-280-16-310-55-567-188-782-403l-98-98-98 98c-213 213-472 347-777 402-128 24-346 25-470 4zm438-341c274-52 521-211 691-444 23-30 79-123 125-207 47-84 88-152 91-152s44 68 91 152c128 231 214 337 362 449 218 164 482 241 755 218 276-23 512-137 708-340 127-133 222-302 271-486 24-88 27-116 27-278 0-163-3-191-28-300-105-447-394-942-865-1480-326-373-749-775-1196-1135l-125-101-125 101c-325 262-717 620-952 868-633 668-987 1227-1109 1747-25 109-28 137-28 300 0 162 3 190 27 278 92 343 335 620 657 750 201 81 411 101 623 60z" />
    </g></svg>`;

    // Si on a pas trouvé de favori on marque l'absence par un tiret sinon on créé une liste
    if (t.favori[0] !== 'vide') {
      liste=t.favori;
    }

    /* Pour chaque élément de la liste des projets analysés, on affiche le projet
     * et si le projet est en favori on ajoute un petit-coeur
     */
    t.liste.forEach(element => {
      i++;
      checkFavori=liste.find(maven => maven.key === element.key);
      if (checkFavori !== undefined) {
        favori = favoriSvg;
      } else {
        favori=' - ';
      }

      str = `<tr id="name-${i}" class="open-sans">
              <td id="key-${i}" data-mavenkey="${element.key}">${element.name}</td>
              <td class="text-center">${favori}</td>
              <td class="text-center capsule">
                <span id="V-${i}" class="capsule-bulle V js-liste-valider">
                  <span id="tooltips-${i}" data-tooltip tabindex="1" title="Je choisi ce projet.">V</span>
                <span>
              </td>
              <td class="text-center capsule">
                <span id="P-${i}" class="capsule-bulle P js-liste-supprimer">
                  <span id="tooltips-${i}" data-tooltip tabindex="2" title="Je supprime ce projet de la liste.">P</span>
                </span>
              </td>
              <td class="text-center capsule">
                <span id="C-${i}" class="capsule-bulle C js-liste-collecter">
                  <span id="tooltips-${i}" data-tooltip tabindex="3" title="Je lance la collecte des données.">C</span>
                </span>
              </td>
              <td class="text-center capsule">
                <span id="R-${i}" class="capsule-bulle R js-liste-afficher-resultat">
                  <span id="tooltips-${i}" data-tooltip tabindex="4" title="J'affiche les résultats.">R</span>
                </span>
              </td>
              <td class="text-center capsule">
                <span id="I-${i}" class="capsule-bulle I js-liste-afficher-indicateur">
                  <span id="tooltips-${i}" data-tooltip tabindex="5" title="J'affiche le tableau de suivi.">I</span>
                </span>
              </td>
              <td class="text-center capsule">
                <span id="O-${i}" class="capsule-bulle O js-liste-owasp">
                  <span id="tooltips-${i}" data-tooltip tabindex="6" title="J'affiche le rapport OWASP.">O</span>
                </span>
              </td>
              <td class="text-center capsule">
                <span id="RM-${i}" class="capsule-bulle RM js-liste-repartition-module">
                  <span id="tooltips-${i}" data-tooltip tabindex="7" title="J'affiche le tableau de répartition par module.">RM</span>
                </span>
              </td>
              </tr>`;
      $('#tableau-liste-projet').append(str);
     });
     $(document).foundation();
    // On met à jour le nombre des projets collectés
    $('#affiche-total-projet').html(`<span class="stat">${i}</span>`);

    /* On gére le click sur le bouton V (Valider) */
    $('.js-liste-valider').on('click', (e) => {
      // On récupère la valeur de l'ID
      const id = e.target.id;
      const a = id.split('-');
      const key='key-'+a[1];

      // On récupère la clé maven du projet
      const element = document.getElementById(key);
      const mavenKey=element.dataset.mavenkey;

      // On récupère le nom du projet
      const b = mavenKey.split(':');
      const nom = b[1];
      const $newOption = $("<option selected='selected'></option>").val(mavenKey).text(nom)
       $('select[name="projet"]').append($newOption).trigger('change');
    });

    /* On gére le click sur le bouton R (afficher les Résulats) */
    $('.js-liste-afficher-resultat').on('click', (e) => {

      // On récupère la valeur de l'ID
      const id = e.target.id;
      const a = id.split('-');
      const key='key-'+a[1];

      // On récupère la clé maven du projet
      const element = document.getElementById(key);
      const mavenKey=element.dataset.mavenkey;
      $('#select-result').html(`<strong>${mavenKey}</strong>`);
      // on active le bouton pour afficher les infos du projet
      $('.js-affiche-resultat').removeClass('affiche-resultat-disabled');
      $('.js-affiche-resultat').addClass('affiche-resultat-enabled');
      // On clique sur le bouton afficher les résultats
      $('.js-affiche-resultat').trigger('click');
    });

    /* On gére le click sur le bouton I (afficher le tableau de suivi) */
    $('.js-liste-afficher-indicateur').on('click', (e) => {

      // On récupère la valeur de l'ID
      const id = e.target.id;
      const a = id.split('-');
      const key='key-'+a[1];

      // On récupère la clé maven du projet
      const element = document.getElementById(key);
      const mavenKey=element.dataset.mavenkey;
      console.log(mavenKey);
      $('#select-result').html(`<strong>${mavenKey}</strong>`);
      // On clique sur le bouton tableau de suivi
      $('.js-tableau-suivi').trigger('click');
    });

    /* On gére le click sur le bouton O (afficher le rapport OWASP) */
    $('.js-liste-owasp').on('click', (e) => {

      // On récupère la valeur de l'ID
      const id = e.target.id;
      const a = id.split('-');
      const key='key-'+a[1];

      // On récupère la clé maven du projet
      const element = document.getElementById(key);
      const mavenKey=element.dataset.mavenkey;
      console.log(mavenKey);
      $('#select-result').html(`<strong>${mavenKey}</strong>`);
      // On clique sur le bouton OWASP
      $('.js-analyse-owasp').trigger('click');
    });

    /* On gére le click sur le bouton O (afficher le rapport OWASP) */
    $('.js-liste-repartition-module').on('click', (e) => {

      // On récupère la valeur de l'ID
      const id = e.target.id;
      const a = id.split('-');
      const key='key-'+a[1];

      // On récupère la clé maven du projet
      const element = document.getElementById(key);
      const mavenKey=element.dataset.mavenkey;
      console.log(mavenKey);
      $('#select-result').html(`<strong>${mavenKey}</strong>`);
      // On clique sur le bouton OWASP
      $('.js-repartition-module').trigger('click');
    });

  });

};

/**
 * description
 * On récupére la répartition des hotspots par sévérité
 * http://{url}/api/peinture/projet/hotspot/details{meven_key}
 *
 * @param {*} mavenKey
 */
const afficheHotspotDetails=function (mavenKey){
  const data = { mavenKey };
  const options = {
    url: 'http://localhost:8000/api/peinture/projet/hotspot/details',
    type: 'GET', dataType: 'json', data, contentType };
  $.ajax(options).then(t=> {
    if (t.code !== 200) {
      log(' - ERROR : La liste des hotspot n\'a pas été trouvée.');
      return;
    }

    /* on efface les données.*/
    $('#tableau-liste-hotspot').html('');
    const str =`<tr id="hotspot-1" class="open-sans">
              <td id="hotspot-high" class="text-center stat" data-hotspot-high="${t.high}">
              ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.high)}</td>
              <td id="hotspot-medium" class="text-center stat" data-hotspot-medium="${t.medium}">
              ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.medium)}</td>
              <td id="hotspot-low" class="text-center stat" data-hotspot-low="${t.low}">
              ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.low)}</td>
              </tr>`;
     $('#tableau-liste-hotspot').append(str);
     $('#hotspot-total').html(`<span class="stat">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.total)}</span>`);

     const t1 = document.getElementById('hotspot-total');
     t1.dataset.hotspotTotal=(t.total);
  });
};

/*************** Main du programme **************/
// On dit bonjour
ditBonjour();
// On met ajour la liste des projets disponibles
selectProjet();

/**
 * description
 * Lance la collecte des données du projet sélectionné.
 */
$('.js-analyse').on('click', function () {
  setTimeout(()=> {
    startSpinner();
  }, 1000);

    log(' - INFO : On lance la collecte...');
  // on bloque le bouton afficher les resultats
  $('.js-affiche-resultat').removeClass('affiche-resultat-enabled');
  $('.js-affiche-resultat').addClass('affiche-resultat-disabled');

  // On récupère la clé du projet qui est affichée.
  const idProject = $('#select-result').text().trim();
  if (idProject === 'N.C') {
    log(' - ERROR : Vous devez chisir un projet !!!');
    return;
  }

  // Analyse du projet
  projetAnalyse(idProject);
  projetMesure(idProject);

 // Analyse Sécurité et Owasp
  projetRating(idProject, 'reliability');
  projetRating(idProject, 'security');
  projetRating(idProject, 'sqale');

  projetOwasp(idProject);
  projetHotspot(idProject);

  // On récupère les infos sur les anomalies
  projetAnomalie(idProject);

  // On récupère le détails surr les anomalies
  projetAnomalieDetails(idProject);

  // On efface les traces :)
  projetHotspotOwasp(idProject, 'a0');
  // On enregistre les résultats
  projetHotspotOwasp(idProject, 'a1');
  projetHotspotOwasp(idProject, 'a2');
  projetHotspotOwasp(idProject, 'a3');
  projetHotspotOwasp(idProject, 'a4');
  projetHotspotOwasp(idProject, 'a5');
  projetHotspotOwasp(idProject, 'a6');
  projetHotspotOwasp(idProject, 'a7');
  projetHotspotOwasp(idProject, 'a8');
  projetHotspotOwasp(idProject, 'a9');
  projetHotspotOwasp(idProject, 'a10');

  // On enregistre le détails de chaque hotspot owasp
  projetHotspotOwaspDetails(idProject);

  // Analyse des anomalies
  projetNosonarDetails(idProject);

  setTimeout(()=> {
    stopSpinner();
  }, 8000);

  // on active le bouton pour afficher les infos du projet
  $('.js-affiche-resultat').removeClass('affiche-resultat-disabled');
  $('.js-affiche-resultat').addClass('affiche-resultat-enabled');
});

/************* Events ***************************/
/**
 * description
 * Événement : Affiche le nom de la clé du projet, active le bouton pour l'analyse.
 */
$('select[name="projet"]').change(function () {
  $('#select-result').html(`<strong>${$('select[name="projet"]').val().trim()}</strong>`);

  // On regarde si le projet est en favori, on récupère son statut
  const data = { mavenKey: $('#select-result').text().trim() };
  const options = {
    url: 'http://localhost:8000/api/favori/check', type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(options).then(t=> {
    if ( t.favori==='TRUE' && t.statut==='FALSE' ) {
          $('.favori-svg').removeClass('favori-svg-select');
        }
    if (t.favori === 'TRUE' && t.statut === 'TRUE') {
          $('.favori-svg').addClass('favori-svg-select');
    } else {
      $('.favori-svg').removeClass('favori-svg-select');
    }
   });

  // On débloque les boutons

  // Bouton : Lance la collecte
  $('.js-analyse').removeClass('lance-analyse-disabled');
  $('.js-analyse').addClass('lance-analyse-enabled');

  // Bouton : Affiche les résultats
  $('.js-affiche-resultat').removeClass('affiche-resultat-disabled');
  $('.js-affiche-resultat').addClass('affiche-resultat-enabled');

  // Bouton : Ouvre la page d'analyse OWASP
  $('.js-analyse-owasp').removeClass('analyse-owasp-disabled');
  $('.js-analyse-owasp').addClass('analyse-owasp-enabled');

  // Bouton : Ouvre la page de suivi des indicateurs
  $('.js-tableau-suivi').removeClass('tableau-suivi-disabled');
  $('.js-tableau-suivi' ).addClass('tableau-suivi-enabled');

  // Bouton : Ouvre la page de répartition des indicateurs par Module
  $('.js-repartition-module').removeClass('repartition-module-disabled');
  $('.js-repartition-module' ).addClass('repartition-module-enabled');

  // on ajoute la clé slectionnée dans le local storage pour les pages security et graphques
  localStorage.setItem('projet', $('select[name="projet"]').val().trim());
});

/**
 * description
 * On affiche la liste des projets déjà analysés et des favoris
 */
$('.js-affiche-liste').on('click', function () {
  afficheProjetFavori();
  $('#modal-liste-projet').foundation('open');
});

/**
 * description
 * On affiche la liste des types d'anomalies par sévérité.
 */
$('.js-affiche-severite').on('click', function () {
  if ($('select[name="projet"]').val() !=='') {
    $('#modal-affiche-severite').foundation('open');
 }
 });

/**
 * description
 * On affiche la liste des hotspots
 */
$('#js-affiche-hotspot').on('click', function () {
  if ($('select[name="projet"]').val() !=='') {
     $('#modal-liste-hotspot').foundation('open');
  }
});

/**
 * description
 * Événement : Ouvre la fenêtre modale de la distribution de la dette technique.
 */
$('.js-affiche-details').on('click', () => {
  if ($('select[name="projet"]').val() !=='') {
    $('#modal-dette-technique').foundation('open');
  }
});

/**
 * description
 * Événement : on marque le projet comme favori.
 */
$('.favori-svg').on('click', () => {
  let statut;

  // On regarde si le projet est déjà en favori
  if ($('select[name="projet"]').val() !=='') {
    if ($('.favori-svg').hasClass('favori-svg-select')){
          $('.favori-svg').removeClass('favori-svg-select');
          statut='FALSE';
      } else {
       $('.favori-svg').addClass('favori-svg-select');
       statut='TRUE';
      }

    const data = { mavenKey: $('#select-result').text().trim(), statut };
    const options = {
      url: 'http://localhost:8000/api/favori', type: 'GET',
      dataType: 'json',  data, contentType };
      $.ajax(options).then( t => {
        if (statut === t.statut){
          log(' - INFO : Ajout du projet à la liste des favoris.');
        }
        if (statut === t.statut) {
          log(' - INFO : Suppression du projet à la liste des favoris.');
        }
      });
  }
});

/**
 * description
 * On affiche la répartition des versions
 */
 $('#js-version-autre').on('click', () => {
  let version ;
  if ($('select[name="projet"]').val() !=='') {
    version = document.getElementById('version-autre');
    if (version.dataset.label === undefined) {
      return;
    }
    /**
     * const label = version.dataset.label;
     * const dataset = version.dataset.dataset;
    */
    const {label, dataset} = version.dataset
    dessineMoiUnMouton(JSON.parse(label), JSON.parse(dataset));
    $('#modal-autre-version').foundation('open');
  }
});

/**
 * description
 * On passe à la peinture
 */
$('.js-affiche-resultat').on('click', () => {
  // On récupère la clé du projet
  const apiMaven = $('#select-result').text().trim();
  // On appel une fonction externe
  if ( $('.js-affiche-resultat').hasClass('affiche-resultat-enabled')){
      // On récupère la répartition des hotspots
      afficheHotspotDetails(apiMaven);
      // On récupère les résultats
      remplissage(apiMaven);
      if ($('#enregistrement').hasClass('enregistrement-disabled')){
          $('#enregistrement').addClass('enregistrement');
          $('#enregistrement').removeClass('enregistrement-disabled');
        }
    }
});

/**
 * description
 * On lance l'enregistrement des données
 */
$('.js-enregistrement').on('click', () => {
  // On récupère la clé du projet
  const apiMaven = $('#select-result').text().trim();
  enregistrement(apiMaven);
 });

/**
 * description
 * On génére la route et on ouvre la page des tableau de suivi
 */
$('.js-tableau-suivi').on('click', () => {
  if ($('select[name="projet"]').val() !==''){
    const apiMaven = $('#select-result').text().trim();
    window.location.href='/suivi?mavenKey='+apiMaven;
   } else {
     log(' - ERROR - Vous devez chosir un projet dans la liste !! !');
    }
 });

 /**
 * description
 * On génére la route et on ouvre la page de répartition des indicateurs par module
 */
  $('.js-analyse-owasp').on('click', () => {
    if ($('select[name="projet"]').val() !==''){
      const apiMaven = $('#select-result').text().trim();
      window.location.href='/owasp?mavenKey='+apiMaven;
     } else {
       log(' - ERROR - [OWASP] Vous devez chosir un projet dans la liste !! !');
      }
   });

 /**
 * description
 * On génére la route et on ouvre la page de répartition des indicateurs par module
 */
$('.js-repartition-module').on('click', () => {
  if ($('select[name="projet"]').val() !==''){
    const apiMaven = $('#select-result').text().trim();
    window.location.href='/projet/repartition?mavenKey='+apiMaven;
   } else {
     log(' - ERROR - [Répartition] Vous devez chosir un projet dans la liste !! !');
    }
 });
