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
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/batch.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import '../../auth/details.js';

/* On importe les paramètres serveur. */
import {serveur} from '../../common/properties.js';

import { showMessage, hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe les constantes */
import { contentType, un, trois, cinqCent, mille, http_500, http_400, http_200, troisMille, cinqMille } from '../../common/constante.js';

/** On gère l'affichage des jobs */
const automatique = '.automatique';
const manuel= '.manuel';
const infoBulle='#info-bulle';

// On  initialise le click
let suppressClick = false;

const getAvatarColorClass = function(username) {
    const hash = Array.from(username).reduce((acc, char) => acc + char.charCodeAt(0), 0);
    const colorIndex = hash % 10;
    return `avatar-color-${colorIndex}`;
};

/**
 * [Description for workInProgress]
 * On remonte le nombre de traitements en attente et en cours.
 *
 * @return array
 *
 * Created at: 14/06/2024 16:23:54 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const workInProgress = async function(){

  const options = {
    url: `${serveur()}/api/traitement/pending`,
    type: 'GET',
    dataType: 'json',
    contentType,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
        const t = await $.ajax(options);

        // 📌 On met à jour la bulle
        if (t.pending > 0){
          $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-start');
          $('#info-bulle-tips').html('Nombre de projet planifié.');
          $(infoBulle).html(t.nombre);
        }

        if (t.pending === 0 && t.in_progress === 0){
          $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-end');
          $('#info-bulle-tips').html('Aucun projet planifié.');
          $(infoBulle).html(t.nombre);
        }

        if (t.pending === 0 && t.in_progress > 0){
          $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-end');
          $('#info-bulle-tips').html('Un projet est en cours de traitement.');
          $(infoBulle).html(t.in_progress);
        }
        return t;
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "Une erreur inattendue s'est produite.";
      showMessage('critical', message, trace);
  }
  return null;
};


/** On lance un traitement manuel - oui Monsieur !!! */
$('.i-am-human-svg').on('click', async (e)=> {
  // On prévient le multi-click
  e.preventDefault();

  /* si on a déjà cliqué, on sort */
  if (suppressClick){
    return;
  }
    suppressClick = true;

  /** On récupère l’élément cliqué depuis le DOM */
  //i-am-human-10
  const id = e.currentTarget.id;
  const idTab = id.split('-');

  /** On regarde si un autre traitement est encours */
  const t = await workInProgress();

  const pending = t.pending;
  const in_progress =  t.in_progress;

  if (in_progress > 0){
    showMessage('warning', `Un autre traitement est cours d’exécution. Attendez quelques minutes avant de relancer.`);
    return;
  }

  if (pending > 0){
    showMessage('warning', `Le traitement ${titre_portefeuille} a été mis en attente.`);
    return;
  }

  /** clignote */
  $(`#${id}`).addClass('blink');

  /** On récupère le titre du portefeuille et le portefeuille (ie. la liste des projets) */
  const element = document.getElementById(`portefeuille-${idTab[trois]}`);
  const titre_portefeuille = element.getAttribute('data-titre');
  const portefeuille = $(`#portefeuille-${idTab[trois]}`).text().trim();
  showMessage('primary', `Le traitement ${portefeuille} a été lancé.`);
  await traitementManuel(idTab[trois], titre_portefeuille, portefeuille);
  suppressClick = false;
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
const traitementManuel = async function(id, titre_portefeuille, portefeuille){
  /** On lance le processus */
  const data = { id, titre_portefeuille, portefeuille };
  const options = {
    url: `${serveur()}/api/traitement/manuel`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-start');
  $('#info-bulle-tips').html('Un projet est en cours de traitement.');
  $(infoBulle).html(1);

  try{
    const t = await $.ajax(options);

    // 📌 Vérification des erreurs
    if (t.code !== http_200){
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      setTimeout( () => { showMessage(t.type, t.message, trace); }, troisMille);
      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 01');
      $(`#i-am-human-${id}`).removeClass('blink');

      $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-error');
      $('#info-bulle-tips').html('Traitement en cours en échec.');
      $(infoBulle).html('x');
      return;
    }

    showMessage('primary',
      `La collecte pour les projets de ${portefeuille} est terminée.<br>
        Référence : <strong>${t.reference}</strong><br>
        Temps total : ${t.temps_traitement}`);
    $(`#i-am-human-${id}`).removeClass('blink');

    /** On met à jour la bulle info */
    $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-end');
    $('#info-bulle-tips').html('Traitement terminé.');
    $(infoBulle).html('x');
    const result = `<span class="show-for-small-only"><strong>OK</strong></span>
                    <span class="show-for-medium"><strong>Succès</strong></span>`;
    $(`#success-${id}`).html(result);
    $(`#temps-execution-${id}`).html(t.temps);
    $(collecteAnimation).removeClass('sp-volume');
  } catch(erreur) {
      const trace = prepareTechnicalDetails(erreur);
      setTimeout( () => {
        showMessage('critical', "Une erreur globale est survenue lors du traitement (Erreur 500).", trace);
      }, troisMille);

      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 01');
      $(`#i-am-human-${id}`).removeClass('blink');

      $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-error');
      $('#info-bulle-tips').html('Traitement en cours en échec.');
      $(infoBulle).html('x');
    return;
  }

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
