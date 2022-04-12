/*
 * Copyright (c) 2021-2022.
 * Laurent HADJADJ <laurent_h@me.com>.
 * Licensed Creative Common  CC-BY-NC-SA 4.0.
 * Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 * http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
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

import {remplissage} from "./app-projet-peinture.js";
import {enregistrement} from "./app-projet-enregistrement.js";

const date_options = {
  year: "numeric", month: "numeric", day: "numeric",
  hour: "numeric", minute: "numeric", second: "numeric",
  hour12: false
};

const contentType='application/json; charset=utf-8';
const matrice = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31];
const palette_couleur = [
  '#065535', '#133337', '#000000', '#ffc0cb', '#008080', '#ff0000', '#ffd700', '#666666',
  '#ff7373', '#fa8072', '#800080', '#800000', '#003366', '#333333', '#20b2aa', '#ffc3a0',
  '#f08080', '#66cdaa', '#f6546a', '#ff6666', '#468499', '#c39797', '#bada55', '#ff7f50',
  '#660066', '#008000', '#088da5', '#808080', '#8b0000', '#0e2f44', '#3b5998', '#cc0000'
];

/**
 * description
 * Mélangeur de couleur
 */
function shuffle(a) {
  var j, x, i;
  for (i = a.length - 1; i > 0; i--) {
    j = Math.floor(Math.random() * (i + 1));
    x = a[i];
    a[i] = a[j];
    a[j] = x;
  }
  return a;
}

/**
 * description
 * Renvoie une nouvelle palette de couleur
 */
function palette() {
  const nouvelle_palette = [];
  shuffle(matrice);
  matrice.forEach((el) => { nouvelle_palette.push(palette_couleur[el]); });
  return nouvelle_palette;
}

/**
 * Affiche le graphique des sources
 */
function dessineMoiUnMouton(label, dataset) {
  const nouvelle_palette = palette();
  const data =
  {
    labels: label,
    datasets: [{ data: dataset, backgroundColor: nouvelle_palette, borderWidth: 1,
                 datalabels: { align: 'center', anchor: 'center' } }],
  };

  const options = {
    animations: { tension: { duration: 2000, easing: 'linear', loop: false } },
    maintainAspectRatio: true,
    responsive: true,
    plugins: {
      title: { display: false, },
      tooltip: { enabled: true },
      legend: {},
      datalabels: {
        color: '#fff',
        font: function (context) {
          var w = context.chart.width; return {
            size: w < 512 ? 12 : 14, weight: 'bold',
          };
        },
      }
    }
  };

  const chartStatus = Chart.getChart("graphique-autre-version");
  if (chartStatus != undefined) { chartStatus.destroy(); }

  const ctx = document.getElementById('graphique-autre-version').getContext('2d');
  const charts = new Chart(ctx, { type: 'doughnut', data: data, options: options });
  if (charts===null){console.info('Pour éviter une erreur sonar !!!');}
}

/**
 * description
 * Affiche la log.
 */
function log(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += new Intl.DateTimeFormat('default', date_options).format(new Date()) + txt + '\n';
}

/**
 * description
 * Initialisation de la log.
 */
function dit_bonjour() { log(' - Initialisation de la log...'); }

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
function start_spinner() {
  if ($('#loader').hasClass('loader-disabled')) {
    $('#loader').removeClass('loader-disabled');
    $('#loader').addClass('loader-enabled');
  }
}

/**
 * description
 * Désactive le spinner.
 */
function stop_spinner() {
  if ($('#loader').hasClass('loader-enabled')) {
    $('#loader').removeClass('loader-enabled');
    $('#loader').addClass('loader-disabled');
  }
}

/**
 * description
 * Active la gomme pour nettoyer la log.
 */
$('.gomme-svg').on('click', function () { $('.log').val(''); });

/**
 * description
 * Propriétés du selecteur de recherche.
 */
function match(params, data) {
  if ($.trim(params.term) === '') {
    return data;
  }
  if (typeof data.text === 'undefined') {
    return null;
  }

  if (data.text.indexOf(params.term) > -1) {
    var modifiedData = $.extend({}, data, true);
    modifiedData.text += ' (trouvé)';
    return modifiedData;
  }
  return null;
}

/**
 * description
 * Création du selecteur de projet.
 */
function select_projet() {
  const options = {
    url: 'http://localhost:8000/api/liste/projet', type: 'GET', dataType: 'json',
    contentType: contentType }

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
        language: "fr",
        data: data.liste
      });
      $('.analyse').removeClass('hide');
    })
}

/**
 * description
 * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version)
 * http://{url}/api/project_analyses/search?project={key}
 */
function projet_analyse(maven_key) {
  const data = { maven_key: maven_key };
  const options = {
    url: 'http://localhost:8000/api/projet/analyses', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  return $.ajax(options).then(
    (t) => {
      log(' - INFO : (1) Nombre de version disponible : ' + t.nombreVersion);
    })
}

/**
 * description
 * Met à jour les indicateurs du projet (lignes, couvertures, duplication, défauts).
 * http://{url}/api/components/app?component={key}
 */
function projet_mesure(maven_key) {
  const data = { maven_key: maven_key };
  const options = {
    url: 'http://localhost:8000/api/projet/mesures', type: 'GET', dataType: 'json', data: data, contentType: contentType  }
  return $.ajax(options).then(
    () => { log(' - INFO : (2) Ajout des mesures.'); })
}

/*
 * description
* Fonction à deux balles pour ajouter une tempotisation entre les appels
* de traitement des anomalies quand le nombre atteint 10000 !!!
*/
function notifyUser(info) {
  log(' - INFO : (8) '+ info);
}

/**
 * description
 * On récupère le nombre total des défauts (BUGn VULNERABILITY, CODE_SMELL), la répartition par dossier la répartition par severity et la dette technique total.
 * Arguements :
 *  maven_key = clé du projet,
 */
function projet_anomalie(maven_key) {
  const data = { maven_key: maven_key };
  const options = {
    url: 'http://localhost:8000/api/projet/anomalie', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  return $.ajax(options).then(
    (t) => {
      /* On temporise pour éviter que les appels asynchronnes se lance tous en même temps.
       * Temporisation : 8 secondes.
      */
      setTimeout(() => { notifyUser(t.info); }, 8000);
    });
}

/**
 * description
 * On récupère pour chaque type (Bug, Vulnerability et Code Smell) le nombre de violation par type.
 * Arguements :
 *  maven_key = clé du projet,
 */
 function projet_anomalie_details(maven_key) {
  const data = { maven_key: maven_key };
  const options = {
    url: 'http://localhost:8000/api/projet/anomalie/details', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  return $.ajax(options).then(
    (t) => {
      setTimeout(() => { notifyUser(t.info); }, 8000);
    });
}


/**
* description
* Récupère la note pour la fiabilité, la sécurité et les mauvaises pratiques.
* http://{url}'/api/projet/historique/note
* {maven_key} = clé du projet
* {type} = reliability, security, sqale
*/
function projet_rating(maven_key, type) {
  const data = { maven_key: maven_key, type: type };
  const options = {
    url: 'http://localhost:8000/api/projet/historique/note', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  return $.ajax(options).then(
    (t) => {
      log(' - INFO : (3) Reprise des notes pour le type : ' + t.type);
      log('              : ' + t.nombre + ' résultats.');
    });
}

/**
* description
* Récupère le top 10 OWASP et construit la vue
* http://{url}/api/issues/search?componentKeys={key}&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10
* Attention une faille peut être comptée deux fois ou plus, cela dépend du tag. Donc il est
* possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10

*/
function projet_owasp(maven_key) {
  const data = { maven_key: maven_key };
  const options = {
    url: 'http://localhost:8000/api/projet/issues/owasp', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  return $.ajax(options).then((t) => {
    if (t.owasp==0) {
      log(' - INFO : (4) Bravo aucune faille OWASP détectée.');
    }
    else { log(' - WARN : (4) J\'ai trouvé '+t.owasp+' faille(s).');}
  })
}


/**
* description
* Traitement des hotspots de type owasp pour sonarqube 8.9 et >
* http://{url}/api/hotspots/search?projectKey={key}&ps=500&p=1
* On récupère les Hotspot a examiner. Les clés sont uniques (i.e. on ne se base pas sur les tags).
*/
function projet_hotspot(maven_key) {
  const data = { maven_key: maven_key };
  const options = {
    url: 'http://localhost:8000/api/projet/hotspot', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  return $.ajax(options).then((t) => {
    if (t.hotspots === 0) {
      log(' - INFO : (5) Bravo aucune faille potentielle détectée.');
    }
    else { log(' - WARN : (5) J\'ai trouvé ' + t.hotspots + ' faille(s) potentielle(s).'); }
  })
}

/**
* description
* Traitement des hotspot de type owasp pour sonarqube 8.9 et >
* http://{url}/api/hotspots/search?projectKey={key}{owasp}&ps=500&p=1
* Pour chaque faille OWASP on récupère les information. Il est possible d'avoir des doublons (i.e. a cause des tags).
*/
function projet_hotspot_owasp(maven_key, owasp) {
  const data = { maven_key: maven_key, owasp: owasp };
  const options = {
    url: 'http://localhost:8000/api/projet/hotspot/owasp', type: 'GET', dataType: 'json', data: data, contentType: contentType  }

  return $.ajax(options).then((t) => {
    if (t.info==='effacement') {
      log(' - INFO : (9) Les enregistrements ont été supprimé de la table hostspot_owasp.');
    }
    if (t.hotspots === 0 && t.info==='enregistrement') {
      log(' - INFO : (10) Bravo aucune faille OWASP '+ owasp +' potentielle détectée.');
    }
    if (t.hotspots != 0 && t.info==='enregistrement') {
      log(' - WARN : (10) J\'ai trouvé ' + t.hotspots + ' faille(s) OWASP '+owasp+' potentielle(s).');
    }
  })
}

/**
* description
* On enregistre le détails des hostspot owasp
* http://{url}/api/projet/hotspot/details{maven_key}
*
*/
function projet_hotspot_owasp_details(maven_key) {
  const data = { maven_key: maven_key };
  const options = {
    url: 'http://localhost:8000/api/projet/hotspot/details', type: 'GET', dataType: 'json', data: data, contentType: contentType  }

  return $.ajax(options).then((t) => {
    if (t.code === 406) {
      log(' - INFO : (11) Aucun détails n\'est disponible pour les hotspots.');
      return;
    }
    // On a trouvé des hotspots OWASP
    log(' - INFO : (11) On a trouvé '+ t.ligne +' descriptions.');
  })
}

/**
* description
* On récupére la liste des exculions de code
* http://{url}/api/projet/nosonar/details
*/
function projet_nosonar_details(maven_key){
  const data = { maven_key: maven_key };
  const options = {
    url: 'http://localhost:8000/api/projet/nosonar/details', type: 'GET', dataType: 'json', data: data, contentType: contentType
  }

  $.ajax(options).then((t) => {
    if (t.hotspots != 0) {
      log(' - WARM : (12) J\'ai trouvé '+t.nosonar +' exclusion(s) NoSonar.');
    }
    else { log(' - INFO : (12) Bravo !!! ' + t.nosonar + ' exlusion NoSonar trouvé.'); }
    });
}

/**
* description
* On récupére la liste des projets et des favori
* http://{url}/api/projet/favori
*/
function affiche_projet_favori() {
  const options = {
    url: 'http://localhost:8000/api/projet/favori', type: 'GET', dataType: 'json', contentType: contentType
  }
  $.ajax(options).then((t) => {
    let str, favori, i, liste=[], check_favori;
    if (t.code != '200') { log(' - ERROR : La liste des projets n\'a pas été trouvée.'); return; }

    /* on efface les données.*/
    $('#tableau-liste-projet').html("");

    i=0;
    const favori_svg='<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 512 512" class="favori-liste-svg"><g transform="translate(0 512) scale(.1 -.1)" ><path d="m1215 4984c-531-89-963-456-1139-966-52-151-69-268-70-463 0-144 4-203 22-297 99-527 412-1085 962-1713 365-418 941-950 1436-1326l134-102 134 102c495 376 1071 908 1436 1326 550 628 863 1186 962 1713 31 167 31 432-1 582-109 513-470 924-951 1084-162 54-239 67-420 73-139 4-183 2-280-16-310-55-567-188-782-403l-98-98-98 98c-213 213-472 347-777 402-128 24-346 25-470 4zm438-341c274-52 521-211 691-444 23-30 79-123 125-207 47-84 88-152 91-152s44 68 91 152c128 231 214 337 362 449 218 164 482 241 755 218 276-23 512-137 708-340 127-133 222-302 271-486 24-88 27-116 27-278 0-163-3-191-28-300-105-447-394-942-865-1480-326-373-749-775-1196-1135l-125-101-125 101c-325 262-717 620-952 868-633 668-987 1227-1109 1747-25 109-28 137-28 300 0 162 3 190 27 278 92 343 335 620 657 750 201 81 411 101 623 60z" /></g></svg>';

    // Si on a pas trouvé de favori on marque l'absence par un tiret sinon on créé une liste
    if (t.favori[0] != 'vide') { liste=t.favori; }

    /* Pour chaque élément de la liste des projets analysés, on affiche le projet
     * et si le projet est en favori on ajoute un petit-coeur
     */
    t.liste.forEach(element => {
      i++;
      check_favori=liste.find(maven => maven.key === element.key)
      if (check_favori != undefined) {favori = favori_svg;} else { favori=' - ';}

      str = '<tr id="name-' + i + '" class="open-sans"><td>' + element.name + '</td><td class="text-center">' + favori + '</td></tr>';
      $('#tableau-liste-projet').append(str);
     });

    // On met à jour le nombre des projets collectés
    $('#affiche-total-projet').html('<span class="stat">'+i+'</span>');
  })
}


/**
* description
* On récupére la répartition des hotspots par sévérité
* http://{url}/api/peinture/projet/hotspot/details{meven_key}
*/
function affiche_hotspot_details(maven_key){
  const data = { maven_key: maven_key };
  const options = {
    url: 'http://localhost:8000/api/peinture/projet/hotspot/details', type: 'GET', dataType: 'json', data: data, contentType: contentType
  }
  $.ajax(options).then((t) => {
    if (t.code != '200') { log(' - ERROR : La liste des hotspot n\'a pas été trouvée.'); return; }

    /* on efface les données.*/
    $('#tableau-liste-hotspot').html("");
    let str = '<tr id="hotspot-1" class="open-sans">';
    str += '<td id="hotspot-high" class="text-center stat" data-hotspot_high="'+t.high+'">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.high);
    str += '</td><td id="hotspot-medium" class="text-center stat" data-hotspot_medium="'+t.medium+'">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.medium) + '</td>';
    str += '<td id="hotspot-low" class="text-center stat" data-hotspot_low="'+t.low+'">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.low) + '</td>';
    str +='</tr>';
     $('#tableau-liste-hotspot').append(str);
     $('#hotspot-total').html('<span class="stat">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.total) + '</span>');

     let t1 = document.getElementById('hotspot-total');
     t1.dataset.hotspot_total=(t.total);

  });
  }


/*************** Main du programme **************/
// On dit bonjour
dit_bonjour();
// On met ajour la liste des projets disponibles
select_projet();

/**
 * description
 * Lance la collecte des données du projet sélectionné.
 */
$('.js-analyse').on('click', function () {
  setTimeout(function () { start_spinner(); }, 2000);
  log(' - INFO : On lance la collecte...');
  // on bloque le bouton afficher les resultats
  $('.js-affiche-resultat').removeClass('affiche-resultat-enabled');
  $('.js-affiche-resultat').addClass('affiche-resultat-disabled');
  // On récupère la clé du projet qui est affichée.
  let id_project = $('#select-result').text().trim();
  if (id_project === 'N.C') { log(' - ERROR : Vous devez chisir un projet !!!'); return }

  // Analyse du projet
  projet_analyse(id_project);
  projet_mesure(id_project);

 // Analyse Sécurité et Owasp
  projet_rating(id_project, 'reliability');
  projet_rating(id_project, 'security');
  projet_rating(id_project, 'sqale');

  projet_owasp(id_project);
  projet_hotspot(id_project);

  // On récupère les infos sur les anomalies
  projet_anomalie(id_project);

  // On récupère le détails surr les anomalies
  projet_anomalie_details(id_project);

  // On efface les traces :)
  projet_hotspot_owasp(id_project, 'a0');
  // On enregistre les résultats
  projet_hotspot_owasp(id_project, 'a1');
  projet_hotspot_owasp(id_project, 'a2');
  projet_hotspot_owasp(id_project, 'a3');
  projet_hotspot_owasp(id_project, 'a4');
  projet_hotspot_owasp(id_project, 'a5');
  projet_hotspot_owasp(id_project, 'a6');
  projet_hotspot_owasp(id_project, 'a7');
  projet_hotspot_owasp(id_project, 'a8');
  projet_hotspot_owasp(id_project, 'a9');
  projet_hotspot_owasp(id_project, 'a10');

  // On enregistre le détails de chaque hotspot owasp
  projet_hotspot_owasp_details(id_project);

  // Analyse des anomalies
  projet_nosonar_details(id_project);

  setTimeout(function () { stop_spinner(); }, 5000);
  // on active le bouton pour afficher les infos du projet
  $('.js-affiche-resultat').removeClass('affiche-resultat-disabled');
  $('.js-affiche-resultat').addClass('affiche-resultat-enabled');
})

/************* Events ***************************/
/**
 * description
 * Événement : Affiche le nom de la clé du projet, active le bouton pour l'analyse.
 */
$('select[name="projet"]').change(function () {
  $('#select-result').html('<strong>' + $('select[name="projet"]').val().trim() + '</strong>');

  // On regarde si le projet est en favori, on récupère son statut
  const data = { maven_key: $('#select-result').text().trim() }
  const options = {
    url: 'http://localhost:8000/api/favori/check', type: 'GET', dataType: 'json', data: data, contentType: contentType
  }

  $.ajax(options).then((t) => {
    if ( t.favori==='TRUE' && t.statut==='FALSE' ) {
          $('.favori-svg').removeClass('favori-svg-select'); }
    if (t.favori === 'TRUE' && t.statut === 'TRUE') {
          $('.favori-svg').addClass('favori-svg-select');
    } else { $('.favori-svg').removeClass('favori-svg-select'); }
   })

  // On débloque les bouton
  $('.js-analyse').removeClass('lance-analyse-disabled');
  $('.js-analyse').addClass('lance-analyse-enabled');

  $('.js-affiche-resultat').removeClass('affiche-resultat-disabled');
  $('.js-affiche-resultat').addClass('affiche-resultat-enabled');

  $('.js-analyse-owasp').removeClass('analyse-owasp-disabled');
  $('.js-analyse-owasp').addClass('analyse-owasp-enabled');

  $('.js-tableau-suivi').removeClass('tableau-suivi-disabled');
  $('.js-tableau-suivi').addClass('tableau-suivi-enabled');

  // on ajoute la clé slectionnée dans le local storage pour les pages security et graphques
  localStorage.setItem('projet', $('select[name="projet"]').val().trim());
});

/**
 * description
 * On affiche la liste des projets déjà analysés et des favoris
 */
$('.js-affiche-liste').on('click', function () {
  affiche_projet_favori();
  $('#modal-liste-projet').foundation('open');
})

/**
 * description
 * On affiche la liste des hotspots
 */
$('#js-affiche-hotspot').on('click', function () {
  if ($('select[name="projet"]').val() != "") {
     $('#modal-liste-hotspot').foundation('open');
  }
})

/**
 * description
 * Événement : Ouvre la fenêtre modale de la distribution de la dette technique.
 */
$('#js-affiche-details').on('click', () => {
  if ($('select[name="projet"]').val() != "") {
    $('#modal-dette-technique').foundation('open');
  }
});

/**
 * description
 * Événement : on marque le projet comme favori.
 */
$('.favori-svg').on('click', function () {
  let statut;

  // On regarde si le projet est déjà en favori
  if ($('select[name="projet"]').val() != "") {
    if ($('.favori-svg').hasClass('favori-svg-select'))
        { $('.favori-svg').removeClass('favori-svg-select'); statut='FALSE'; }
     else { $('.favori-svg').addClass('favori-svg-select'); statut='TRUE'; }

    const data = { maven_key: $('#select-result').text().trim(), statut: statut };
    const options = {
      url: 'http://localhost:8000/api/favori', type: 'GET', dataType: 'json',
      data: data, contentType: contentType
     }
    return $.ajax(options).then(
      (t) => {
        if (statut === t.statut){ log(' - INFO : Ajout du projet à la liste des favoris.'); }
        if (statut === t.statut) { log(' - INFO : Suppression du projet à la liste des favoris.'); }
      })
  }
});

/**
 * description
 * On affiche la répartition des versions
 */
 $('#js-version-autre').on('click', () => {
  let version ;
  if ($('select[name="projet"]').val() != "") {
    version = document.getElementById('version-autre');
    if (version.dataset.label == undefined) { return; }
    let label = version.dataset.label;
    let dataset = version.dataset.dataset;
    dessineMoiUnMouton(JSON.parse(label), JSON.parse(dataset));
    $('#modal-autre-version').foundation('open');
  }
});

/**
 * description
 * On passe à la peinture
 */
$('.js-affiche-resultat').on('click', function () {
  // On récupère la clé du projet
  let api_maven = $('#select-result').text().trim();
  // On appel une fonction externe
  if ( $('.js-affiche-resultat').hasClass('affiche-resultat-enabled'))
    {
      // On récupère la répartition des hotspots
      affiche_hotspot_details(api_maven);
      // On récupère les résultats
      remplissage(api_maven);
      if ($('#enregistrement').hasClass('enregistrement-disabled'))
        {
          $('#enregistrement').addClass('enregistrement');
          $('#enregistrement').removeClass('enregistrement-disabled');
        }
    }
})

/**
 * description
 * On lance l'enregistrement des données
 */
 $('.js-enregistrement').on('click', function () {
  // On récupère la clé du projet
  let api_maven = $('#select-result').text().trim();
  enregistrement(api_maven);
 })

/**
 * description
 * On génére la route et on ouvre la page des tableau de suivi
 */
 $('.js-tableau-suivi').on('click', function () {
  if ($('select[name="projet"]').val() != "")
   {
    let api_maven = $('#select-result').text().trim();
    window.location.href='/suivi?maven_key='+api_maven;
   }
   else { log(' - ERROR - Vous devez chosir un projet dans la liste !! !')}
 });
