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


import '../css/dash.css';

/* Intégration de jquery */
// eslint-disable-next-line no-unused-vars
import $ from 'jquery';

import 'select2';
import 'select2/dist/js/i18n/fr.js';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import html2pdf from 'html2pdf.js';

import './foundation.js';

import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';

import moment from 'moment';
import 'moment/locale/fr';
import 'chartjs-adapter-moment';
Chart.register(ChartDataLabels);

/* Initialisation de moments */
const a= moment().toString();
/* Pour éviter d'avoir une erreur sonar */
console.info(a);

/* chartJS */
const chartColors = {
  rouge: 'rgb(255,99,132)',
  rougeOpacity: 'rgb(255,99,132,0.5)',
  bleu: 'rgb(54,162,235)',
  bleuOpacity: 'rgb(54,162,235,0.5)',
  orange: 'rgb(170,102,51)',
  orangeOpacity: 'rgb(170,102,51,0.5)' };

const contentType='application/json; charset=utf-8';

/* Construction des callbox de type success */
const callboxInformation='<div class="callout primary text-justify" data-closable="slide-out-right"><p style="color:#00445b;" class="open-sans" cell">Information! ';
const callboxSuccess='<div class="callout success text-justify" data-closable="slide-out-right"><p style="color:#00445b;" class="open-sans" cell">Bravo ! ';
const callboxError='<div class="callout error text-justify" data-closable="slide-out-right"><p style="color:#00445b;" class="open-sans" cell">Ooups ! ';
const callboxFermer='<button class="close-button" aria-label="Fermer la fenêtre" type="button" data-close><span aria-hidden="true">&times;</span></button></div>';

/**
 * Affiche le graphique des sources
 */
const dessineMoiUnMouton= function( labels, data1, data2, data3) {
  const data = {
    labels,
    datasets: [{
      label: 'Bug',
      pointBorderColor: '#00445b',
      pointBackgroundColor: '#00445b',
      borderWidth: 2,
      radius: 0,
      data: data1,
      fill: true,
      borderColor: chartColors.orange,
      backgroundColor: chartColors.orangeOpacity,
      tension: 0.2 },
    {
      label: 'Vulnérabilité',
      pointBorderColor: '#C64444',
      pointBackgroundColor: '#C64444',
      borderWidth: 2,
      radius: 0,
      data: data2,
      fill: true,
      borderColor: chartColors.rouge,
      backgroundColor: chartColors.rougeOpacity,
      tension: 0.2 },
    {
      label: 'Mauvaise pratique',
      pointBorderColor: '#C64444',
      pointBackgroundColor: '#C64444',
      borderWidth: 2,
      radius: 0,
      data: data3,
      fill: true,
      borderColor: chartColors.bleu,
      backgroundColor: chartColors.bleuOpacity,
      tension: 0.2}]};
const options = {
  aspectRatio:3,
  animations: { radius: { duration: 400, easing: 'linear' } },
  maintainAspectRatio: true,
  responsive: true,
  layout: {
      padding: { left: 20, top: 20 }},
      scales: {
      x: {
          type: 'time',
          time: {
            unit: 'day',
            unitStepSize: 1,
            displayFormats: { 'day': 'YYYY MMM DD' } },
          display: true },
      y: {
        display: true,
        type: 'logarithmic',
        position: 'right',
        title: { display: true, text: 'Violations', color: '#00445b' },
        ticks: { color: '#00445b' }}
    },
    plugins: {
      tooltip: { enabled: false },
      legend: { position: 'bottom' },
      datalabels: {
        display: true,
        align: 'end', anchor: 'right',
        color: '#000',
        font: function (context) {
          const w = context.chart.width;
          return { size: w < 512 ? 11 : 12, weight: 'bold'};
        }
      }
    }};

  const chartStatus = Chart.getChart('graphique-anomalie');
  if (chartStatus !== undefined) {
    chartStatus.destroy();
  }

  const ctx = document.getElementById('graphique-anomalie').getContext('2d');
  const charts = new Chart(ctx, { type: 'line', data, options });
  if (charts === null) {
    console.info('null');
  }
};

/* On récupère les datatset */
const dataAttribut = document.getElementById('graphique-anomalie');
const _data1= dataAttribut.dataset.data1;
const _data2= dataAttribut.dataset.data2;
const _data3= dataAttribut.dataset.data3;
const _labels= dataAttribut.dataset.label;

/** je ne sais pas d'ou cela sort ! */
dessineMoiUnMouton(
  Object.values(JSON.parse(_labels)),
  Object.values(JSON.parse(_data1)),
  Object.values(JSON.parse(_data2)),
  Object.values(JSON.parse(_data3)));

/**
 * description
 * Création du sélecteur de projet.
 */
 const selectVersion=function(mavenKey) {
  const data={ mavenKey };
  const options = {
    url: 'http://localhost:8000/api/liste/version', type: 'GET',
    dataType: 'json', data, contentType };

  return $.ajax(options)
    .then(function (r) {
      $('.js-version').select2({
        placeholder: 'Cliquez pour ouvrir la liste',
        selectOnClose: true,
        width: '100%',
        minimumResultsForSearch: 5,
        language: 'fr',
        data: r.liste});
      $('.analyse').removeClass('hide');
    });
};

/**
 * description
 * On affiche la liste des projets et on nettoie le formulaire
 */
 $('.js-ajouter-analyse').on('click', function () {
 const mavenKey=$('#js-nom').data('maven');

 /* On nettoie le formulaire */
 $('#bloquant,#critique, #majeur, #mineur, #info').val('');
 // On desactive l'option : par défaut la version que
 // l'On ajoute n'est pas la version de référence
 if ($('.switch-active').css('display')==='block') {
   $('#switch').click();
  }

 /* On charge la liste */
 selectVersion(mavenKey);

 /* On ouvre la fenêtre modale */
 $('#modal-ajouter-analyse').foundation('open');
});

/* On recharge la page pour mettre à jour la vue */
$('#fermer-choisir-analyse').on('click', ()=>{
  location.reload();
});

/**
 * description
 * On affiche la liste des projets et on nettoie le formulaire
 */
 $('.js-modifier-analyse').on('click', function () {

  const poubelle=`<svg version="1.0" xmlns="http://www.w3.org/2000/svg" viewbox="0 0 512 512" class="poubelle-svg"  preserveAspectRatio="xMidYMid meet"><g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)" stroke="none">    <path d="M1871 5109 c-128 -25 -257 -125 -311 -241 -37 -79 -50 -146 -50 -258 l0 -88 -292 -5 c-308 -4 -329 -7 -448 -57 -171 -72 -327 -228 -400 -400 -41 -97 -51 -152 -57 -297 l-6 -143 2253 0 2253 0 -6 143 c-6 145 -16 200 -57 297-73 172 -229 328 -400 400 -119 50 -140 53 -447 57 l-293 5 0 88 c0 48 -5 111 -10 141 -34 180 -179 325 -359 359 -66 12 -1306 12 -1370 -1z m1359 -309 c60 -31 80 -78 80 -190 l0 -90 -750 0 -750 0 0 90 c0 110 20 159 78 189 36 19 60 20 670 21 615 0 634 -1 672 -20z"/> <path d="M626 3283 c3 -21 63 -684 134 -1473 136 -1518 135 -1505 194 -1599 64 -100 180 -179 295 -201 73 -14 2549 -14 2622 0 115 22 231 101 295 201 59 94 58 81 194 1599 71 789 131 1452 134 1473 l4 37 -1938 0 -1938 0 4 -37z m1134 -283 c43 -22 65 -55 74 -110 11 -69 99 -2156 92 -2185 -10 -40 -69 -93 -112 -101 -83 -15 -167 45 -178 128 -6 46 -96 2049 -96 2134 0 118 115 188 220 134z m870 0 c26 -13 47 -34 60 -60 20 -39 20 -57 20 -1130 0 -1073 0 -1091 -20 -1130 -23 -45 -80 -80 -130 -80 -50 0 -107 35 -130 80 -20 39 -20 57 -20 1130 0 1073 0 1091 20 1130 37 73 124 99 200 60z m893 -13 c66 -50 66 20 13 -1166 -26 -592 -52 -1092 -57 -1113 -18 -69 -99 -118 -174 -104 -42 8 -101 62 -111 101 -7 29 81 2116 92 2185 9 54 35 91 79 112 52 25 114 19 158 -15z"/></g></svg>`;

  /* On récupère la clé maven */
  const data = { mavenKey: $('#js-nom').data('maven') };
  const options = {
    url: 'http://localhost:8000/api/dash/version/liste', type: 'PUT',
    dataType: 'json', data: JSON.stringify(data), contentType };

  $.ajax(options).then(t => {
    /* On gére le résultat de la requête */
    if (t.code!=='OK') {
      const message=`Je n'ai pas réussi à charger la liste des versions (${t.code}).`;
      $('#message').html(callboxError+message+callboxFermer);
      return;
    } else {
      const message='La liste des versions a été chargée correctement.';
      $('#message').html(callboxInformation+message+callboxFermer);
    }

    /* On boucle pour construire le tableau */
    let ligne=0;
    let html='';
    let switchFavori='';
    let switchReference='';
    let favori='FALSE';
    let reference='FALSE';

    $('#tableau-liste-version').html(html);

    t.versions.forEach(version => {
      ligne++;
      /* On défini le switch pour le favori */
      switchFavori='<div class="siwtch js-switch-favori">';
      switchFavori+=`<input class="switch-input" id="switch-favori-${ligne}" type="checkbox" name="switch-favori-${ligne}">`;
      switchFavori+=`<label class="switch-paddle" for="switch-favori-${ligne}">`;
      switchFavori+='<span class="show-for-sr">favori</span>';
      switchFavori+='</label></div>';

      /* On défini le switch pour la référence */
      switchReference='<div class="siwtch js-switch-reference">';
      switchReference+=`<input class="switch-input" id="switch-reference-${ligne}" type="checkbox" name="switch-reference-${ligne}">`;
      switchReference+=`<label class="switch-paddle" for="switch-reference-${ligne}">`;
      switchReference+='<span class="show-for-sr">reference</span>';
      switchReference+='</label></div>';

      /*  On construit le tableau */
      html  =`<tr id="ligne-${ligne}">`;
      html +=`<td id="poubelle-${ligne}" class="text-left">${poubelle}</td>`;
      html +=`<td id="date-${ligne}" class="text-left">${version.date}</td>`;
      html +=`<td id="version-${ligne}" class="text-left">${version.version}</td>`;
      html +=`<td id="favori-${ligne}" class="text-left">${switchFavori}</td>`;
      html +=`<td id="reference-${ligne}" class="text-left">${switchReference}</td>`;
      html +='</tr>';

      /* On ajoute la ligne */
      $('#tableau-liste-version').append(html);

      /**
        * Favori|reference enable
        * 0 = FALSE (false), 1 = TRUE (true)
        */
      if (version.favori===1) {
        $(`#switch-favori-${ligne}`).click();
      }

      if (version.initial===1) {
        $(`#switch-reference-${ligne}`).click();
      }
    });

    /* On gére le changement de favori */
    $('[id^=switch-favori-]').on('click', e =>{
      // on récupère la version et la date
      const id=$(e.target).attr('id');
      const l=id.split('-');
      const  version = $(`#version-${l[2]}`).text().trim();
      const  date = $(`#date-${l[2]}`).text().trim();

      if ($(`#${id}:checked`).length===1) {
        favori='TRUE';
      } else {
        favori='FALSE';
      }
        const dataFavori = { mavenKey: $('#js-nom').data('maven'), favori, version, date };
        const optionsFavori = {
          url: 'http://localhost:8000/api/dash/version/favori', type: 'PUT',
          dataType: 'json', data: JSON.stringify(dataFavori), contentType };
        /**
         * On appel l'API de mise à jour du favori
         */
        $.ajax(optionsFavori).then(() => {
          if (t.code==='OK') {
            const message='Mise à jour du favori.';
            $('#message').html(callboxSuccess+message+callboxFermer);
          } else {
            const message=`Erreur lors de la mise à jour (${t.code}).`;
            $('#message').html(callboxError+message+callboxFermer);
          }
        });
    });

    /* On gére le changement de reference */
    $('[id^=switch-reference-]').on('click', e=>{
      /* on récupère la version et la date */
      const id=$(e.target).attr('id');
      const l=id.split('-');
      const  version=$(`#version-${l[2]}`).text().trim();
      const  date=$(`#date-${l[2]}`).text().trim();

      if ($(`#${id}:checked`).length===1){
        reference='TRUE';
      } else {
        reference='FALSE';
      }

      /**
       * On appel l'API de mise àjour de la version de référence
       */
      const dataReference = { mavenKey: $('#js-nom').data('maven'), reference, version, date };
      const optionsReference = {
        url: 'http://localhost:8000/api/dash/version/reference', type: 'PUT',
        dataType: 'json', data: JSON.stringify(dataReference), contentType };

        $.ajax(optionsReference).then(() => {
        if (t.code==='OK') {
          const message='Mise à jour de la version de référence.';
          $('#message').html(callboxSuccess+message+callboxFermer);
        } else {
          const message=`Erreur lors de la mise à jour (${t.code}).`;
          $('#message').html(callboxError+message+callboxFermer);
        }
      });
    });

   $('[id^=poubelle-]').on('click', e=>{
    /* on récupère la version et la date */
    const id=$(e.currentTarget).attr('id');
    const l=id.split('-');
    const  version = $(`#version-${l[1]}`).text().trim();
    const  date = $(`#date-${l[1]}`).text().trim();

    /**
     * On l'API de suppresion de la version dans l'historique
     */
    const dataPoubelle = { mavenKey: $('#js-nom').data('maven'), version, date };
    const optionsPoubelle = {
      url: 'http://localhost:8000/api/dash/version/poubelle', type: 'PUT',
      dataType: 'json', data: JSON.stringify(dataPoubelle), contentType };

    $.ajax(optionsPoubelle).then(() => {
      if (t.code==='OK') {
      const message='Suppresion de cette version dans l\'historique.';
      $('#message').html(callboxSuccess+message+callboxFermer);
      // On masque la ligne
      $('#ligne-'+l[1]).hide();
    } else {
      const message=`Erreur lors de la suppresion (${t.code}).`;
      $('#message').html(callboxError+message+callboxFermer);
    }
  });

  });
   $('#modal-modifier-analyse').foundation('open');
  });
});

/**
 * description
 * On charge les données
 */
$('select[name="version"]').change(function () {
  /* On affiche la clé */
  $('#key-maven').html($('#js-nom').data('maven').trim());

  /* On affiche le nom */
  const n=$('#js-nom').data('maven').trim();
  const name=n.split(':');
  $('#nom').html(name[1]);

  /* On récupère la date et l'a nettoie avant de l'envoyer */
  const d=$('#liste-version :selected').text();
  const d1=d.split('(');
  const d2=d1[1].split(')');
  const d3=d2[0].split('+');
  const t0 = document.getElementById('date');
  t0.dataset.date=(d3[0]);

  /* On affiche la version */
  $('#version').html(d1[0]);
  /* On affiche la date */
  $('#date').html(d3[0]);

  /**
   *  On appel l'API de récupération des versions
  */
  const data = { mavenKey: $('#key-maven').text().trim(), date:d2[0] };
  const options = {
    url: 'http://localhost:8000/api/get/version', type: 'PUT',
    dataType: 'json', data: JSON.stringify(data), contentType };

  $.ajax(options).then(t => {

    const tNotes = ['', 'A', 'B', 'C', 'D', 'E', 'Z'];
    const vert1='vert1';
    const vert2='vert2';
    const jaune='jaune';
    const orange='orange';
    const rouge='rouge';
    const bleu='bleu';

    let couleur1, couleur2, couleur3, couleur4;

    if (t.noteReliability === 1 ) {
      couleur1 = vert1;
    }
    if (t.noteSecurity === 1) {
      couleur2 = vert1;
    }
    if (t.noteSqale === 1) {
      couleur3 = vert1;
    }
    if (t.noteHotspotsReview === 1) {
      couleur4 = vert1;
    }

    if (t.noteReliability === 2) {
      couleur1 = vert2;
    }
    if (t.noteSecurity === 2) {
      couleur2 = vert2;
    }
    if (t.noteSqale === 2) {
      couleur3 = vert2;
    }
    if (t.noteHotspotsReview === 2) {
      couleur4 = vert2;
    }

    if (t.noteReliability === 3) {
      couleur1 = jaune;
    }
    if (t.noteSecurity === 3) {
      couleur2 = jaune;
    }
    if (t.noteSqale === 3) {
      couleur3 = jaune;
    }
    if (t.noteHotspotsReview === 3) {
      couleur4 = jaune;
    }

    if (t.noteReliability === 4) {
      couleur1 = orange;
    }
    if (t.noteSecurity === 4) {
      couleur2 = orange;
    }
    if (t.noteSqale === 4) {
      couleur3 = orange;
    }
    if (t.noteHotspotsReview === 4) {
      couleur4 = orange;
    }

    if (t.noteReliability === 5) {
      couleur1 = rouge;
    }
    if (t.noteSecurity === 5) {
      couleur2 = rouge;
    }
    if (t.noteSqale === 5) {
      couleur3 = rouge;
    }
    if (t.noteHotspotsReview === 5) {
      couleur4 = rouge;
    }

    /*  On a pas de note pour les hotspots */
    if (t.noteHotspotsReview === 6) {
      couleur4 = bleu;
    }

    const noteReliability = tNotes[parseInt(t.noteReliability,10)];
    const noteSecurity = tNotes[parseInt(t.noteSecurity,10)];
    const noteSqale = tNotes[parseInt(t.noteSqale,10)];
    const noteHotspotsReview = tNotes[parseInt(t.noteHotspotsReview,10)];

    /*  On affiche les notes */
    $('#note-reliability').html(`<span  class="${couleur1}">${noteReliability}</span>`);
    $('#note-security').html(`<span  class="${couleur2}">${noteSecurity}</span>`);
    $('#note-sqale').html(`<span  class="${couleur3}">${noteSqale}</span>`);
    $('#note-hotspots-review').html(`<span  class="${couleur4}">${noteHotspotsReview}</span>`);

    /* Historique*/
    const t1 = document.getElementById('note-reliability');
    const t2 = document.getElementById('note-security');
    const t3 = document.getElementById('note-sqale');
    const t4 = document.getElementById('note-hotspots-review');
    t1.dataset.noteReliability=(noteReliability);
    t2.dataset.noteSecurity=(noteSecurity);
    t3.dataset.noteSqale=(noteSqale);
    t4.dataset.noteHotspotsReview=(noteHotspotsReview);

    /* On affiche le nombre de bugs, de vulnérabilités et de mauvaises pratiques. */
    $('#bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bug));
    $('#vulnerabilities').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilities));
    $('#code-smell').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmell));
    let verifyHotspotsReview=t.hotspotsReview;
    if (verifyHotspotsReview !== -1) {
      $('#hotspots-review').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(verifyHotspotsReview));
    }
    else {
      $('#hotspots-review').html('-');
    }

    /* historique */
    const t5 = document.getElementById('bug');
    const t6 = document.getElementById('vulnerabilities');
    const t7 = document.getElementById('code-smell');
    const t8 = document.getElementById('hotspots-review');
    t5.dataset.bug=(t.bug);
    t6.dataset.vulnerabilities=(t.vulnerabilities);
    t7.dataset.codeSmell=(t.codeSmell);
    t8.dataset.hotspotsReview=(t.hotspotsReview);

    /* On affiche les autres métriques */
    $('#ncloc').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.ncloc));
    $('#lines').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.lines));
    $('#dette').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.dette/60/60));

    $('#duplication').html(new Intl.NumberFormat('fr-FR', { style: 'percent',maximumFractionDigits: 2 }).format(t.duplication/100));
    $('#coverage').html(new Intl.NumberFormat('fr-FR', { style: 'percent',maximumFractionDigits: 2 }).format(t.coverage/100));
    $('#tests').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.tests));

    /* historique */
    const t9 = document.getElementById('ncloc');
    const t10 = document.getElementById('lines');
    const t11 = document.getElementById('coverage');
    const t12 = document.getElementById('tests');
    const t13 = document.getElementById('dette');
    const t14 = document.getElementById('duplication');
    t9.dataset.ncloc=(t.ncloc);
    t10.dataset.lines=(t.lines);
    t11.dataset.coverage=(t.coverage);
    t12.dataset.tests=(t.tests);
    t13.dataset.dette=(t.dette);
    t14.dataset.duplication=(t.duplication);
  });
});

/**
 * description
 * Enregistrement des données
*/
$('.js-enregistrer-analyse').on('click', ()=>{
  if ($('select[name="version"]').val()==='') {
    return;
  }

  const mavenKey=$('#js-nom').data('maven').trim();
  const nom=$('#js-nom').text().trim();
  const version=$('#version').text().trim();
  const t0 = document.getElementById('date');
  const dateVersion=t0.dataset.date;

  const t1 = document.getElementById('note-reliability');
  const t2 = document.getElementById('note-security');
  const t3 = document.getElementById('note-sqale');
  const t4 = document.getElementById('note-hotspots-review');
  const noteReliability=t1.dataset.noteReliability;
  const noteSecurity=t2.dataset.noteSecurity;
  const noteSqale=t3.dataset.noteSqale;
  const noteHotspotsReview=t4.dataset.noteHotspotsReview;

  const t5 = document.getElementById('bug');
  const t6 = document.getElementById('vulnerabilities');
  const t7 = document.getElementById('code-smell');
  const t8 = document.getElementById('hotspots-review');
  const bug=t5.dataset.bug;
  const vulnerabilities=t6.dataset.vulnerabilities;
  const codeSmell=t7.dataset.codeSmell;
  const hotspotsReview=t8.dataset.hotspotsReview;
  const defauts=parseInt(bug,10)+parseInt(vulnerabilities,10)+parseInt(codeSmell,10);

  const t9 = document.getElementById('ncloc');
  const t10 = document.getElementById('lines');
  const t11 = document.getElementById('coverage');
  const t12 = document.getElementById('duplication');
  const t13 = document.getElementById('tests');
  const t14 = document.getElementById('dette');
  const ncloc=t9.dataset.ncloc;
  const lines=t10.dataset.lines;
  const coverage=t11.dataset.coverage;
  const duplication=t12.dataset.duplication;
  const tests=t13.dataset.tests;
  const dette=t14.dataset.dette;

  let bloquant=$('#bloquant').val().trim();
  let critique=$('#critique').val().trim();
  let majeur=$('#majeur').val().trim();
  let mineur=$('#mineur').val().trim();
  let info=$('#info').val().trim();

  if ($('#bloquant').val()==='') {
    bloquant=0;
  }
  if ($('#critique').val()==='') {
    critique=0;
  }
  if ($('#majeur').val()==='') {
    majeur=0;
  }
  if ($('#mineur').val()==='') {
    mineur=0;
  }
  if ($('#info').val()==='') {
    info=0;
  }

  let initial='FALSE';
  if ($('.switch-active').css('display')==='block') {
    initial='TRUE';
  }
  if ($('.switch-inactive').css('display')==='block') {
    initial='FALSE';
  }

  const data={
    date:dateVersion,
    mavenKey, nom, version,
    noteReliability, noteSecurity,
    noteSqale, noteHotspotsReview,
    defauts, bug, vulnerabilities,codeSmell,
    hotspotsReview,
    lines, ncloc, coverage, duplication,tests, dette,
    bloquant, critique, majeur,
    mineur, info, initial };

    /**
     * On lance l'API de mise à jour
     */
    const options = {
    url: 'http://localhost:8000/api/suivi/mise-a-jour', type: 'PUT',
    dataType: 'json', data: JSON.stringify(data), contentType };

  $.ajax(options).then(t => {
      if (t.code==='OK') {
        const success='<span class="lead" style="color:#187e3d">INFO</span> : ';
        const message='Enregistrement des informations effectué.';
        $('.info').html(`${success}${message}`);
        } else {
          const error=`<span class="lead" style="color:#971c09">ERROR (${t.code})</span> : `;
          const message='L\'enregistrement n\'a pas été effectué !! !.';
          $('.info').html(`${error}${message}`);
        }
    });
});


/**
 * description
 * Génère une edition PDF
*/
$('.lien-editer').on('click', ()=>{
  console.log('impression en cours');
  const date2 = new Date();
  const element1 = document.getElementById('element1-to-print');
  const element2 = document.getElementById('element2-to-print');
  const element3 = document.getElementById('element3-to-print');

  /* On récupère le nom */
  const n=$('#js-nom').data('maven').trim();
  const name=n.split(':');

  const opt = {
    margin:       10,
    filename:     `${name[1]}-suivi-${date2.toLocaleDateString('fr-FR')}.pdf`,
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2 },
    putOnlyUsedFonts:true,
    pagebreak: { mode: 'avoid-all'},
    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }};

  const debut=`<h1 class="claire-hand">Rapport de suivi des indicateurs.</h1>
               <p class="open-sans">Date : ${date2.toLocaleDateString('fr-FR')}</p><br />`;
  const fin='<br /><br /><p class="open-sans text-center" style="font-size:4rem;">* * * *</p>';
  const tempo=debut+element1.innerHTML+element2.innerHTML+element3.innerHTML+fin;
  html2pdf().set(opt).from(tempo).toPdf().get('pdf').save();
});
