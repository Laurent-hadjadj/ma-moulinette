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
    console.log(t);
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
  const optionsAnomalies = {
    url: 'http://localhost:8000/api/peinture/projet/anomalies', type: 'GET', dataType: 'json', data: data, contentType: contentType
  }

  $.ajax(optionsAnomalies).then((t) => {
    if (t[0] == '406') {
      log(' - ERROR : Récupération des anomalies.')
      log(t.message);
      return;
    }

    /* Dette technique */
    $('#dette').html(t.dette);
    $('#dette-bug').html(t.dette_bug);
    $('#dette-vulnerability').html(t.dette_vulnerability);
    $('#dette-code-smell').html(t.dette_code_smell);

    /* Nombre d'anomalie */
    $('#nombre-bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.bug));
    $('#nombre-vulnerabilite').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.vulnerability));
    $('#nombre-mauvaise-pratique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.code_smell));

    /* Répartion des anomalies par sévérité */
    $('#bug-bloquante').html(t.bug_blocker);
    $('#bug-critique').html(t.bug_crtitcal);
    $('#bug-info').html(t.bug_info);
    $('#bug-majeure').html(t.bug_major);
    $('#bug-mineure').html(t.bug_minor);

    $('#vulnerabilite-bloquante').html(t.vulnerabilty_blocker);
    $('#vulnerabilite-critique').html(t.vulnerabilty_crtitcal);
    $('#vulnerabilite-info').html(t.vulnerabilty_info);
    $('#vulnerabilite-majeure').html(t.vulnerabilty_major);
    $('#vulnerabilite-mineure').html(t.vulnerabilty_minor);

    $('#mauvaise-pratique-bloquante').html(t.code_smell_blocker);
    $('#mauvaise-pratique-critique').html(t.code_smell_crtitcal);
    $('#mauvaise-pratique-info').html(t.code_smell_info);
    $('#mauvaise-pratique-majeure').html(t.code_smell_major);
    $('#mauvaise-pratique-mineure').html(t.code_smell_minor);

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
