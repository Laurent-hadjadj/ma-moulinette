/*
 * Copyright (c) 2021-2022.
 * Laurent HADJADJ <laurent_h@me.com>.
 * Licensed Creative Common  CC-BY-NC-SA 4.0.
 * Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 * http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

// Intégration de jquery
import $ from 'jquery';

/**
 * description
 * Affiche la log.
 */
function log(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += new Intl.DateTimeFormat('default', date_options).format(new Date()) + txt + '\n';
}

const date_options = {year: "numeric", month: "numeric", day: "numeric", hour: "numeric", minute: "numeric", second: "numeric", hour12: false };

const contentType='application/json; charset=utf-8';

/**
 * description
 * Fonction de remplissage des tableaux.
 */
export function remplissage(maven_key) {
  const data = { maven_key: maven_key };

  //On récupère les informations sur les versions, et le dernier audit.
  const optionsInfo = {
    url: 'http://localhost:8000/api/peinture/projet/version', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(optionsInfo).then((t) => {
    /* On regarde le code http de retour.
     * Si la requête à un résultat, il est toujours égal à 200
     * sinon 406 pour signaler que le projet n'a pas encore été analysé.
     */
    if (t[0] === '406')
      {
        log(' - ERROR : Récupération de la version.')
        log(t.message);
        return;
      }

    let release=0, snapshot=0;
    const nom = maven_key.split(':');
    $('#nom-projet').html(nom[1]);
    $('#clef-projet').html(maven_key);
    if (t.version.RELEASE !== undefined) { release = t.version.RELEASE.total; }
    if (t.version.SNAPSHOT !== undefined) { snapshot = t.version.SNAPSHOT.total; } else { snapshot= '0'; }
    $('#version-release').html(release);
    $('#version-snapshot').html(snapshot);

    const version = document.getElementById('version-autre');
    version.dataset.label = JSON.stringify(t.label);
    version.dataset.dataset = JSON.stringify(t.dataset);
    $('#version').html(t.projet);
    $('#date-version').html(new Intl.DateTimeFormat('default', date_options).format(new Date(t.date)));

    // Historique
    const t1 = document.getElementById('version-release');
    const t2 = document.getElementById('version-snaphot');
    const t3 = document.getElementById('date-version');
    t1.dataset.release=(release);
    t2.dataset.snapshot=(snapshot);
    t3.dataset.date_version=(t.date);
  })

  //On récupère les exclusions noSonar
  const optionsNoSonar = {
    url: 'http://localhost:8000/api/peinture/projet/nosonar/details', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(optionsNoSonar).then((t) => {
    $('#suppress-warning').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.s1309));
    $('#no-sonar').html( new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.nosonar));
    const t1 = document.getElementById('suppress-warning');
    const t2 = document.getElementById('no-sonar');
    t1.dataset.s1309=(t.s1309);
    t2.dataset.nosonar=(t.nosonar);
  });

  //On récupère les informations du projet : lignes, couverture fonctionnelle, duplication, tests unitaires et le nombre de défaut.
  const optionsProjet = {
    url: 'http://localhost:8000/api/peinture/projet/information', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(optionsProjet).then((t) => {
    if (t[0] === '406')
    {
      log(' - ERROR : Récupération des informations.')
      log(t.message);
      return;
    }
    $('#nombre-ligne').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.lines));
    $('#nombre-ligne-de-code').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.ncloc));
    $('#couverture').html(new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.coverage / 100));
    $('#duplication').html(new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.duplication / 100));
    $('#tests-unitaires').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.tests));
    $('#nombre-defaut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.issues));

    //Historique
    const t1 = document.getElementById('nombre-ligne');
    const t2 = document.getElementById('couverture');
    const t3 = document.getElementById('duplication');
    const t4 = document.getElementById('tests-unitaires');
    const t5 = document.getElementById('nombre-defaut');
    t1.dataset.nombre_ligne=(t.lines);
    t2.dataset.coverage=(t.coverage);
    t3.dataset.duplication=(t.duplication);
    t4.dataset.tests_unitaires=(t.tests);
    t5.dataset.nombre_defaut=(t.issues);
  });

  //On récupère les informations sur la dette technique et les anomalies.
  const optionsAnomalie = {
    url: 'http://localhost:8000/api/peinture/projet/anomalie', type: 'GET', dataType: 'json', data: data, contentType: contentType
   }

  $.ajax(optionsAnomalie).then((t) => {
    if (t[0] === '406') {
      log(' - ERROR : Récupération des anomalies.')
      log(t.message);
      return;
    }

    /* Dette technique */
    $('#dette').html(t.dette);
    $('#js-dette-reliability').html(t.dette_reliability);
    $('#js-dette-vulnerability').html(t.dette_vulnerability);
    $('#js-dette-code-smell').html(t.dette_code_smell);

    // Historique
    const t25 = document.getElementById('js-dette');
    const t26 = document.getElementById('js-dette-reliability');
    const t27 = document.getElementById('js-dette-vulnerability');
    const t28 = document.getElementById('js-dette-code-smell');
    t25.dataset.dette_minute=t.dette_minute;
    t26.dataset.dette_reliability_minute=t.dette_reliability_minute;
    t27.dataset.dette_vulnerability_minute=t.dette_vulnerability_minute;
    t28.dataset.dette_code_smell_minute=t.dette_code_smell_minute;

    /* Nombre d'anomalie */
    $('#nombre-bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.bug));
    $('#nombre-vulnerabilite').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.vulnerability));
    $('#nombre-mauvaise-pratique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.code_smell));
    if (t.code_smell===10000) { $('#nombre-mauvaise-pratique').css('color', '#771404');}

    // Historique
    const t1 = document.getElementById('nombre-bug');
    const t2 = document.getElementById('nombre-vulnerabilite');
    const t3 = document.getElementById('nombre-mauvaise-pratique');
    t1.dataset.nombre_bug=(t.bug);
    t2.dataset.nombre_vulnerabilite=(t.vulnerability);
    t3.dataset.nombre_code_smell=(t.code_smell);

    /* Répartition modules*/
    let total_module, i1, i2, i3, p1, p2, p3, e1='', e2='', e3='';
    total_module=parseInt(t.frontend+t.backend+t.batch,10);

    if (total_module !==0) {
      if (t.frontend!==0) {
        p1=t.frontend/total_module;
        if (p1*100>10 && p1*100<100) { e1='<span style="color:#fff;">0</span>'}
        if (p1*100<10) { e1='<span style="color:#fff;">00</span>'}
        i1='<span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.frontend)+'</span> '+e1+'<span>'+new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.frontend/total_module);
      } else
        { i1='<span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0)+'</span>';}
      $('#nombre-frontend').html(i1);

      if (t.backend!==0) {
        p2=t.backend/total_module;
        if (p2*100>10 && p2*100<100) { e2='<span style="color:#fff;">0</span>'}
        if (p2*100<10) { e2='<span style="color:#fff;">00</span>'}
        i2='<span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.backend)+'</span> '+e2+'<span>'+new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.backend/total_module);
      } else { i2='<span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0)+'</span>';}
      $('#nombre-backend').html(i2);

      if (t.batch!==0) {
        p3=t.batch/total_module;
        if (p3*100>10 && p3*100<100) { e3='<span style="color:#fff;">0</span>'}
        if (p3*100<10) { e3='<span style="color:#fff;">00</span>'}
        i3='<span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.batch)+'</span> '+e3+' <span>'+new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.batch/total_module);
      } else { i3='<span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0)+'</span>';}
      $('#nombre-batch').html(i3);

      // Historique
      const t4 = document.getElementById('nombre-frontend');
      const t5 = document.getElementById('nombre-backend');
      const t6 = document.getElementById('nombre-batch');
      t4.dataset.nombre_frontend=t.frontend;
      t5.dataset.nombre_backend=t.backend;
      t6.dataset.nombre_batch=t.batch;
      }
    else {
          $('#nombre-frontend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0));
          $('#nombre-backend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0));
          $('#nombre-batch').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0));
          const t4 = document.getElementById('nombre-frontend');
          const t5 = document.getElementById('nombre-backend');
          const t6 = document.getElementById('nombre-batch');
          t4.dataset.nombre_frontend=0;
          t5.dataset.nombre_backend=0;
          t6.dataset.nombre_batch=0;
          }

    /* Répartition des anomalies par sévérité */
    $('#nombre-anomalie-bloquante').html(t.blocker);
    $('#nombre-anomalie-critique').html(t.critical);
    $('#nombre-anomalie-info').html(t.info);
    $('#nombre-anomalie-majeure').html(t.major);
    $('#nombre-anomalie-mineure').html(t.minor);

    const t16 = document.getElementById('nombre-anomalie-bloquante');
    const t17 = document.getElementById('nombre-anomalie-critique');
    const t18 = document.getElementById('nombre-anomalie-info');
    const t19 = document.getElementById('nombre-anomalie-majeure');
    const t20 = document.getElementById('nombre-anomalie-mineure');
    t16.dataset.nombre_anomalie_bloquante=t.blocker;
    t17.dataset.nombre_anomalie_critique=t.critical;
    t18.dataset.nombre_anomalie_info=t.info;
    t19.dataset.nombre_anomalie_majeure=t.major;
    t20.dataset.nombre_anomalie_mineure=t.minor;

    //On récupère les notes sonarqube pour la version courante
    let t_notes = ['', 'A', 'B', 'C', 'D', 'E'], couleur1, couleur2, couleur3 = '';
    if (t.note_reliability === 1 ) { couleur1 = 'note-vert1'; }
    if (t.note_security === 1) { couleur2 = 'note-vert1'; }
    if (t.note_sqale === 1) { couleur3 = 'note-vert1'; }

    if (t.note_reliability === 2) { couleur1 = 'note-vert2'; }
    if (t.note_security === 2) { couleur2 = 'note-vert2'; }
    if (t.note_sqale === 2) { couleur3 = 'note-vert2'; }

    if (t.note_reliability === 3) { couleur1 = 'note-jaune'; }
    if (t.note_security === 3) { couleur2 = 'note-jaune'; }
    if (t.note_sqale === 3) { couleur3 = 'note-jaune'; }

    if (t.note_reliability === 4) { couleur1 = 'note-orange'; }
    if (t.note_security === 4) { couleur2 = 'note-orange'; }
    if (t.note_sqale === 4) { couleur3 = 'note-orange'; }

    if (t.note_reliability === 5) { couleur1 = 'note-rouge'; }
    if (t.note_security === 5) { couleur2 = 'note-rouge'; }
    if (t.note_sqale === 5) { couleur3 = 'note-rouge'; }

    const note_reliability = t_notes[parseInt(t.note_reliability,10)];
    const note_security = t_notes[parseInt(t.note_security,10)];
    const note_sqale = t_notes[parseInt(t.note_sqale,10)];

    $('#note-reliability').html('<span class="' + couleur1 + '">' + note_reliability + '</span>');
    $('#note-security').html('<span class="' + couleur2 + '">' + note_security + '</span>');
    $('#note-sqale').html('<span class="' + couleur3 + '">' + note_sqale + '</span>');

   });

  //On récupère les hotspot.
  const optionsHotspots = {
    url: 'http://localhost:8000/api/peinture/projet/hotspots', type: 'GET', dataType: 'json', data: data, contentType: contentType
  }

  $.ajax(optionsHotspots).then((t) => {
    let couleur='';

    if (t[0] === '406') {
      log(' - ERROR : Récupération des anomalies.')
      log(t.message);
      return;
    }

    if (t.note === 'E') { couleur = 'note-rouge'; }
    if (t.note === 'D') { couleur = 'note-orange'; }
    if (t.note === 'C') { couleur = 'note-jaune'; }
    if (t.note === 'B') { couleur = 'note-vert2'; }
    if (t.note === 'A') { couleur = 'note-vert1'; }

    $('#note-hotspot').html('<span class="' + couleur + '">' + t.note + '</span>');
  });
}
