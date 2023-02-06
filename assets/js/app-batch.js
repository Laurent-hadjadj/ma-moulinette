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

/** On importe les constatntes */
import { trois, cinqCent, mille } from './constante';

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

  /** On desactive le spinner et on reset les messages */
  $(collecteAnimation).removeClass('sp-volume');
  $(collecteTexte).html('');
  $('#js-nom-job').html('');
  $('#js-non').removeClass('disable');

  /** On récupère l'élement cliquer depuis le DOM */
  //i-am-human-10
  const id = $(this).attr('id').split('-');

  /** On récupère le nom du Job */
  const portefeuille=$('#job-'+id[trois]).text();
  $('#js-nom-job').html(portefeuille);

  /** On ouvre la fenêtre modal */
  $('#modal-traitement-manuel').foundation('open');

  /** on sort si on clique sur non */
  $('#js-non').on('click', function(){
    /** si le spinner toune on desactive le bouton non */
    if (!$(collecteAnimation).hasClass('sp-volume')) {
      $('#modal-traitement-manuel').foundation('close');
    }
  });

  $('#js-oui').on('click', function(){
    $('#js-non').addClass('disable');
    $(collecteAnimation).addClass('sp-volume');
    $(collecteTexte).html(' Collecte en cours cours...');
  });



});
