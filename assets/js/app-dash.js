/*
 * Copyright (c) 2021-2022.
 * Laurent HADJADJ <laurent_h@me.com>.
 * Licensed Creative Common  CC-BY-NC-SA 4.0.
 * Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 * http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

import '../css/dash.css';

// Intégration de jquery
// eslint-disable-next-line no-unused-vars
import $ from 'jquery';

import 'select2';
import 'select2/dist/js/i18n/fr.js';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
// eslint-disable-next-line no-unused-vars
import moment from "moment";
import "moment/locale/fr";
import "chartjs-adapter-moment";
Chart.register(ChartDataLabels);

const CHART_COLORS = {
  rouge: 'rgb(255, 99, 132)',
  rouge_opacity: 'rgb(255, 99, 132, 0.5)',
  bleu: 'rgb(54, 162, 235)',
  bleu_opacity: 'rgb(54, 162, 235, 0.5)',
  orange: 'rgb(170,	102,	51)',
  orange_opacity: 'rgb(170,	102, 51, 0.5)',
};

const contentType='application/json; charset=utf-8';

/**
 * Affiche le graphique des sources
 */
function dessineMoiUnMouton( labels, data1, data2, data3) {
  const data = {
    labels: labels,
    datasets: [{
      label: 'Bug',
      pointBorderColor: '#00445b',
      pointBackgroundColor: '#00445b',
      borderWidth: 2,
      radius: 0,
      data: data1,
      fill: true,
      borderColor: CHART_COLORS.orange,
      backgroundColor: CHART_COLORS.orange_opacity,
      tension: 0.2,
    },
    {
      label: 'Vulnérabilité',
      pointBorderColor: '#C64444',
      pointBackgroundColor: '#C64444',
      borderWidth: 2,
      radius: 0,
      data: data2,
      fill: true,
      borderColor: CHART_COLORS.rouge,
      backgroundColor: CHART_COLORS.rouge_opacity,
      tension: 0.2,
    },
    {
      label: 'Mauvaise pratique',
      pointBorderColor: '#C64444',
      pointBackgroundColor: '#C64444',
      borderWidth: 2,
      radius: 0,
      data: data3,
      fill: true,
      borderColor: CHART_COLORS.bleu,
      backgroundColor: CHART_COLORS.bleu_opacity,
      tension: 0.2,
    },
    ]
  };
const options = {
  animations: { radius: { duration: 400, easing: 'linear', } },
    maintainAspectRatio: true,
    responsive: true,
    layout: {
      padding: {
          left: 20
      }},
    scales: {
      x: {
          type: 'time',
          time: {
            unit: 'day',
            unitStepSize: 1,
            displayFormats: { 'day': 'YYYY MMM DD' },
          },
          display: true,
        },
      y: {
        display: true,
        type: 'logarithmic',
        position: 'right',
        title: { display: true, text: 'Violations', color: '#00445b' },
        ticks: { color: '#00445b' }

      }
    },
    plugins: {
      tooltip: { enabled: false },
      datalabels: {
        display: true,
        align: 'end', anchor: 'right',
        color: '#000',
        font: function (context) {
          const w = context.chart.width;
          return { size: w < 512 ? 11 : 12, weight: 'bold', };
        },
      }
    }};

  const chartStatus = Chart.getChart("graphique-anomalie");
  if (chartStatus !== undefined) { chartStatus.destroy(); }

  const ctx = document.getElementById('graphique-anomalie').getContext('2d');
  const charts = new Chart(ctx, { type: 'line', data: data, options: options });
  if (charts === null) { console.log(''); }
}


// On récupère les datatset
const data_attribut = document.getElementById('graphique-anomalie');
const _data1= data_attribut.dataset.data1;
const _data2= data_attribut.dataset.data2;
const _data3= data_attribut.dataset.data3;
const _labels= data_attribut.dataset.label;


dessineMoiUnMouton(Object.values(JSON.parse(_labels)), Object.values(JSON.parse(_data1)), Object.values(JSON.parse(_data2)), Object.values(JSON.parse(_data3)));

/**
 * description
 * Création du selecteur de projet.
 */
 function select_version(maven_key) {
  const data={ maven_key: maven_key}
  const options = {
    url: 'http://localhost:8000/api/liste/version', type: 'GET', dataType: 'json',
    data: data, contentType: contentType }

  return $.ajax(options)
    .then(function (r) {
      $('.js-version').select2({
        placeholder: 'Cliquez pour ouvrir la liste',
        selectOnClose: true,
        width: '100%',
        minimumResultsForSearch: 5,
        language: "fr",
        data: r.liste,
      });
      $('.analyse').removeClass('hide');
    })
}

/**
 * description
 * On affiche la liste des projets et on nettoie le formulaire
 */
 $('.js-ajouter-analyse').on('click', function () {
 const maven_key=$("#js-nom").data('maven');

 // On nettoie le formulaire
 $('#bloquant,#critique, #majeur, #mineur, #info').val('');
 // On desactive l'option : par défaut la version que
 // l'on ajoute n'est pas la version de référence
 if ($('.switch-active').css('display')==='block') { $("#switch").click() }

 // On charge la liste
 select_version(maven_key);

 $('#modal-ajouter-analyse').foundation('open');
})

/**
 * description
 * On charge les données
 */
$('select[name="version"]').change(function () {
  // On affiche la clé
  $('#key-maven').html($("#js-nom").data('maven').trim());

  // On affiche le nom
  const n=$("#js-nom").data('maven').trim();
  const name=n.split(':');
  $('#nom').html(name[1]);

  // On récupère la date et l'a nettoie avant de l'envoyer
  const d=$('#liste-version :selected').text();
  const d1=d.split('(');
  const d2=d1[1].split(')');
  const d3=d2[0].split('+')
  const t0 = document.getElementById('date');
  t0.dataset.date=(d2[0]);

  // On affiche la version
  $('#version').html(d1[0]);
  // On affiche la date
  $('#date').html(d3[0]);

  const data = { maven_key: $('#key-maven').text().trim(), date:d2[0] }
  const options = {
    url: 'http://localhost:8000/api/get/version', type: 'PUT', dataType: 'json', data: JSON.stringify(data), contentType: contentType
  }

  $.ajax(options).then((t) => {

    const t_notes = ['', 'A', 'B', 'C', 'D', 'E'];
    let couleur1, couleur2, couleur3, couleur4;

    if (t.note_reliability === 1 ) { couleur1 = 'vert1'; }
    if (t.note_security === 1) { couleur2 = 'vert1'; }
    if (t.note_sqale === 1) { couleur3 = 'vert1'; }
    if (t.note_hotspots_review === 1) { couleur4 = 'vert1'; }

    if (t.note_reliability === 2) { couleur1 = 'vert2'; }
    if (t.note_security === 2) { couleur2 = 'vert2'; }
    if (t.note_sqale === 2) { couleur3 = 'vert2'; }
    if (t.note_hotspots_review === 2) { couleur4 = 'vert2'; }

    if (t.note_reliability === 3) { couleur1 = 'jaune'; }
    if (t.note_security === 3) { couleur2 = 'jaune'; }
    if (t.note_sqale === 3) { couleur3 = 'jaune'; }
    if (t.note_hotspots_review === 3) { couleur4 = 'jaune'; }

    if (t.note_reliability === 4) { couleur1 = 'orange'; }
    if (t.note_security === 4) { couleur2 = 'orange'; }
    if (t.note_sqale === 4) { couleur3 = 'orange'; }
    if (t.note_hotspots_review === 4) { couleur4 = 'orange'; }

    if (t.note_reliability === 5) { couleur1 = 'rouge'; }
    if (t.note_security === 5) { couleur2 = 'rouge'; }
    if (t.note_sqale === 5) { couleur3 = 'rouge'; }
    if (t.note_hotspots_review === 5) { couleur4 = 'rouge'; }

    const note_reliability = t_notes[parseInt(t.note_reliability,10)];
    const note_security = t_notes[parseInt(t.note_security,10)];
    const note_sqale = t_notes[parseInt(t.note_sqale,10)];
    const note_hotspots_review = t_notes[parseInt(t.note_hotspots_review,10)];

    // On affiche les notes
    $('#note-reliability').html('<span class="' + couleur1 + '">' + note_reliability + '</span>');
    $('#note-security').html('<span class="' + couleur2 + '">' + note_security + '</span>');
    $('#note-sqale').html('<span class="' + couleur3 + '">' + note_sqale + '</span>');
    $('#note-hotspots-review').html('<span class="' + couleur4 + '">' + note_hotspots_review + '</span>');

    // Historique
    const t1 = document.getElementById('note-reliability');
    const t2 = document.getElementById('note-security');
    const t3 = document.getElementById('note-sqale');
    const t4 = document.getElementById('note-hotspots-review');
    t1.dataset.note_reliability=(t.note_reliability);
    t2.dataset.note_security=(t.note_reliability);
    t3.dataset.note_sqale=(t.note_reliability);
    t4.dataset.note_hotspots_review=(t.note_hotspots_review);

    // On affiche le nombre de bugs, de vulnérabilités et de mauvaises pratiques.
    $('#bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.bug));
    $('#vulnerabilities').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.vulnerabilities));
    $('#code-smell').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.codesmell));
    $('#hotspots-review').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.hotspots_review));

    //historique
    const t5 = document.getElementById('bug');
    const t6 = document.getElementById('vulnerabilities');
    const t7 = document.getElementById('code-smell');
    const t8 = document.getElementById('hotspots-review');
    t5.dataset.bug=(t.bug);
    t6.dataset.vulnerabilities=(t.vulnerabilities);
    t7.dataset.codesmell=(t.codesmell);
    t8.dataset.hotspots_review=(t.hotspots_review);

    // On affiche les autres métriques
    $('#ncloc').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.ncloc));
    $('#lines').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.lines));
    $('#dette').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.dette/60/60));

    $('#duplication').html(new Intl.NumberFormat('fr-FR', { style: 'percent',maximumFractionDigits: 2 }).format(t.duplication/100));
    $('#coverage').html(new Intl.NumberFormat('fr-FR', { style: 'percent',maximumFractionDigits: 2 }).format(t.coverage/100));
    $('#tests').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.tests));

    //historique
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
})

/**
 * description
 * Enregistrement des données
*/
$('.js-enregistrer-analyse').on('click', ()=>{
  if ($('select[name="version"]').val() == '') { return }

  const maven_key=$("#js-nom").data('maven').trim();
  const nom=$("#js-nom").text().trim();
  const version=$("#js-version").text().trim();
  const t0 = document.getElementById('date');
  const date=t0.dataset.date;

  const t1 = document.getElementById('note-reliability');
  const t2 = document.getElementById('note-security');
  const t3 = document.getElementById('note-sqale');
  const t4 = document.getElementById('note-hotspots-review');
  const note_reliability=t1.dataset.note_reliability;
  const note_security=t2.dataset.note_security;
  const note_sqale=t3.dataset.note_sqale;
  const note_hotspots_review=t4.dataset.note_hotspots_review;

  const t5 = document.getElementById('bug');
  const t6 = document.getElementById('vulnerabilities');
  const t7 = document.getElementById('code-smell');
  const t8 = document.getElementById('hotspots-review');
  const bug=t5.dataset.bug;
  const vulnerabilities=t6.dataset.vulnerabilities;
  const codesmell=t7.dataset.codesmell;
  const hotspots_review=t8.dataset.hotspots_review;

  const t9 = document.getElementById('ncloc');
  const t10 = document.getElementById('lines');
  const t11 = document.getElementById('coverage');
  const t12 = document.getElementById('tests');
  const t13 = document.getElementById('dette');
  const ncloc=t9.dataset.ncloc;
  const lines=t10.dataset.lines;
  const coverage=t11.dataset.coverage;
  const tests=t12.dataset.tests;
  const dette=t13.dataset.dette;

  let bloquant=$('#bloquant').val().trim();
  let critique=$('#critique').val().trim();
  let majeur=$('#majeur').val().trim();
  let mineur=$('#mineur').val().trim();
  let info=$('#info').val().trim();

  if ($('#bloquant').val()=='') { bloquant=0; }
  if ($('#critique').val()=='') { critique=0; }
  if ($('#majeur').val()=='') { majeur=0; }
  if ($('#mineur').val()=='') { mineur=0; }
  if ($('#info').val()=='') { info=0; }

  let initial=1;
  if ($('.switch-active').css('display')==='block') { initial='TRUE'; }
  if ($('.switch-inactive').css('display')==='block') { initial='FALSE'; }

  const data={
    maven_key:maven_key, nom:nom, version:version, date:date,
    note_reliability:note_reliability, note_security:note_security,
    note_sqale:note_sqale, note_hotspots_review:note_hotspots_review,
    bug:bug, vulnerabilities:vulnerabilities,
    codesmell:codesmell, hotspots_review:hotspots_review,
    lines:lines,ncloc:ncloc, coverage:coverage, tests:tests, dette:dette,
    bloquant:bloquant, critique:critique, majeur:majeur,
    mineur:mineur, info:info, initial:initial
  }
  //console.log(data);
  const options = {
    url: 'http://localhost:8000/api/suivi/mise-a-jour', type: 'PUT', dataType: 'json',
    data: JSON.stringify(data), contentType: contentType }
    $.ajax(options).then((t) => {
        if (t.info=="OK") {
          $('#info').html('<span class="lead" style="color:#187e3d">INFO</span> : Enregistrement des informations effectué.');
          } else {
            $('#info').html('<span class="lead" style="color:#971c09">ERROR</span> : L\'enregistrement n\'a pas été réussi !! !.');
          }
      });
})
