/*
 * Copyright (c) 2021-2022.
 * Laurent HADJADJ <laurent_h@me.com>.
 * Licensed Creative Common  CC-BY-NC-SA 4.0.
 * Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 * http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

import '../css/home.css';

// Intégration de jquery
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

import browserUpdate from 'browser-update';

console.log('Home : Chargement de webpack !');

// Vérification du navigateur
const configuration_options = {
  required: { i: 11, e: -3, c: -3, f: -3, o: -3, s: -3, },
  insecure: true,
  unsupported: true,
  api: 2021.10,
  reminder: 24
};

// Chargement de browser update
browserUpdate([configuration_options]);

const contentType = 'application/json; charset=utf-8';

const date_options = {
  year: "numeric", month: "numeric", day: "numeric",
  hour: "numeric", minute: "numeric", second: "numeric",
  hour12: false
};


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
 * Vérifie si le serveur sonarqube est UP
 */
function sonar_is_up() {
  const options = {
    url: 'http://localhost:8000/api/status', type: 'GET', dataType: 'json',
    contentType: contentType  }
  return $.ajax(options)
    .then(function (data) {
      log(' - INFO : État du serveur sonarqube : ' + data.status);
      log(' - INFO : Version '+ data.version);
    })
    .catch(function (message) {
      log(' - ERREUR : État du serveur sonarqube : DOWN (' + message.statusText + ')'); return (message.statusText);
    }
    )
  }

/**
 * description
 * Récupération de la liste des projets.
 */
function liste_projet_ajout() {
  const options = {
    url: 'http://localhost:8000/api/liste_projet/ajout', type: 'GET', dataType: 'json',
    contentType: contentType }

  return $.ajax(options)
    .then(function (t) {
      log(' - INFO : Nombre de projet disponible : ' + t.nombreProjet);
      $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(t.nombreProjet));
      })
}

/**
 * description
 * Date de la dernière mise à jour.
 */
function liste_projet_date(){
  const options = {
    url: 'http://localhost:8000/api/liste_projet/date', type: 'GET', dataType: 'json',
    contentType: contentType }
  return $.ajax(options)
    .then((data)=> {
      if (data.nombreProjet===0 && data.dateCreation==0){
        $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(0));
        log(' - ERROR : Vous devez importer la liste des projets !!!');
        }
      else
        {
        $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(data.nombreProjet));
          log(' - INFO : Nombre de projet disponible : ' + data.nombreProjet);
          log(' - INFO : Derrnière mise à jour : ' + data.dateCreation);
        }
    })
}

/**
 * description
 * Affiche le nombre de profil.
 */
 function affiche_nombre_profil() {
  const options = {
    url: 'http://localhost:8000/api/quality', type: 'GET', dataType: 'json',
    contentType: contentType
  }
  return $.ajax(options)
    .then(function (r) {
         $('#js-nombre-profil').html('<span class="stat">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.nombre) + '</span>')
         if (r.nombre===0){
           return $.ajax({
           url:'http://localhost:8000/api/quality/profiles',
           type: 'GET', dataType: 'json', contentType: contentType})
          }
      });
 }

/**
 * description
 * Affiche le nombre de profil.
 */
 // eslint-disable-next-line no-unused-vars
 function affiche_projet_visibility() {
  const options = {
    url: 'http://localhost:8000/api/visibility', type: 'GET', dataType: 'json',
    contentType: contentType
  }
  return $.ajax(options)
    .then(function (r) {
         $('#js-projet-public').html('<span class="stat">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.nombre) + '</span>')
         $('#js-projet-private').html('<span class="stat">' + new Intl.NumberFormat('fr-FR', { style: 'decimal', }).format(r.nombre) + '</span>')
      });
 }


/********* Main du programme *******/
/**
 * description
 * Événement : on recharge la liste des projets.
 */
$('.refresh-bd').on('click', function () {
  sonar_is_up()
    .then(function (result) {
      if (result != 'error') {
        liste_projet_ajout();
      }
    });
});

/**                               **/
// On dit bonjour.
start_spinner();
dit_bonjour();
// On récupère la date de la dernière analyse
liste_projet_date()
// On récupére le nomnbre de profil
affiche_nombre_profil();
// On affiche le nombre de projet privée et public
// Attention, il faut avoir les droits d'administration !!!
// La fonction est désactivée par défaut.
// affiche_projet_visibility();
stop_spinner();
