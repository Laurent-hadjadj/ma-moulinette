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
  const portefeuille=$(`#job-${idTab[trois]}`).text();
  $('#js-nom-job').html(portefeuille);

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

    const data = { portefeuille , mode:'null' };
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
      batchManuel(idTab[trois], portefeuille);
      resolve();
      });
    });
  });
});


/**
 * [Description for batchManuel]
 * Lance le batch manuel
 *
 * @param string id
 * @param string job
 *
 * @return [type]
 *
 * Created at: 07/02/2023, 15:05:56 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const batchManuel = function(id, portefeuille){
  /** On lance le processus */
  const infoBulle='#info-bulle';
  const collecteTexte='#collecte-texte';
  const collecteAnimation='#collecte-animation';

  const data = { portefeuille };
  const options = {
    url: `${serveur()}/traitement/manuel`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType};
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

/** On affiche la log pour le job sélectonné */
$('.js-affiche-information').on('click', function() {
  /** On récupère l'ID */
  const id=$(this).attr('id');
  const idTab = id.split('-');

  /** On récupère le job et le type */
  const job=$(`#job-${idTab[1]}`).text();
  const type=$(`#${idTab[1]}`).data('type');

  /** On on récupère la log */
  lireInformationManuel(job, type);

  /** On affiche le nom du projet */
  $('#js-nom-projet').html(job);

  /** On ouvre la fenêtre modal */
  $('#modal-information').foundation('open');

  /** On va à la fin du fichier */
  $('#js-go-end').on('click', ()=>{
    const textarea = document.getElementById('js-journal');
    const end = textarea.value.length;
    textarea.setSelectionRange(end, end);
    textarea.focus();
  });
});

/**
 * [Description for lireInformationManuel]
 *
 * @return [type]
 *
 * Created at: 05/03/2023, 15:29:19 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const lireInformationManuel = function(job, type){
  const data = { job, type };
  const options = {
    url: `${serveur()}/traitement/information`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType};
  return new Promise(resolve => {
    $.ajax(options).then( t => {
      if (t.recherche==='OK') {
          $('#js-journal').text(t.journal);
      }
      resolve();
    })
  })
}

/**
 * [Description for traitementAuto]
 * Démmarrage du traitement automatique.
 *
 * @return void
 *
 * Created at: 08/02/2023, 17:09:11 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const traitementAuto=function(){
  /** On récupère le token */
  const e = document.querySelector('.batch-processing-svg');
  const token=e.dataset.session;
  const data = { token, mode: 'null' };

  const options = {
  url: `${serveur()}/traitement/auto`, type: 'POST',
  dataType: 'json', data: JSON.stringify(data), contentType};
  return new Promise(resolve => {
    $.ajax(options).then( () => {
      resolve();
    });
  });
};

/** On lance un traitement automatique */
$('.batch-processing-svg').on('click', ()=>{
  traitementAuto();
});
