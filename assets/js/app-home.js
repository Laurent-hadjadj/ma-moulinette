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
import { http_200, http_400, http_401, http_404, http_406, http_500, contentType } from './constante.js';

/**
 * [Description for afficheMessage]
 * Mutualise l'affichage des messages d'erreur.
 *
 * @param mixed t
 *
 * @return void
 *
 * Created at: 14/03/2024 10:11:15 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const afficheMessage=function(t){
    $('#callout-accueil-message').removeClass('hide success alert warning primary secondary');
    $('#callout-accueil-message').addClass(t.type);
    $('#js-reference-information').html(t.reference);
    $('#js-message-information').html(t.message);
}

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
  let message=[];
  try
  {
    return await $.ajax(options);
  } catch (t) {
    message.type='alert';
    message.reference='<strong>[Accueil]</strong>';
    if (t.status===http_404){
      message.message='La requête est incorrecte (Erreur 404).'
    }
    if (t.status===http_500 || t.staus==='DOWN'){
      message.message='État du serveur sonarqube : DOWN.'
    }
    afficheMessage(message);
    return;
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
    url: `${serveur()}/api/home/projet`, type: 'POST',
          dataType: 'json', data: JSON.stringify(), contentType };
    return new Promise(resolve => {
      $.ajax(options).then(t => {
        if (t.code!==http_200){
          afficheMessage(t);
          return;
        } else {
          console.log(t)
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
          afficheMessage(t);
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


/********* Evenement *******/

/**
 * description
 * Événement : on recharge la liste des projets.
 */
$('.refresh-bd').on('click', ()=> {
  sonarIsUp()
    .then((t)=> {
      if (t.code === 504 || t.code === http_400 || t.message === 'Internal Server Error') {
        if (t.code=== http_404) {
          return;
        }
        afficheMessage(t);
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
    //log(' - ERROR - La clé n\'est pas correcte !! !');
  }
});
