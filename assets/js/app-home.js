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

// Intégration de jquery
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

// On importe les paramètres serveur
import {serveur} from "./properties.js";

console.log('Home : Chargement de webpack !');

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
 * On récupère le nombre de ligne de code total
 *
 */
const nloc=function(){
  const options = {
    url: `${serveur()}/api/system/info`, type: 'GET',
    dataType: 'json',  contentType };
  return new Promise((resolve) => {
    $.ajax(options).then( t => {
      console.log(t);
      resolve();
    });
  });
}

/**
 * description :
 * Récupération de la liste des projets.
 *
 */
const listeProjetAjout=function() {
  const options = {
    url: `${serveur()}/api/liste_projet/ajout`, type: 'GET',
    dataType: 'json', contentType };

    return new Promise((resolve) => {
      $.ajax(options).then(t => {
        log(` - INFO : Nombre de projet disponible : ${t.nombreProjet}`);
        $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nombreProjet));
        resolve();
      });
    });
};

/**
 * description :
 * Date de la dernière mise à jour.
 */
const listeProjetDate=function(){
  const options = {
    url: `${serveur()}/api/liste_projet/date`, type: 'GET',
    dataType: 'json', contentType };

    return new Promise((resolve) => {
      $.ajax(options).then(data=> {
      if (data.nombreProjet===0 && data.dateCreation===0){
        $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
        log(` - ERROR : Vous devez importer la liste des projets !!!`);
        } else {
        $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(data.nombreProjet));
          log(` - INFO : Nombre de projet disponible : ${data.nombreProjet}`);
          log(` - INFO : Derrnière mise à jour : ${data.dateCreation}`);
        }
      resolve();
    });
  });
};

/**
 * description
 * Affiche le nombre de profil.
 */
const afficheNombreProfil=function() {
  const options = {
    url: `${serveur()}/api/quality`, type: 'GET',
    dataType: 'json', contentType };

  return new Promise((resolve) => {
    $.ajax(options).then(r => {
        $('#js-nombre-profil').html(`<span class="stat">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.nombre)}</span>`);
        if (r.nombre===0){
          $.ajax({  url: `${serveur()}/api/quality/profiles`,
                    type: 'GET', dataType: 'json', contentType});
          }
      resolve();
      });
  });
};

/**
  * description
  * Affiche le nombre de profil.
  * @returns
  */
 // eslint-disable-next-line no-unused-vars
const  afficheProjetVisibility=function() {
  const options = {
          url: `${serveur()}/api/visibility`, type: 'GET',
          dataType: 'json', contentType
  };

  return $.ajax(options)
    .then(function (r) {
        $('#js-projet-public').html(`<span class="stat">
        ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.nombre)}</span>`);
        $('#js-projet-private').html(`<span class="stat">
        ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.nombre)}</span>`);
      });
};

/********* Main du programme *******/
/**
 * description
 * Événement : on recharge la liste des projets.
 */
$('.refresh-bd').on('click', function () {
  sonarIsUp()
    .then(function (result) {
      if (result !== 'error') {
        listeProjetAjout();
      }
    });
});

/**         main                    **/

// Fonctions asynchronnes de récupération des infos systèmes
async function informationSystemeAsync() {
  // Nombre de ligne de code total.
  await nloc();
}

// Fonctions asynchronnes (liste et profils)
async function informationsAsync() {
  // On récupère la date de la dernière analyse
  await listeProjetDate();

  // On récupére le nomnbre de profil
  await afficheNombreProfil();

  /**  On affiche le nombre de projet privée et public
  * Attention, il faut avoir les droits d'administration !!!
  * La fonction est désactivée par défaut.
  *
  * await afficheProjetVisibility();
 */

}

// On dit bonjour.
startSpinner();
ditBonjour();
// On appelle la fonction de récupèration des infos systèmes
informationSystemeAsync();
// On appelle la fonction de récupèration des infos sonar
informationsAsync();
stopSpinner();
