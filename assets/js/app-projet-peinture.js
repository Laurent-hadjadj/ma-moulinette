/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

/**
 * description
 * Affiche la log.
 */
function log(txt) {
  let textarea = document.getElementById('log');
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
    if (t[0] == '406')
      {
        log(' - ERROR : Récupération de la version.')
        log(t.message);
        return;
      }

    let release=0, snapshot=0;
    let nom = maven_key.split(':');
    $('#nom-projet').html(nom[1]);
    $('#clef-projet').html(maven_key);
    if (t.version.RELEASE != undefined) { release = t.version.RELEASE.total; }
    if (t.version.SNAPSHOT != undefined) { snapshot = t.version.SNAPSHOT.total; } else { snapshot= '0'; }
    $('#version-release').html(release);
    $('#version-snapshot').html(snapshot);
    let version = document.getElementById('version-autre');
    version.dataset.label = JSON.stringify(t.label);
    version.dataset.dataset = JSON.stringify(t.dataset);
    $('#version').html(t.projet);
    $('#date-version').html(new Intl.DateTimeFormat('default', date_options).format(new Date(t.date)));
  })

  //On récupère les exclusions noSonar
  const optionsNoSonar = {
    url: 'http://localhost:8000/api/peinture/projet/nosonar/details', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(optionsNoSonar).then((t) => {
    $('#supress-warning').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.s1309));
    $('#no-sonar').html( new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.nosonar));
  })

  //On récupère les informations du projet : lignes, couverture fonctionnelle, duplication, tests unitaires et le nombre de défaut.
  const optionsProjet = {
    url: 'http://localhost:8000/api/peinture/projet/information', type: 'GET', dataType: 'json', data: data, contentType: contentType }

  $.ajax(optionsProjet).then((t) => {
    if (t[0] == '406')
    {
      log(' - ERROR : Récupération des informations.')
      log(t.message);
      return;
    }


    $('#nombre-ligne').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.lines));
    $('#couverture').html(new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.coverage / 100));
    $('#duplication').html(new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.duplication / 100));
    $('#tests-unitaires').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.tests));
    $('#nombre-defaut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.issues));
  });

  //On récupère les informations sur la dette technique et les anomalies.
  const optionsAnomalie = {
    url: 'http://localhost:8000/api/peinture/projet/anomalie', type: 'GET', dataType: 'json', data: data, contentType: contentType
   }

  $.ajax(optionsAnomalie).then((t) => {
    if (t[0] == '406') {
      log(' - ERROR : Récupération des anomalies.')
      log(t.message);
      return;
    }

    /* Dette technique */
    $('#dette').html(t.dette);
    $("#js-dette").data('dette', t.dette_minute);
    $('#js-dette-reliability').html(t.dette_reliability);
    $('#js-dette-vulnerability').html(t.dette_vulnerability);
    $('#js-dette-code-smell').html(t.dette_code_smell);

    $("#js-dette-reliability").data('dette-reliability', t.dette_reliability_minute);
    $("#js-dette-vulnerability").data('dette-vulnerability', t.dette_vulnerability_minute);
    $("#js-dette-code-smell").data('dette-code-smell', t.dette_code_smell_minute);

    /* Nombre d'anomalie */
    $('#nombre-bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.bug));
    $('#nombre-vulnerabilite').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.vulnerability));
    $('#nombre-mauvaise-pratique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.code_smell));
    if (t.code_smell==10000) { $('#nombre-mauvaise-pratique').css('color', '#771404');}

    /* Répartition modules*/
    let total_module, i1, i2, i3, p1, p2, p3, e1, e2, e3;
    total_module=parseInt(t.frontend+t.backend+t.batch);

    if (total_module !=0) {
      if (t.frontend!=0) {
        p1=t.frontend/total_module;
        if (p1*100>10 && p1*100<100) { e1='<span style="color:#fff;">0</span>'}
        if (p1*100<10) { e1='<span style="color:#fff;">00</span>'}
        i1='<span> </span></span><span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.frontend)+'</span> '+e1+'<span>'+new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.frontend/total_module);
      } else { i1='<span> </span></span><span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0)+'</span>';}
      $('#nombre-frontend').html(i1);

      if (t.backend!=0) {
        p2=t.backend/total_module;
        if (p2*100>10 && p2*100<100) { e2='<span style="color:#fff;">0</span>'}
        if (p2*100<10) { e2='<span style="color:#fff;">00</span>'}
        i2='<span> </span></span><span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.backend)+'</span> '+e2+'<span>'+new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.backend/total_module);
      } else { i2='<span> </span></span><span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0)+'</span>';}
      $('#nombre-backend').html(i2);

      if (t.batch!=0) {
        p3=t.batch/total_module;
        if (p3*100>10 && p3*100<100) { e3='<span style="color:#fff;">0</span>'}
        if (p3*100<10) { e3='<span style="color:#fff;">00</span>'}
        i3='<span> </span></span><span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.batch)+'</span> '+e3+' span>'+new Intl.NumberFormat('fr-FR', { style: 'percent', }).format(t.batch/total_module);
      } else { i3='<span> </span></span><span>'+new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0)+'</span>';}
      $('#nombre-batch').html(i3);
    }
    else {
            $('#nombre-frontend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0));
            $('#nombre-backend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0));
            $('#nombre-batch').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0));
          }

    /* Répartion des anomalies par sévérité */
    $('#nombre-anomalie-bloquante').html(t.blocker);
    $('#nombre-anomalie-critique').html(t.critical);
    $('#nombre-anomalie-info').html(t.info);
    $('#nombre-anomalie-majeure').html(t.major);
    $('#nombre-anomalie-mineure').html(t.minor);

    //On récupère les notes sonarqube pour la version courante
    let t_notes = ['', 'A', 'B', 'C', 'D', 'E'], couleur1, couleur2, couleur3 = '';
    if (t.note_reliability == '1' ) { couleur1 = 'note-vert1'; }
    if (t.note_security == '1') { couleur2 = 'note-vert1'; }
    if (t.note_sqale == '1') { couleur3 = 'note-vert1'; }

    if (t.note_reliability == '2') { couleur1 = 'note-vert2'; }
    if (t.note_security == '2') { couleur2 = 'note-vert2'; }
    if (t.note_sqale == '2') { couleur3 = 'note-vert2'; }

    if (t.note_reliability == '3') { couleur1 = 'note-jaune'; }
    if (t.note_security == '3') { couleur2 = 'note-jaune'; }
    if (t.note_sqale == '3') { couleur3 = 'note-jaune'; }

    if (t.note_reliability == '4') { couleur1 = 'note-orange'; }
    if (t.note_security == '4') { couleur2 = 'note-orange'; }
    if (t.note_sqale == '4') { couleur3 = 'note-orange'; }

    if (t.note_reliability == '5') { couleur1 = 'note-rouge'; }
    if (t.note_security == '5') { couleur2 = 'note-rouge'; }
    if (t.note_sqale == '5') { couleur3 = 'note-rouge'; }

    let note_reliability = t_notes[parseInt(t.note_reliability)];
    let note_security = t_notes[parseInt(t.note_security)];
    let note_sqale = t_notes[parseInt(t.note_sqale)];
    $('#note-reliability').html('<span class="' + couleur1 + '">' + note_reliability + '</span>');
    $('#note-security').html('<span class="' + couleur2 + '">' + note_security + '</span>');
    $('#note-sqale').html('<span class="' + couleur3 + '">' + note_sqale + '</span>');
   });

  //On récupère les hotspot.
  const optionsHotspots = {
    url: 'http://localhost:8000/api/peinture/projet/hotspots', type: 'GET', dataType: 'json', data: data, contentType: contentType
  }

  $.ajax(optionsHotspots).then((t) => {
    let couleur;

    if (t[0] == '406') {
      log(' - ERROR : Récupération des anomalies.')
      log(t.message);
      return;
    }

    if (t.note == 'E') { couleur = 'note-rouge'; }
    if (t.note == 'D') { couleur = 'note-orange'; }
    if (t.note == 'C') { couleur = 'note-jaune'; }
    if (t.note == 'B') { couleur = 'note-vert2'; }
    if (t.note == 'A') { couleur = 'note-vert1'; }

    $('#note-hotspot').html('<span class="' + couleur + '">' + t.note + '</span>');
  });
}
