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
import { contentType, dateOptions, un, trois, cinqCent, mille, http_500, http_400, http_200, troisMille, cinqMille } from '../../common/constante.js';

/** on charge le service pendingWorker */
import { pendingWorkerService } from '../batch/pendingWorkerService.js';

/** On gère l'affichage des jobs */
const automatique = '.automatique';
const manuel= '.manuel';
const infoBulle='#info-bulle';

// On  initialise le click
let suppressClick = false;

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
    url: `${serveur()}/api/secure/traitement/pending`,
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
        return t;
  } catch(error) {
      const trace = prepareTechnicalDetails(error);
      const message = "Une erreur inattendue s'est produite lors de la récupération des données de disponibilité de workInProgress.";
      showMessage('critical', message, trace);
  }
  return null;
};

const lancerTraitementSiPossible = async function(id, traitement_id, titre_portefeuille, portefeuille) {
  try {
    const t = await workInProgress(); // récupère pending/in_progress

    if (!t) {
      //console.debug( "⚠️ Impossible de récupérer l'état des traitements.");
      sessionStorage.setItem('ma_moulinette_batch', "⚠️ Impossible de récupérer l'état des traitements");
      showMessage('warning', `Impossible de récupérer l'état des traitements.`);
      return
    }

    if (t.in_progress > 0) {
      //console.debug("⚠️ Un traitement est déjà en cours → ajout à pending");
      sessionStorage.setItem('ma_moulinette_batch',"⚠️ Un traitement est déjà en cours → ajout à pending")
      showMessage('warning', `Un traitement est déjà en cours → ajout à la file d'attente.`);

      await $.ajax({
        url: '/api/secure/traitement/add-pending',
        type: 'POST',
        data: JSON.stringify({traitement_id, titre_portefeuille, portefeuille }),
        contentType: 'application/json'
      });
      return;
    }

    //console.debug("ℹ️ Aucun traitement en cours → lancement immédiat");
    sessionStorage.setItem('ma_moulinette_batch',"ℹ️ Aucun traitement en cours → lancement immédiat.");
    showMessage('primary', `Aucun traitement en cours → lancement immédiat.`);

    const data = { traitement_id, titre_portefeuille, portefeuille };
    const options = {
      url: `${serveur()}/api/secure/traitement/start`,
      type: 'POST',
      dataType: 'json',
      data: JSON.stringify(data),
      contentType,
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app'
      },
    };

    const start = await $.ajax(options);

    if (start.code !== http_200){
      const hasTrace = !!start.trace;
      const trace = hasTrace ? prepareTechnicalDetails(start.trace) : null;
      setTimeout( () => { showMessage(start.type, start.message, trace);}, troisMille );

      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 01');
      $(`#i-am-human-${id}`).removeClass('blink');

      $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-error');
      $('#info-bulle-tips').html('Traitement en échec.');
      $(infoBulle).html('X');

      const result = `<span class="show-for-small-only color-rouge"><strong>KO</strong></span>
                      <span class="show-for-medium color-rouge"><strong>Erreur</strong></span>`;
      $(`#result-${id}`).html(result);
      return;
    }

    showMessage('primary',
      `La collecte pour les projets de ${portefeuille} est terminée.<br>
        Référence : <strong>${start.reference}</strong><br>
        Temps total : ${start.temps_traitement}`);
    $(`#i-am-human-${id}`).removeClass('blink');

    /** On met à jour la bulle info */
    $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-end');
    $('#info-bulle-tips').html('Traitement terminé.');
    $(infoBulle).html('-');

    const result = `<span class="show-for-small-only color-vert"><strong>OK</strong></span>
                    <span class="show-for-medium color-vert"><strong>Succès</strong></span>`;
    $(`#result-${id}`).html(result);

    const temps = t.temps_traitement;
    const isZeroTime = /^0{2}:\d{1,2}:\d{1,2}(\.\d+)?$/.test(temps) && parseFloat(temps.replace(/[:.]/g, '')) === 0;
    const affiche_temps = isZeroTime ? "--:--.--" : temps;
    $(`#temps-execution-${id}`).html(affiche_temps);
    suppressClick = false;

  } catch (erreur) {
    const trace = prepareTechnicalDetails(erreur);
    showMessage('critical', "Erreur de lancement traitement", trace);

    $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-error');
    $('#info-bulle-tips').html('Traitement en échec.');
    $(infoBulle).html('X');

    const result = `<span class="show-for-small-only color-rouge"><strong>KO</strong></span>
                      <span class="show-for-medium color-rouge"><strong>Erreur</strong></span>`;
    $(`#result-${id}`).html(result);
    suppressClick = false;
    return;
  }

}

/**
 * [Description for traitementInformation]
 * LRécupère les information du traitement
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
const traitementInformation = async function(traitement_id){
  /** On lance le processus */
  const data = { traitement_id };
  const options = {
    url: `${serveur()}/api/secure/traitement/information`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try{
    const t = await $.ajax(options);

    // 📌 Vérification des erreurs
    if (t.code !== http_200){
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      return;
    }

    const traitement = t.map;
    // Nom du traitement
    $('.js-nom-traitement').text(traitement.nom_traitement);

    // Badges et infos
    $('.js-portefeuille').text(traitement.portefeuille);
    $('.js-nombre-projet').text(traitement.nombre_projet);
    $('.js-mode-collecte').text(traitement.mode_collecte);

    // Statut
    $('.js-statut')
        .text(traitement.statut ? '✅ Succès' : '❌ Échec')
        .removeClass('success failed')
        .addClass(traitement.statut ? 'success' : 'failed');

    // Activation
    $('.js-activated')
        .text(traitement.is_activated ? 'Oui' : 'Non')
        .removeClass('active inactive')
        .addClass(traitement.is_activated ? 'active' : 'inactive');

    // Dates
    $('.js-start-at').html(new Intl.DateTimeFormat('default', dateOptions)
                      .format(new Date(traitement.start_at)));
    $('.js-end-at').html(new Intl.DateTimeFormat('default', dateOptions)
                      .format(new Date(traitement.end_at)));

    // Timeline : calcul progress si fin > début
    const start = new Date(traitement.start_at);
    const end = new Date(traitement.end_at);
    const now = new Date();
    let progress = 0;

    // on calcul la durée
    const time_elapse = Math.abs((end - start) / (1000 * 60));;
    $('.js-time-elapse').html(`${time_elapse} minutes`);

    if (now < start) progress = 0;
    else if (now > end) progress = 100;
    else progress = ((now - start) / (end - start)) * 100;

    $('.timeline-progress').css('width', `${progress}%`);


    // Ouvrir modale
    $('#modal-traitement-information').foundation('open');
  } catch(erreur) {
    const trace = prepareTechnicalDetails(erreur);
    showMessage('critical', "Une erreur globale est survenue lors du traitement (Erreur 500).", trace);
    return;
  }

}

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
    url: `${serveur()}/api/secure/traitement/manuel`,
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
      $('#info-bulle-tips').html('Traitement en échec.');
      $(infoBulle).html('X');

      const result = `<span class="show-for-small-only color-rouge"><strong>KO</strong></span>
                      <span class="show-for-medium color-rouge"><strong>Erreur/strong></span>`;
      $(`#result-${id}`).html(result);
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
    $(infoBulle).html('-');

    const result = `<span class="show-for-small-only color-vert"><strong>OK</strong></span>
                    <span class="show-for-medium color-vert"><strong>Succès</strong></span>`;
    $(`#result-${id}`).html(result);

    const temps = t.temps_traitement;
    const isZeroTime = /^0{2}:\d{1,2}:\d{1,2}(\.\d+)?$/.test(temps) && parseFloat(temps.replace(/[:.]/g, '')) === 0;
    const affiche_temps = isZeroTime ? "--:--:--" : temps;
    $(`#temps-execution-${id}`).html(affiche_temps);
  } catch(erreur) {
      const trace = prepareTechnicalDetails(erreur);
      setTimeout( () => {
        showMessage('critical', "Une erreur globale est survenue lors du traitement (Erreur 500).", trace);
      }, troisMille);

      sessionStorage.setItem('ma_moulinette_collecte', 'Erreur phase 01');
      $(`#i-am-human-${id}`).removeClass('blink');

      $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-error');
      $('#info-bulle-tips').html('Traitement en échec.');
      $(infoBulle).html('X');

      const result = `<span class="show-for-small-only color-rouge"><strong>KO</strong></span>
                      <span class="show-for-medium color-rouge"><strong>Erreur/strong></span>`;
      $(`#result-${id}`).html(result);
    return;
  }
}

$('.js-outil-lire').on('click', async (e) => {
  const id = e.currentTarget.id;
  const idTab = id.split('-');
  // on récupère l'index 1
  const traitement_id = $(`#outil-${idTab[1]}`).attr('data-reference') ?? null;

  if (traitement_id == '' || traitement_id == undefined || traitement_id == null){
    showMessage('warning', "La référence à ce traitement n'existe pas (Erreur 404).");
    return;
  }
  /** On appelle l'API */
  traitementInformation(traitement_id);
});

$(document).ready(() => {
  const $bulle_wip = $('#info-bulle-in-progress');
  const $bulle_workers = $('#info-bulle-workers');

  // Valeur par défaut (avant que le worker ne réponde)
  if ($bulle_wip.length && !$bulle_wip.text().trim()) {
    $bulle_wip
      .addClass('bulle-init')
      .removeClass('bulle-ok bulle-error')
      .text('–')
      .attr('title', 'Initialisation du service...' );
    $bulle_workers
      .addClass('bulle-init')
      .removeClass('bulle-ok bulle-retry bulle-error')
      .text('–')
      .attr('title', 'Initialisation du service...' );
  }

  pendingWorkerService.stop({ debug: false });

  $('.i-am-human-svg').on('click', async (e) => {
    /* si on a déjà cliqué, on sort */
    if (suppressClick){
      showMessage('warning', `Un traitement est déjà en cours → ajout à la file d'attente.`);
      return;
    }
    suppressClick = true;

    const id = e.currentTarget.id;
    const idTab = id.split('-');

    /** On récupère le titre du portefeuille et le portefeuille (ie. la liste des projets) */
    const element = document.getElementById(`portefeuille-${idTab[trois]}`);
    const element2 = document.getElementById(`outil-${idTab[trois]}`);
    const titre_portefeuille = element.getAttribute('data-titre');
    const portefeuille = $(`#portefeuille-${idTab[trois]}`).text().trim();
    const traitement_id = element2.getAttribute('data-reference');
    await lancerTraitementSiPossible(idTab[trois], traitement_id, titre_portefeuille, portefeuille);
  });
});
