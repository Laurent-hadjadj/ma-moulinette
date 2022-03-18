/*
 * Copyright (c) 2021-2022.
 * Laurent HADJADJ <laurent_h@me.com>.
 * Licensed Creative Common  CC-BY-NC-SA 4.0.
 * Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 * http://creativecommons.org/licenses/by-nc-sa/4.0/
  */

import '../css/owasp.css';

// Intégration de jquery
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

console.log('Owasp : Chargement de webpack !');

const contentType='application/json; charset=utf-8';
const note = ['', 'A', 'B', 'C', 'D', 'E'];
const couleur = ['', 'badge-vert1', 'badge-vert2', 'badge-jaune', 'badge-orange', 'badge-rouge'];

const liste_owasp2017 = [
  "", "A1 - Attaques d'injection", "A2 - Authentification défaillante", "A3 - Fuites de données sensibles",
  "A4 - Entités externes XML (XXE)", "A5 - Contrôle d'accès défaillant", "A6 - Configurations défaillantes",
  "A7 - Attaques cross-site scripting (XSS)", "A8 - Désérialisation sans validation", "A9 - Composants tiers vulnérables",
  "A10 - Journalisation et surveillance insuffisantes"];

 /**
 * description
 * Calcul la note des hotspots
 */
function calcul_note_hotspot(taux) {
  let c, n
  if (taux > 0.79) { c = couleur[1]; n = note[1] }
  if (taux > 0.71 && taux < 0.81) {
      c = couleur[2];
      n = note[2]
    }
  if (taux > 0.51 && taux < 0.71) {
      c = couleur[3];
      n = note[3]
    }
  if (taux > 0.31 && taux < 0.51) {
      c = couleur[4];
      n = note[4]
    }
  if (taux < 0.31) {
      c = couleur[5];
      n = note[5]
    }
  return [c, n]
}

/**
 * description
 * Récupération des informations sur les vulnérabilités OWASP.
 */
 function remplissage_owasp_info(id_maven) {
  if (id_maven === undefined) { return; }

  const data={'maven_key': id_maven };
  const options = {
    url: 'http://localhost:8000/api/peinture/owasp/liste', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(options).then((r) => {

    if (r['info'] === '406'){
        console.log('Le projet n\'existe pas..');
        return;
      }
    const html_01='</span> <span class="badge ';

    // On ajoute les valeurs pour les vulnérabilités
    $('#nombre_faille_owasp').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.total));
    $('#nombre_faille_bloquante').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.bloquante));
    $('#nombre_faille_critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.critique));
    $('#nombre_faille_majeure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.majeure));
    $('#nombre_faille_mineure').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.mineure));

    let c=[],n=[],i="";
    // Détails A1
    if (parseInt(r.a1_blocker + r.a1_critical + r.a1_major + r.a1_minor,10) === 0){
      c = couleur[1];
      n = note[1]
    }
    if (parseInt(r.a1_minor,10) > 1) {
      c = couleur[2];
      n = note[2]
    }
    if (parseInt(r.a1_major,10) > 1) {
      c = couleur[3];
      n = note[3]
     }
    if (parseInt(r.a1_critical,10) > 1) {
      c = couleur[4]; n = note[4] }
    if (parseInt(r.a1_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a1) + html_01 + c + '">' + n + '</span>';
    $('#a1').html(i);

    // Détails A2
    if (parseInt(r.a2_blocker + r.a2_critical + r.a2_major + r.a2_minor,10) === 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a2_minor,10) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a2_major,10) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a2_critical,10) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a2_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a2) + html_01 + c + '">' + n + '</span>';
    $('#a2').html(i);

    // Détails A3
    if (parseInt(r.a3_blocker + r.a3_critical + r.a3_major + r.a3_minor,10) === 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a3_minor,10) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a3_major,10) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a3_critical,10) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a3_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a3) + html_01 + c + '">' + n + '</span>';
    $('#a3').html(i);

    // Détails A4
    if (parseInt(r.a4_blocker + r.a1_critical + r.a1_major + r.a1_minor,10) === 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a4_minor,10) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a4_major,10) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a4_critical,10) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a4_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a4) + html_01 + c + '">' + n + '</span>';
    $('#a4').html(i);

    // Détails A5
    if (parseInt(r.a5_blocker + r.a5_critical + r.a5_major + r.a5_minor,10) === 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a5_minor,10) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a5_major,10) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a5_critical,10) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a5_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a5) + html_01 + c + '">' + n + '</span>';
    $('#a5').html(i);

    // Détails A6
    if (parseInt(r.a6_blocker + r.a6_critical + r.a6_major + r.a6_minor,10) === 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a6_minor,10) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a6_major,10) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a6_critical,10) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a6_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a6) + html_01 + c + '">' + n + '</span>';
    $('#a6').html(i);

    // Détails A7
    if (parseInt(r.a7_blocker + r.a7_critical + r.a7_major + r.a7_minor,10) === 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a7_minor,10) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a7_major,10) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a7_critical,10) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a7_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a7) + html_01 + c + '">' + n + '</span>';
    $('#a7').html(i);

    // Détails A8
    if (parseInt(r.a8_blocker + r.a8_critical + r.a8_major + r.a8_minor,10) === 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a8_minor,10) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a8_major,10) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a8_critical,10) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a8_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a8) + html_01 + c + '">' + n + '</span>';
    $('#a8').html(i);

    // Détails A9
    if (parseInt(r.a9_blocker + r.a9_critical + r.a9_major + r.a9_minor,10) === 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a9_minor,10) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a9_major,10) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a9_critical,10) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a9_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a9) + html_01 + c + '">' + n + '</span>';
    $('#a9').html(i);

    // Détails A10
    if (parseInt(r.a10_blocker + r.a10_critical + r.a10_major + r.a10_minor,10) === 0) { c = couleur[1]; n = note[1] }
    if (parseInt(r.a10_minor,10) > 1) { c = couleur[2]; n = note[2] }
    if (parseInt(r.a10_major,10) > 1) { c = couleur[3]; n = note[3] }
    if (parseInt(r.a10_critical,10) > 1) { c = couleur[4]; n = note[4] }
    if (parseInt(r.a10_blocker,10) > 1) { c = couleur[5]; n = note[5] }
    i = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.a10) + html_01 + c + '">' + n + '</span>';
    $('#a10').html(i);
  });
}

/**
 * description
 * Récupération des informations sur les hotspots OWASP
 */
function remplissage_hotspot_info(id_maven) {
  if (id_maven === undefined) { return }

  const data={'maven_key': id_maven };
  const options = {
    url: 'http://localhost:8000/api/peinture/owasp/hotspot/info/', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(options).then((r) => {
  let i='';
  const html_01='</span> <span class="badge ';

  // On compte le nombre de hotspot au status REVIEWED
  $('#hotspot-reviewed').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.reviewed)); sessionStorage.setItem('hotspot-reviewed', r.reviewed);

  // On compte le nombre de hotspot au status TO_REVIEW
 $('#hotspot-to-review').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.to_review)); sessionStorage.setItem('hotspot-to-review', r.to_review);

  // On affiche le nombre de hotspot OWASP et par la répartition
  $('#hotspot-total').html(r.total); sessionStorage.setItem('hotspot-total', r.total);
  $('#nombre_hotspot_high').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.high));
  $('#nombre_hotspot_medium').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.medium));
  $('#nombre_hotspot_low').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.low));

  let leTaux=1, _note=['badge-vert1', 'A'];
  const hotspot_total=parseInt(sessionStorage.getItem('hotspot-total'),10);
  if ( hotspot_total !==0 ) {
    leTaux = 1 - (parseInt(sessionStorage.getItem('hotspot-to-review'),10) / hotspot_total);
    _note = calcul_note_hotspot(leTaux);
  }

  i = '<span>' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
  $('#note-hotspot').html(i);
  });
}

/**
* description
* Fonction de remplissage du tableau avec les infos hotspot owasp A1-A10.
*/
function remplissage_hotspot_liste(id_maven) {
  if (id_maven === undefined) { return }

  const data={'maven_key': id_maven };
  const options = {
    url: 'http://localhost:8000/api/peinture/owasp/hotspot/liste', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(options).then((r) => {
  let i, leTaux=1, _note=['badge-vert1', 'A'], espace="";
  const hotspot_total=parseInt(sessionStorage.getItem('hotspot-total'),10);
  console.log('hotspot_total: ', hotspot_total);
  console.log("r :", r);
  const html_01='</span> <span class="badge ';

  if ( hotspot_total !==0 )
  {
    //A1
    leTaux = 1 - (parseInt(r.menace_a1,10)/hotspot_total);
    console.log('leTaux: ', leTaux);
    _note = calcul_note_hotspot(leTaux);
    console.log('_note: ', _note);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace=" ";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a1)+'</span><span class="stat-note">' + espace + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h1').html(i);

    //A2
    leTaux = 1 - (parseInt(r.menace_a,10)/ hotspot_total);
    console.log('leTaux: ', leTaux);

    _note = calcul_note_hotspot(leTaux);
    console.log('_note: ', _note);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace="";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a2) +'</span><span class="stat-note">' + espace + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    console.log('i: ', i);

    $('#h2').html(i);

    //A3
    leTaux = 1 - (r.menace_a3/ hotspot_total);
    _note = calcul_note_hotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace="";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+ new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a3) +'</span><span class="stat-note">' + espace + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h3').html(i);

    //A4
    leTaux = 1 - (r.menace_a4/ hotspot_total);
    _note = calcul_note_hotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace="";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a4) +'</span><span class="stat-note">' + espace + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h4').html(i);

    //A5
    leTaux = 1 - (r.menace_a5/ hotspot_total);
    _note = calcul_note_hotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace="";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a5) +'</span><span class="stat-note">' + espace + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h5').html(i);

    //A6
    leTaux = 1 - (r.menace_a6/ hotspot_total);
    _note = calcul_note_hotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace="";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a6) +'</span><span class="stat-note">' + espace + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h6').html(i);

    //A7
    leTaux = 1 - (r.menace_a7 / hotspot_total);
    _note = calcul_note_hotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace="";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a7) +'</span><span class="stat-note">'+ espace + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h7').html(i);

    //A8
    leTaux = 1 - (r.menace_a8/ hotspot_total);
    _note = calcul_note_hotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace="";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a8) +'</span><span class="stat-note">' + espace + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h8').html(i);

    //A9
    leTaux = 1 - (r.menace_a9/ hotspot_total);
    _note = calcul_note_hotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace="";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a9) +'</span><span class="stat-note">' + espace +  new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h9').html(i);

    //A10
    leTaux = 1 - (r.menace_a10/ hotspot_total);
    _note = calcul_note_hotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) { espace="&nbsp;&nbsp;&nbsp;"} else {espace="";}
    if ( leTaux*100===100) { espace="&nbsp;"}
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.menace_a10) +'</span><span class="stat-note">' + espace + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h10').html(i);
   }
  else
  {
    i = '<span class="stat-note">'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0) +'</span><span class="stat-note">&nbsp;' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(leTaux) + html_01 + _note[0] + '">' + _note[1] + '</span>';
    $('#h1').html(i);
    $('#h2').html(i);
    $('#h3').html(i);
    $('#h4').html(i);
    $('#h5').html(i);
    $('#h6').html(i);
    $('#h7').html(i);
    $('#h8').html(i);
    $('#h9').html(i);
    $('#h10').html(i);
  }

  });
}

/*
 * description
 * Affiche le tableau du détails des hotspots
*/
function remplissage_hotspot_details(id_maven) {
  if (id_maven === undefined) { return }

  const data={'maven_key': id_maven };
  const options = {
    url: 'http://localhost:8000/api/peinture/owasp/hotspot/details', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(options).then((r) => {

    let numero=0, mon_numero, ligne, c, frontend=0, backend=0, batch=0;
    let _a, _b, _c, too, total_a_b_c, zero='', bc;
    const serveur=$('#js-serveur').data('serveur');

    if (r.details==="vide") {
        // On met ajour la répartition par module
        _a = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0) + '</span> <span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(0) + '</span>';
        _b = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0) + '</span> <span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(0) + '</span>';
        _c = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0) + '</span> <span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(0) + '</span>';
        $('#frontend').html(_a)
        $('#backend').html(_b)
        $('#batch').html(_c)

        // On ajoute une ligne dans le tableau
        ligne = '<tr class="text-center"><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>Pas de failles</td></tr>';
        $('#tbody').html(ligne);
      }

    else
    {
      // On efface le tableau et on ajoute les lignes
      // On calcul l'impact sur les modules
      $('#tbody').html("");
      for ( let detail of r.details)
        {
        numero++;

        if (numero < 10) { mon_numero = '0' + numero } else { mon_numero = numero }
        if (detail.severity === 'LOW') { c = 'severity-jaune'; }
        if (detail.severity === 'MEDIUM') { c = 'severity-orange'; }
        if (detail.severity === 'HIGH') { c = 'severity-rouge'; }

        if (detail.frontend === 1) {frontend++;}
        if (detail.backend === 1) {backend++;}
        if (detail.batch === 1) {batch++;}

        ligne = '<tr>';
        ligne += '<td class="stat-note">' + mon_numero + '</td>';
        ligne += '<td><a href="' + serveur + '/coding_rules?open=' + detail.rule + '&q=' + detail.rule + '">' + detail.rule + '</a></td>';
        ligne += '<td class="' + c + '">' + detail.severity + '</td>';
        ligne += '<td class="component">' + detail.file + '</td>';
        ligne += '<td>' + detail.line + '</td>';
        ligne += '<td>' + detail.message + '</td>';
        ligne += '<td>' + detail.status + '</td>';
        ligne += '</tr>';
        $('#tbody').append(ligne);
      }

    // Met à jour la répartition par module
    total_a_b_c=parseInt(frontend+backend+batch,10);

    if ((frontend <10)) {zero="00"}
    if (frontend >9 && frontend <100) {zero="0"}

    // Calcul pour le frontend
    too=(frontend/total_a_b_c);
    if (frontend <10) {zero="00"}
    if (frontend >9 && frontend <100) {zero="0"}
    if (too*100 <30) { bc='module-vert'}
    if (too*100 >29 && too*100 <70) { bc='module-orange'}
    if (too*100 >69) { bc='module-rouge'}
    _a = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(too) + '</span> <span class="box '+bc+' stat-note">'+ zero + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(frontend) + '</span>';

    // Calcul pour le backend
    too=(backend/total_a_b_c);
    if (backend<10) {zero="00"}
    if (backend>9 && backend<100) {zero="0"}
    if (too*100 <30) { bc='module-vert'}
    if (too*100 >29 && too*100 <70) { bc='module-orange'}
    if (too*100 >69) { bc='module-rouge'}
    _b = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(too) + '</span> <span class="box '+bc+' stat-note">'+zero+ +new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(backend) + '</span>';

    // Calcul pour le backend
    too=(batch/total_a_b_c);
    if (batch<10) {zero="00"}
    if (batch>9 && batch<100) {zero="0"}
    if (too*100 <30) { bc='module-vert'}
    if (too*100 >29 && too*100 <70) { bc='module-orange'}
    if (too*100 >69) { bc='module-rouge'}
    _c = '<span class="stat-note">' + new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(too) + '</span> <span class="box '+bc+' stat-note">'+zero+ new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(batch) + '</span>';

    $('#frontend').html(_a)
    $('#backend').html(_b)
    $('#batch').html(_c)
  }
 });
}

function remplissage_details_hotspot_owasp(id_maven, menace, titre) {
  if (id_maven === undefined) { return }

  const data={'maven_key': id_maven, 'menace': menace };
  const options = {
    url: 'http://localhost:8000/api/peinture/owasp/hotspot/severity', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(options).then((r) => {
  $('.details-titre').html(liste_owasp2017[titre]);
  $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.high.total));
  $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.medium.total));
  $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.low.total));
  });
}

$('.js-details').on('click', function () {
  const id = $(this).attr('id').split('-');
  const _key=localStorage.getItem('projet');
  if (id[1] === 'a1') { remplissage_details_hotspot_owasp(_key, 'a1',1) }
  if (id[1] === 'a2') { remplissage_details_hotspot_owasp(_key, 'a2',2) }
  if (id[1] === 'a3') { remplissage_details_hotspot_owasp(_key, 'a3',3) }
  if (id[1] === 'a4') { remplissage_details_hotspot_owasp(_key, 'a4',4) }
  if (id[1] === 'a5') { remplissage_details_hotspot_owasp(_key, 'a5',5) }
  if (id[1] === 'a6') { remplissage_details_hotspot_owasp(_key, 'a6',6) }
  if (id[1] === 'a7') { remplissage_details_hotspot_owasp(_key, 'a7',7) }
  if (id[1] === 'a8') { remplissage_details_hotspot_owasp(_key, 'a8',8) }
  if (id[1] === 'a9') { remplissage_details_hotspot_owasp(_key, 'a9',9) }
  if (id[1] === 'a10') { remplissage_details_hotspot_owasp(_key, 'a10',10) }
  $('#details').foundation('open');
})

/*************** Main du programme **************/
// On récupère la clé du projet
const key=localStorage.getItem('projet');
const projet=key.split(":");
// On met à jour la page
$('#js-application').html(projet[1]);

remplissage_owasp_info(key);
remplissage_hotspot_info(key);
remplissage_hotspot_liste(key);
remplissage_hotspot_details(key);
