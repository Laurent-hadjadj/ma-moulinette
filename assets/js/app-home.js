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
  year: 'numeric', month: 'numeric', day: 'numeric',
  hour: 'numeric', minute: 'numeric', second: 'numeric',
  hour12: false };

/**
 * description
 * Affiche la log.
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
 * description :
 * Vérifie si le serveur sonarqube est UP
 * Affiche la version du seveur
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
 * description :
 * Récupération de la liste des projets.
 *
 */
const miseAjourListe=function() {
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

// Fonctions asynchronnes (liste et profils)
const miseAjourListeAsync= async function() {
  await miseAjourListe();
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
        miseAjourListeAsync();
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
  if (mavenKey!=''){
    window.location.href='/suivi?mavenKey='+mavenKey;
    } else {
    log(' - ERROR - La clé n\'est pas correcte !! !');
  }
});

/** ******************** main *************************** */

// On dit bonjour.
ditBonjour();
