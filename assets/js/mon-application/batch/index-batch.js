/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/common/select2.min.css';
import '../../../styles/mon-application/batch.css';

import '../../select2/select2.min.js';
import '../../select2/i18n/fr.js'

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';

/** Gestion des modales zurb foundation compatible WCAG  */
import { modalSafe } from '../../common/safeModal.js';
import '../../auth/details.js';

/* On importe les paramètres serveur. */
import {serveur} from '../../common/properties.js';

import { showMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe les constantes */
import { content_type, dateOptions, trois, http_200, troisMille } from '../../common/constante.js';

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
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 */
const workInProgress = async function(){

  const options = {
    url: `${serveur()}/api/secure/traitement/pending`,
    type: 'GET',
    dataType: 'json',
    contentType: content_type,
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
      $(`#i-am-human-${id}`).removeClass('blink');
      suppressClick = false;
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
        contentType: content_type,
      });
      $(`#i-am-human-${id}`).removeClass('blink');
      suppressClick = false;
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
      contentType: content_type,
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
      suppressClick = false;
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

    /**
     * MODIF 22/11/2026 : les liens « Afficher le rapport »/« Télécharger le
     * PDF » (#outil-${id}) sont rendus côté Twig à partir de
     * `traitement.message` au moment du chargement de la page — figés sur
     * href="#" tant qu'aucune exécution n'a encore eu lieu. Sans ce
     * correctif, un traitement lancé ici (sans rechargement de page) reste
     * cliqué sur "#" alors que le rapport existe déjà en base. `traitement_id`
     * est stable (identifiant du Batch, pas de l'exécution) : on peut donc
     * reconstruire la même URL que `rapport_execution_pdf`
     * (BatchController::rapportExecutionPdf, route
     * /api/public/traitement/rapport/pdf/{token}/{download}) sans appel
     * réseau supplémentaire.
     */
    const rapportBase = `${serveur()}/api/public/traitement/rapport/pdf/${traitement_id}`;
    $(`#outil-${id} .pdf-view`).attr('href', `${rapportBase}/0`);
    $(`#outil-${id} .pdf-download`).attr('href', `${rapportBase}/1`);

    const temps = t.temps_traitement;
    const isZeroTime = /^0{2}:\d{1,2}:\d{1,2}(\.\d+)?$/.test(temps) && Number.parseFloat(temps.replace(/[:.]/g, '')) === 0;
    const affiche_temps = isZeroTime ? "--:--.--" : temps;
    $(`#temps-execution-${id}`).html(affiche_temps);
    suppressClick = false;

  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    showMessage('critical', "Erreur de lancement traitement", trace);

    $(infoBulle).removeClass('bulle-info-vide', 'bulle-info-start', 'bulle-info-end', 'bulle-info-error').addClass('bulle-info-error');
    $('#info-bulle-tips').html('Traitement en échec.');
    $(infoBulle).html('X');

    const result = `<span class="show-for-small-only color-rouge"><strong>KO</strong></span>
                      <span class="show-for-medium color-rouge"><strong>Erreur</strong></span>`;
    $(`#result-${id}`).html(result);
    $(`#i-am-human-${id}`).removeClass('blink');
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
 * @copyright Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 */
const traitementInformation = async function(traitement_id){
  /** On lance le processus */
  const data = { traitement_id };
  const options = {
    url: `${serveur()}/api/secure/traitement/information`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
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
    // Nom du traitement + référence
    $('.js-nom-traitement').text(traitement.nom_traitement);
    $('.js-reference').text(traitement.traitement_id);

    // Badges et infos
    $('.js-portefeuille').text(traitement.portefeuille);
    $('.js-nombre-projet').text(traitement.nombre_projet);
    const mode_collecte = (traitement.mode_collecte === 'TRAITEMENT AUTOMATIQUE')
      ? 'Automatique'
      : 'Manuel';
    $('.js-mode-collecte').text(mode_collecte);

    // Statut
    $('.js-statut').text(traitement.statut ? '✅ Succès' : '❌ Échec');
    $('.js-statut-tile')
        .removeClass('info-tile-success info-tile-failed')
        .addClass(traitement.statut ? 'info-tile-success' : 'info-tile-failed');

    // Activation
    $('.js-activated').text(traitement.is_activated ? 'Oui' : 'Non');
    $('.js-activated-tile')
        .removeClass('info-tile-success info-tile-inactive')
        .addClass(traitement.is_activated ? 'info-tile-success' : 'info-tile-inactive');

    // Dates
    $('.js-start-at').html(new Intl.DateTimeFormat('default', dateOptions)
                      .format(new Date(traitement.start_at)));
    $('.js-end-at').html(new Intl.DateTimeFormat('default', dateOptions)
                      .format(new Date(traitement.end_at)));

    // Timeline : calcul progress si fin > début
    const start = new Date(traitement.start_at);
    const end = new Date(traitement.end_at);

    // on calcul la durée
    const diffMs = Math.abs(end - start);
    const totalSeconds = Math.floor(diffMs / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    $('.js-time-elapse').html(`${minutes} min ${seconds} sec`);

    const total = Number(traitement.nombre_projet);
    const ok = Number(traitement.nombre_ok);
    const ko = Number(traitement.nombre_ko);
    const oko = Number(traitement.nombre_oko);

    // MODIF 2026-11-19 : BUG CORRIGÉ — priorité d'opérateurs manquante
    // (ok + oko / total) calculait ok + (oko / total), pas (ok + oko) / total.
    const progress = total > 0 ? Math.round(((ok + oko) / total) * 100) : 0;
    let color = '#4CAF50';
    if (progress < 80) color = '#FFB300';
    if (progress < 40) color = '#E53935';
    $('.timeline-progress')
      .css('width', `${progress}%`)
      .css('background-color', color);

    // MODIF 2026-11-19 : BUG CORRIGÉ — ok - oko pouvait devenir négatif
    // (countBatchExecutionJournalCode() comptait ko et oko comme des
    // ensembles non exclusifs). Les 3 valeurs sont désormais mutuellement
    // exclusives côté SQL, plus besoin de soustraction ici.
    $('.js-nb-ok').text(ok);
    $('.js-nb-ko').text(ko);
    $('.js-nb-oko').text(oko);

    // On suppose que ta réponse API contient aussi un tableau `projets`
    const projets = traitement.projets || [];

    // On vide le select avant de le remplir
    const $select = $('.js-select-journal');
    $select.empty().append('<option value="">-- Sélectionner un projet --</option>');

    // On injecte les nouvelles options
    projets.forEach(p => {
      const $opt = $('<option>')
        .val(p.job_id)
        .text(p.nom_projet)
        .attr('data-status', p.status);
      $select.append($opt);
    });

    // ⚠️ Détruire l'ancien select2 si déjà présent
    if ($select.hasClass("select2-hidden-accessible")) {
      $select.select2('destroy');
    }
    // On rafraîchit Select2 (ou l’initialise s’il ne l’était pas)
    if ($select.data('select2')) {
      $select.trigger('change.select2');
    } else {
      $select.select2({
        placeholder: '-- Sélectionner un projet --',
        width: '100%',
        allowClear: true,
        language: 'fr',

        templateResult: function (data) {
          if (!data.id) return data.text;

          // Lecture du statut depuis data-status
          const status = (data.element?.getAttribute('data-status') || '').toLowerCase();
          let color = '#9E9E9E';
          let label = 'Inconnu';

          if (status === 'ok' || status === 'success') {
            color = '#1f7a22ff';
            label = 'Succès';
          } else if (status === 'ko' || status === 'failed') {
            color = '#bb1b10ff';
            label = 'Échec';
          } else if (status === 'oko' || status === 'standby') {
            // MODIF 2026-11-19 : BUG CORRIGÉ — "En attente" suggérait à tort
            // qu'il restait quelque chose à faire, alors que oko (code 202)
            // signifie "déjà à jour" (collecte volontairement sautée), même
            // sémantique que le statut "oko" utilisé dans le rapport PDF.
            color = '#ed7327ff';
            label = 'Déjà à jour';
          }

          // Élément visuel stylé
          const $result = $(`
            <div class="select2-option-custom">
              <span class="status-dot" style="background-color:${color}"></span>
              <span class="option-text">${data.text}</span>
              <span class="option-status">${label}</span>
            </div>
          `);
          return $result;
        },

        templateSelection: function (data) {
          if (!data.id) return data.text;

          const status = (data.element?.getAttribute('data-status') || '').toLowerCase();
          let color = '#9E9E9E';
          if (status === 'ok' || status === 'success') color = '#1f7a22ff';
          if (status === 'ko' || status === 'failed') color = '#bb1b10ff';
          if (status === 'oko' || status === 'standby') color = '#ed7327ff';

          return $(`
            <span class="selected-option">
              <span class="status-dot" style="background-color:${color}"></span>
              ${data.text}
            </span>
          `);
        }
      });
    }
    // Ouvrir modale
    modalSafe.open('#modal-traitement-information');
  } catch(error) {
    const trace = prepareTechnicalDetails(error);
    showMessage('critical', "Une erreur globale est survenue lors du traitement (Erreur 500).", trace);
    return;
  }

}

/********************************************************/
/**               Bouton Information                   **/
/********************************************************/

$('.js-outil-info').on('click', async (e) => {
  const id = e.currentTarget.id;
  const idTab = id.split('-');
  // On récupère l'index 1
  const traitement_id = $(`#outil-${idTab[1]}`).attr('data-reference') ?? null;

  if (traitement_id == '' || traitement_id == undefined || traitement_id == null){
    showMessage('warning', "La référence à ce traitement n'existe pas (Erreur 404).");
    return;
  }
  /** On appelle l'API */
  void traitementInformation(traitement_id);
});

/********************************************************/
/**              Sélecteur de projets                  **/
/********************************************************/

// Initialisation du select2
$('.js-select-journal').select2({
  placeholder: '-- Sélectionner un projet --',
  width: '100%',
  allowClear: true,
  language: 'fr'
});

// Lorsqu’un projet est sélectionné
$('.js-select-journal').on('select2:select', async function(e) {
  const job = e.params.data.id;
  const nom_projet = e.params.data.text;

  try {
    // Requête du journal
    const data = { job, nom_projet };
    const response = await $.ajax({
      url: `${serveur()}/api/secure/traitement/journal`,
      type: 'POST',
      data: JSON.stringify(data),
      contentType: content_type,
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app'
      }
    });

    // Injection du contenu
    $('.js-journal-nom').text(nom_projet);
    $('.js-journal-content').html(response.html);

    // Ouverture de la modale
    modalSafe.open('#modal-journal');
  } catch (error) {
    const trace = prepareTechnicalDetails(error);
    showMessage('critical', 'Impossible de charger le journal demandé.', trace);
  }
});

/** On ferme la modale "Information sur le traitement" */
$('#bouton-fermer-information').on('click', function() {
  modalSafe.close('#modal-traitement-information');
});

/** On ferme la modale "Journal d'exécution" */
$('#bouton-fermer-journal').on('click', function() {
  modalSafe.close('#modal-journal');
});

// Optionnel : nettoyage du contenu à la fermeture
$('#modal-journal').on('closed.zf.reveal', function() {
  $('.js-journal-content').html('<p class="text-center text-muted">Aucun journal chargé.</p>');
});

/********************************************************/
/**                   Rapport PDF                      **/
/********************************************************/
/* Les liens .pdf-view/.pdf-download sont de vraies ancres <a href="...">
 * (href posé côté Twig au chargement, rafraîchi côté JS après un traitement
 * réussi — voir lancerTraitementSiPossible() ci-dessus) : la navigation
 * native du navigateur suffit, aucun gestionnaire de clic n'est nécessaire. */

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

    /* Spinner : indique visuellement que le clic a bien été pris en compte,
     * dès le clic (avant même la réponse de workInProgress()). Retiré dans
     * tous les chemins de sortie de lancerTraitementSiPossible(). */
    $(e.currentTarget).addClass('blink');

    /** On récupère le titre du portefeuille et le portefeuille (ie. la liste des projets) */
    const element = document.getElementById(`portefeuille-${idTab[trois]}`);
    const element2 = document.getElementById(`outil-${idTab[trois]}`);
    const titre_portefeuille = element.getAttribute('data-titre');
    const portefeuille = $(`#portefeuille-${idTab[trois]}`).text().trim();
    const traitement_id = element2.getAttribute('data-reference');
    await lancerTraitementSiPossible(idTab[trois], traitement_id, titre_portefeuille, portefeuille);
  });
});
