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

import tinymce from 'tinymce/tinymce';

// Import TinyMCE plugins and themes as needed
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/plugins/link';
import 'tinymce/plugins/table';
import 'tinymce/plugins/image';
import 'tinymce/plugins/code';

/* On importe les paramètres serveur. */
import {serveur} from './properties.js';

/** On importe les constatntes */
import { contentType, trois, cinqCent, mille, http_500, http_400, http_200 } from './constante';


// Initialize TinyMCE
const useDarkMode = window.matchMedia('(prefers-color-scheme: default)').matches;
tinymce.init({
  selector: 'textarea.tinymce',
  license_key: 'gpl',
  language: 'fr_FR',
  //skin: 'oxide',
  theme: 'silver',
  editable_root: false,
  plugins: 'preview',
  menubar: false,
  toolbar: " preview print copy",
  skin: useDarkMode ? 'oxide-dark' : 'oxide',
  content_css: useDarkMode ? 'dark' : 'default',
  content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
  base_url: '/build/tinymce' });

  /** On gére l'affichage des jobs */
const jsAutomatique = '.js-automatique';
const automatique = '.automatique';
const jsManuel = '.js-manuel';
const manuel= '.manuel';

const afficheMessage=function(t){
  $('#callout-projet-message').removeClass('hide success alert warning primary secondary');
  $('#callout-projet-message').addClass(t.type);
  $('#js-reference-information').html(t.reference);
  $('#js-message-information').html(t.message);
}

/** On affche la liste des traitements automatiques */
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

/** On affiche la liste des traitements manuels */
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
  //const infoBulle='#info-bulle';

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

  /** On sort si on clique sur non */
  $('#js-non').on('click', function(){
    /** si le spinner tourne on desactive le bouton non */
    if (!$(collecteAnimation).hasClass('sp-volume')) {
      $(`#${id}`).removeClass('blink');
      $('#modal-traitement-manuel').foundation('close');
    }
  });

  /** Si on clique OUI */
  $('#js-oui').on('click', function(){
    /** On désactive le bouton non */
    $('#js-non').addClass('disable');
    /** clignote */
    $(`#${id}`).addClass('blink');
    $(collecteAnimation).addClass('sp-volume');
    $(collecteTexte).html(`Démarrage du traitement...`);
    /** On appel la fonction de démarrage des traitements en manuel */
    // idTab = l'id de la ligne, portefeuille = liste des projets
    batchManuel(idTab[trois], portefeuille);
  });
});

/**
 * [Description for batchManuel]
 * Lance le batch manuel
 *
 * @param string id
 * @param string portefeuille
 *
 * @return void
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
  const portefeuille=$(`#job-${idTab[1]}`).text();
  const type=$(`#${idTab[1]}`).data('type');

  /** On on récupère la log */
  lireInformationManuel(portefeuille, type);

  /** On va à la fin du fichier */
  //$('#js-go-end').on('click', ()=>{
  //  const textarea = document.getElementById('js-journal');
  //  const end = textarea.value.length;
  //  textarea.setSelectionRange(end, end);
  //  textarea.focus();
  });

/**
 * [Description for lireInformationManuel]
 *
 * @return void
 *
 * Created at: 05/03/2023, 15:29:19 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const lireInformationManuel = function(portefeuille, type){
  const data = { portefeuille, type };
  const options = {
    url: `${serveur()}/traitement/information`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType};
  return new Promise(resolve => {
    $.ajax(options).then( t => {
      if (t.code === http_400 || t.code === http_500){
        afficheMessage(t);
        return t.code;
      }
      if (t.recherche==='OK' || t.code===http_200) {
        /** On affiche le nom du projet */
        $('#js-nom-projet').html(portefeuille);
        /** On ouvre la fenêtre modal */
        $('#modal-information').foundation('open');
        tinymce.get('js-journal').setContent(t.journal);
      }

      /** On va à la fin du fichier */
      $('#js-go-end').on('click', ()=>{
        const textarea = document.getElementById('js-journal');
        const end = textarea.value.length;
        textarea.setSelectionRange(end, end);
        textarea.focus();
      });
    })
    resolve();
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
