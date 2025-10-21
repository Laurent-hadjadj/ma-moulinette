/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import '../../../styles/mon-application/batch.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';

import tinymce from 'tinymce/tinymce';

// Import TinyMCE plugins and themes as needed
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/plugins/link';
import 'tinymce/plugins/table';
import 'tinymce/plugins/image';
import 'tinymce/plugins/code';

/* On importe les paramètres serveur. */
import {serveur} from '../../common/properties.js';

import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe les constantes */
import { contentType, un, trois, cinqCent, mille, http_500, http_400, http_200 } from '../../common/constante.js';


/** Initialize TinyMCE */
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

  /** On gère l'affichage des jobs */
const jsAutomatique = '.js-automatique';
const automatique = '.automatique';
const jsManuel = '.js-manuel';
const manuel= '.manuel';
const infoBulle='#info-bulle';

/**
 * [Description for nombreProjetRabbitMQ]
 * On met à jour la liste des jobs manuels
 *
 * @return int
 *
 * Created at: 14/06/2024 16:23:54 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const nombreProjet = async function()
{
  const options = {
    url: `${serveur()}/messages/count/traitement_manuel`,
    type: 'GET',
    dataType: 'json',
    contentType
  };

  const t = await $.ajax(options);
  // 📌 Vérification des erreurs
  if (t.nombre > 0){
    $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end').addClass('bulle-info-start');
    $('#info-bulle-tips').html('Nombre de projet planifié');
    $(infoBulle).html(t.nombre);
  }

  if (t.nombre === 0){
    $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end').addClass('bulle-info-end');
    $('#info-bulle-tips').html('Aucun projet planifié');
    $(infoBulle).html(t.nombre);
  }

};

/** On affiche la liste des traitements automatiques */
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

/** On lance un traitement manuel - oui Monsieur !!! */
$('.i-am-human-svg').on('click', function() {
  /** On récupère l’élément cliqué depuis le DOM */
  //i-am-human-10
  const id=$(this).attr('id');
  const idTab = id.split('-');
  /** clignote */
  $(`#${id}`).addClass('blink');

  /** On récupère le titre du portefeuille (ie. la liste des projets) et le portefeuille */
  const element=document.getElementById(`portefeuille-${idTab[trois]}`);
  const titrePortefeuille = element.getAttribute('data-titre');
  const portefeuille=$(`#portefeuille-${idTab[trois]}`).text();

  traitementManuel(idTab[trois], titrePortefeuille, portefeuille);
});

/**
 * [Description for traitementManuel]
 * Lance le traitement manuel
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
const traitementManuel = async function(id, titrePortefeuille, portefeuille){
  /** On lance le processus */
  const data = { 'titre_portefeuille': titrePortefeuille, portefeuille };
  const options = {
    url: `${serveur()}/traitement/manuel`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const t = await $.ajax(options);
  // 📌 Vérification des erreurs
  if (t.code != http_200){
    showMessage('alert', typeMessage(t.message));
    $(`#i-am-human-${id}`).removeClass('blink');
    return;
  }

  showMessage('default', `La collecte pour les projets de ${portefeuille} est terminée.<br>Vous pouvez consulter le journal des traitements.`);
  $(`#i-am-human-${id}`).removeClass('blink');
}


  /** On affiche la log pour le job sélectionné */
  $('.js-outil-lire').on('click', function() {
    /** On récupère l'ID */
    const id=$(this).attr('id');
    const idTab = id.split('-');
    /** On récupère le job et le type */
    const portefeuille=$(`#portefeuille-${idTab[un]}`).text();
    const type=$(`#${idTab[un]}`).data('type');
    /** On récupère la log */
    lireJournal(portefeuille, type);
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
const lireJournal = async function(portefeuille, type){
  const data = { portefeuille, type };
  const options = {
    url: `${serveur()}/traitement/journal/lire`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const t = await  $.ajax(options);
  // 📌 Vérification des erreurs
  if (t.code === http_400 || t.code === http_500){
    showMessage(t.code, typeMessage(t.message));
    return t.code;
  }

  tinymce.get('js-journal').setContent('');
  if (t.recherche === 'OK' || t.code === http_200) {
    /** On affiche le nom du projet */
    $('#js-nom-projet').html(portefeuille);
    /** On ouvre la fenêtre modal */
    tinymce.get('js-journal').setContent(t.journal);
    $('#modal-information').foundation('open');
  }
}

/** On affiche la log pour le job sélectionné */
$('.js-outil-efface').on('click', function() {
  /** On récupère l'ID */
  const id=$(this).attr('id');
  const idTab = id.split('-');

  /** On récupère le job et le type */
   /** On récupère le job et le type */
  const portefeuille=$(`#portefeuille-${idTab[un]}`).text();
  const type=$(`#${idTab[1]}`).data('type');

  /** On efface la log */
  effaceJournal(portefeuille, type);

  });

const effaceJournal = async function(portefeuille, type){
  const data = { portefeuille, type };
  const options = {
    url: `${serveur()}/traitement/journal/efface`,
    type: 'DELETE',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType
  };

  const t = $.ajax(options);
  // 📌 Vérification des erreurs
  if (t.code === http_400 || t.code === http_500){
    showMessage(t.code, typeMessage(t.message));
    return t.code;
  }

  afficheMessage('success', `Le journal pour le portefeuille ${portefeuille} a été supprimé.` );
}

/**
 * [Description for traitementAuto]
 * Démarrage du traitement automatique.
 *
 * @return void
 *
 * Created at: 08/02/2023, 17:09:11 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const traitementAuto = async function(){
  /** On récupère le token */
  const e = document.querySelector('.batch-processing-svg');
  const token=e.dataset.session;
  const data = { token  };

  const options = {
  url: `${serveur()}/traitement/auto`,
  type: 'POST',
  dataType: 'json',
  data: JSON.stringify(data),
  contentType,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  await $.ajax(options);
};

/** On lance un traitement automatique */
$('.batch-processing-svg').on('click', ()=>{
  traitementAuto();
});

/*** Main */

/** On met à jour la bulle info */
/*$(infoBulle).removeClass('bulle-info-start').addClass('bulle-info-end');
$('#info-bulle-tips').html('Collecte terminée');
const result = `<span class="show-for-small-only"><strong>OK</strong></span>
                <span class="show-for-medium"><strong>Succès</strong></span>`;
$(`#resultat-${id}`).html(result);
$(`#temps-execution-${id}`).html(t.temps);
$(collecteAnimation).removeClass('sp-volume');*/
