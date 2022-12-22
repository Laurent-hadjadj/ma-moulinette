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

import '../css/home.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

/** On importe les paramètres serveur */
import {serveur} from './properties.js';

const contentType = 'application/json; charset=utf-8';

const dateOptions = {
  year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric', hour12: false };

/**
  * [Description for log]
  * Affiche la log.
  *
  * @param mixed txt
  * @return [type]
  *
  */
const log=function(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += `${new Intl.DateTimeFormat('default',
  dateOptions).format(new Date())} ${txt}\n`;
};

/**
  * [Description for ditBonjour]
  * Initialisation de la log.
  * @return [type]
  *
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
  * [Description for sonarIsUp]
  * Vérifie si le serveur sonarqube est UP
  * Affiche la version du seveur
  *
  * @return [type]
  *
  */
const sonarIsUp=function() {
  const options = {
    url: `${serveur()}/api/status`, type: 'GET',
    dataType: 'json',  contentType };
  return $.ajax(options)
    .then( data => {
      log(` - INFO : État du serveur sonarqube : ${data.status}`);
      log(` - INFO : Version ${data.version}`);
    })
    .catch( message => {
      log(` - ERREUR : État du serveur sonarqube : DOWN (${message.statusText})`);
      return (message.statusText);
    }
    );
};

/**
  * [Description for miseAJourListe]
  * Récupération de la liste des projets.
  *
  * @return [type]
  *
  */
const miseAJourListe=function() {
  const options = {
    url: `${serveur()}/api/projet/liste`, type: 'GET',
    dataType: 'json', contentType };

    return new Promise(resolve => {
      $.ajax(options).then(t => {
        log(` - INFO : Nombre de projet disponible : ${t.nombre}`);
        $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nombre));
        /** On efface le plus|moins */
        $('#js-moins, #js-plus').html('');
        /** On ferme la callout */
        $('#info-close').trigger('click');
        resolve();
      });
    });
};

/**
  * [Description for miseAJourListeProjet]
  *
  * @return [type]
  *
  */
const miseAJourListeProjet=function() {
  const options = {
    url: `${serveur()}/api/tags`, type: 'GET',
    dataType: 'json', contentType };
    return new Promise(resolve => {
      $.ajax(options).then(t => {
        log(` - INFO : ${t.message}`);
        $('#js-public').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.public));
        if (isNaN(t.private)) {
          $('#js-private').html('-');
          } else {
            $('#js-private').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.private));
          }
        resolve();
      });
    });
};


/**
  * [Description for miseAJourListeAsync]
  * Fonctions asynchronnes (liste et profils)
  * @return [type]
  *
  */
const miseAJourListeAsync= async function() {
  await miseAJourListe();
};


/**
  * [Description for miseAJourListeProjetAsync]
  * Fonctions asynchronnes (Mise à jour de la liste de prjet et des tags)
  * @return [type]
  *
  */
const miseAJourListeProjetAsync= async function() {
  await miseAJourListeProjet();
};

/********* Evenement *******/

/**
 * description
 * Événement : on recharge la liste des projets.
 */
$('.refresh-bd').on('click', function() {
  sonarIsUp()
    .then(function (result) {
      if (result !== 'error') {
        miseAJourListeAsync();
      }
    });
});

/**
 * description
 * Événement : On ouvre le tableau de suivi pour le projet.
 */
$('.suivi-svg').on('click', function(e) {
  const id = e.target.id;

  /* On récupère la clé maven du projet. */
  const element = document.getElementById(id);
  const mavenKey=element.dataset.mavenkey;
  if (mavenKey!==''){
    window.location.href='/suivi?mavenKey='+mavenKey;
    } else {
    log(' - ERROR - La clé n\'est pas correcte !! !');
  }
});

/** ******************** main *************************** */

// On dit bonjour.
ditBonjour();
miseAJourListeProjetAsync();
