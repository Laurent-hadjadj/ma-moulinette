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
import './app-authentification-details.js';

/** On importe les paramètres serveur */
import {serveur} from './properties.js';

/** On importe les constantes */
import { dateOptions, contentType } from './constante.js';

/**
  * [Description for log]
  * Affiche la log.
  *
  * @param string txt
  * @return void
  *
  */
const log=function(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += `${new Intl.DateTimeFormat('default',
  dateOptions).format(new Date())} ${txt}\n`;
};

/**
  * [Description for sonarIsUp]
  * Vérifie si le serveur sonarqube est UP
  * Affiche la version du seveur
  *
  * @return [type]
  *
  */
const sonarIsUp=async function() {
  const data={'mode': 'null' };
  const options = {
    url: `${serveur()}/api/status`, type: 'POST',
    dataType: 'json',  data: JSON.stringify(data), contentType };
  try
  {
    return await $.ajax(options);
  } catch (message)
  {
    $('#callout-accueil-message').removeClass('hide success alert primary secondary');
    $('#callout-accueil-message').addClass('alert');
    $('#js-reference-information').html('<strong>[Accueil]</strong>');
    $('#js-message-information').html(`État du serveur sonarqube : DOWN (${ message.statusText })`);
    return ['504', message.statusText];
  }
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
    url: `${serveur()}/api/projet/liste`, type: 'POST', dataType: 'json', contentType };
    return new Promise(resolve => {
      $.ajax(options).then(t => {
        if (t.type==='alert'){
          $('#callout-accueil-message').removeClass('hide success alert primary secondary');
          $('#callout-accueil-message').addClass(t.type);
          $('#js-reference-information').html(t.reference);
          $('#js-message-information').html(t.message);
          return;
        } else {
          /** On affiche le nombre de projet */
          $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nombre));
          /** On efface le plus|moins */
          $('#js-moins, #js-plus').html('');
          /** On ferme la callout */
          $('#info-close').trigger('click');

          /** On met à jour le nombre de projet public */
          $('#js-public').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.public));
          /** On met à jour le nombre de projet privée */
          if (isNaN(t.private)) {
            $('#js-private').html('-');
            } else {
              $('#js-private').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.private));
            }

          /** On affiche un message à l'utilisateur */
          $('#callout-accueil-message').removeClass('hide success alert primary secondary');
          $('#callout-accueil-message').addClass(t.type);
          $('#js-reference-information').html(t.reference);
          $('#js-message-information').html(t.message);
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
  console.log('je rentre !!!');
  await miseAJourListe();
};


/********* Evenement *******/

/**
 * description
 * Événement : on recharge la liste des projets.
 */
$('.refresh-bd').on('click', ()=> {
  sonarIsUp()
    .then((result)=> {
      if (result[0]===504 || result[0]===400 || result[1] === 'Internal Server Error') {
        if (result[0]===400) {
          $('#callout-accueil-message').removeClass('hide success alert primary secondary');
          $('#callout-accueil-message').addClass('alert');
          $('#js-reference-information').html('<strong>[Accueil]</strong>');
          $('#js-message-information').html(`La requête est incorrecte (Erreur 400).`);
        }
        return;
      }
      /** si le serveur est disponible */

      miseAJourListeAsync();
    });
});

/**
 * description
 * Événement : On ouvre le tableau de suivi pour le projet.
 */
$('.suivi-svg').on('click', function(e) {
  const id = e.currentTarget.id;

  /* On récupère la clé maven du projet. */
  const element = document.getElementById(id);
  const mavenKey=element.dataset.mavenkey;
  if (mavenKey!==''){
    window.location.href='/suivi?mavenKey='+mavenKey;
    } else {
    log(' - ERROR - La clé n\'est pas correcte !! !');
  }
});
