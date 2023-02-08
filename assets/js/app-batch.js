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

/** Import des dépendances */
import '../css/batch.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

/* On importe les paramètres serveur. */
import {serveur} from './properties.js';

/** On importe les constatntes */
import { contentType, trois, cinqCent, mille } from './constante';

/** On gére l'affichage des jobs */
const jsAutomatique = '.js-automatique';
const automatique = '.automatique';
const jsManuel = '.js-manuel';
const manuel= '.manuel';


$(jsAutomatique).on('click', ()=> {
  if ($(jsAutomatique).hasClass('active')) {
    $(jsAutomatique).removeClass('active').addClass('bouton-automatique');
    $(automatique).hide(cinqCent);
  } else {
    $(jsAutomatique).removeClass('bouton-automatique').addClass('active');
    $(automatique).show(mille);
    if ($(jsManuel).hasClass('active')) {
      $(manuel).show(mille);
    } else {
      $(manuel).hide(cinqCent);
    }

  }
});

$(jsManuel).on('click', function() {
  /**
   * si on click et que le bouton a le statut actif
   * On retire le statut actif  et on ajoute le tag bouton-manuel
   */
  if ($(jsManuel).hasClass('active')) {
    $(jsManuel).removeClass('active').addClass('bouton-manuel');
    $(manuel).hide(cinqCent);
  } else {
    /**
     * J'active l'affichage des données pour les traitements manuels
     * On passe de bleu a orange.
     * On affiche les lignes manuels et
     * On garde les lignes automatiques si le bouton est orange
     */
    $(jsManuel).removeClass('bouton-manuel').addClass('active');
    $(manuel).show(mille);
    if ($(jsAutomatique).hasClass('active')) {
      $(automatique).show(mille);
    } else {
      $(automatique).hide(cinqCent);
    }
  }
});

/** On lance un job manuel - oui Monsieur !!! */
$('.i-am-human-svg').on('click', function() {
  /** literals  */
  const collecteAnimation='#collecte-animation';
  const collecteTexte='#collecte-texte';
  const infoBulle='#info-bulle';

  /** On desactive le spinner et on reset les messages */
  $(collecteAnimation).removeClass('sp-volume');
  $(collecteTexte).html('');
  $('#js-nom-job').html('');
  $('#js-non').removeClass('disable');

  /** On récupère l'élement cliqué depuis le DOM */
  //i-am-human-10
  const id=$(this).attr('id');
  const idTab = id.split('-');

  /** On récupère le nom du Job */
  const job=$(`#job-${idTab[trois]}`).text();
  $('#js-nom-job').html(job);

  /** On ouvre la fenêtre modal */
  $('#modal-traitement-manuel').foundation('open');

  /** on sort si on clique sur non */
  $('#js-non').on('click', function(){
    /** si le spinner tourne on desactive le bouton non */
    if (!$(collecteAnimation).hasClass('sp-volume')) {
      $(`#${id}`).removeClass('blink');
      $('#modal-traitement-manuel').foundation('close');
    }
  });

  $('#js-oui').on('click', function(){
    /** On dsactive le bouton non */
    $('#js-non').addClass('disable');
    /** clignote */
    $(`#${id}`).addClass('blink');
    $(collecteAnimation).addClass('sp-volume');
    $(collecteTexte).html(`Démarrage du traitement...`);

    const data = { job };
    const options = {
      url: `${serveur()}/traitement/pending`, type: 'GET',
      dataType: 'json', data, contentType};

    /** On vérifie que le traitement n'est pas en start le traitement */
    return new Promise(resolve => {
      $.ajax(options).then( t => {
        /** On met à jour la bulle info */
        if (t.execution==='start') {
          $(infoBulle).removeClass('bulle-info-vide').addClass('bulle-info-start');
          $(infoBulle).html('1');
          $('#info-bulle-tips').html('Traitement en cours');
          setTimeout(function(){
            $(collecteTexte).html('Collecte en cours, cours...');
          }, mille);
        }
        if (t.execution==='pending') {
          setTimeout(function(){
          $(collecteTexte).html('Il y a déjà un traitement en cours !');
          $(`#${id}`).removeClass('blink');
        }, mille);
      }
      /* on lance le traitement */
      batchManuel(idTab[trois], job);
      resolve();
      });
    });
  });
});

/**
 * [Description for batchManuel]
 *  Lance le batch manuel
 * @param string id
 * @param string job
 *
 * @return [type]
 *
 * Created at: 07/02/2023, 15:05:56 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const batchManuel = function(id, job){
  /** On lance le processus */
  const infoBulle='#info-bulle';
  const collecteTexte='#collecte-texte';
  const collecteAnimation='#collecte-animation';
  const data = { job };
  const options = {
    url: `${serveur()}/traitement/manuel`, type: 'GET',
    dataType: 'json', data, contentType};
  return new Promise(resolve => {
    $.ajax(options).then( t => {
      /** On met à jour la bulle info */
      if (t.execution==='end') {
          $(infoBulle).removeClass('bulle-info-start').addClass('bulle-info-end');
          $(infoBulle).html('1');
          $('#info-bulle-tips').html('Collecte terminée');
          $(`#i-am-human-${id}`).removeClass('blink');
          const resultat=`<span class="show-for-small-only"><strong>OK</strong></span>
                          <span class="show-for-medium"><strong>Succès</strong></span>`;
          $(`#resultat-${id}`).html(resultat);
          $(`#temps-execution-${id}`).html(t.temps);
          setTimeout(function(){
            $(collecteTexte).html('Collecte terminée...');
          }, mille);
        }
        $(collecteAnimation).removeClass('sp-volume');
      resolve();
    });
  });
};
