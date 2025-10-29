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

$(document).ready(() => {
  pendingWorkerService.start();

  $('.i-am-human-svg').click(async () => {
    const projetId = $(this).data('projet-id');
    console.log(projetId);
    //await lancerTraitementSiPossible(projetId);
  });
});

/**
 * pendingWorkerService
 *
 * @var [type]
 */
const pendingWorkerService = {
  worker: null,
  pollingDelay: 30000, // configurable
  infoBulleSelector: '#info-bulle',
  infoTipsSelector: '#info-bulle-tips',

  start() {
    if (this.worker) return; // déjà démarré

    this.worker = new Worker('./pendingWorker.js');

    // écoute les messages du worker
    this.worker.onmessage = (event) => {
      const { status, data, error } = event.data;
      if (status === 'ok') {
        this.updateInfoBulle(data);
      } else {
        sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] ❌', error);
      }
    };

    // Pause/reprise automatique selon visibilité
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        this.pause();
      } else {
        this.resume();
      }
    });

    sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] ✅ Service démarré');
  },

  pause() {
    if (this.worker) {
      this.worker.terminate();
      this.worker = null;
      sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] ⏸️ Pause (onglet inactif)');
    }
  },

  resume() {
    if (!this.worker) {
      sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] ▶️ Reprise (onglet actif)');
      this.start(); // recrée le worker
    }
  },

  stop() {
    this.pause();
    sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] 🛑 Service arrêté manuellement');
  },

  updateInfoBulle(t) {
    const $infoBulle = $(this.infoBulleSelector);
    const $tips = $(this.infoTipsSelector);

    if (!t) return;

    // Affiche loader pendant la mise à jour
    $infoBulle.addClass('loading');

    $infoBulle.removeClass('bulle-info-vide bulle-info-start bulle-info-end bulle-info-error');

    if (t.pending > 0) {
      $infoBulle.addClass('bulle-info-start').html(t.pending);
      $tips.html('Nombre de projet planifié.');
    } else if (t.pending === 0 && t.in_progress > 0) {
      $infoBulle.addClass('bulle-info-end').html(t.in_progress);
      $tips.html('Un projet est en cours de traitement.');
    } else {
      $infoBulle.addClass('bulle-info-end').html('0');
      $tips.html('Aucun projet planifié.');
    }

    // Retire loader après affichage
    setTimeout(() => $infoBulle.removeClass('loading'), 300);
  }
};

const lancerTraitementSiPossible = async function(projetId) {
  try {
    const t = await workInProgress(); // récupère pending/in_progress

    if (!t) return console.warn("Impossible de récupérer l'état des traitements");

    if (t.in_progress > 0) {
      console.log("Un traitement est déjà en cours → ajout à pending");
      await $.ajax({
        url: '/api/traitement/add-pending',
        type: 'POST',
        data: JSON.stringify({ projetId }),
        contentType: 'application/json'
      });
      return;
    }

    console.log("Aucun traitement en cours → lancement immédiat");
    await $.ajax({
      url: '/api/traitement/start',
      type: 'POST',
      data: JSON.stringify({ projetId }),
      contentType: 'application/json'
    });

  } catch (error) {
    console.error("Erreur lancement traitement:", error);
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

/** On lance un traitement manuel - oui Monsieur !!! */
$('.betat?i-am-human-svg').on('click', async (e)=> {
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
  console.log('terminée ?');
  suppressClick = false;
});
