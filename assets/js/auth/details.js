/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

/** On importe les constantes */
import { http_200, http_400, http_500, un, content_type } from '../common/constante.js';

/* On importe les paramètres serveur. */
import { serveur } from '../common/properties.js';

/** La gestion des messagesJS */
import { showMessage, prepareTechnicalDetails } from '../common/messageHelper.js';

/** Gestion des modales zurb foundation compatible WCAG  */
import { modalSafe } from '../common/safeModal.js';

// Initialisation de l'état du switch à l'ouverture
const mon_switch_password = $('#js-reset-password');
const $r = document.getElementById('js-reset-password');
const init = $r.dataset.resetPassword;

if (init >= un) {
  const $r = $('#js-reset-password');
  $r.prop('checked', true);
  $r.attr('aria-checked', 'true');
}
/**
 * description
 * On active ou non la mise à jour du mot de passe.
 *
 * MODIF 2026-07-19 : `$.ajax()` était appelé sans `await` dans un handler non
 * async — `t` valait l'objet jqXHR (pas la réponse JSON), donc `t.code`
 * valait toujours `undefined` : la branche d'erreur (400/500) ne se
 * déclenchait jamais, quelle que soit la réponse réelle du serveur.
 *
 * @type {"#js-reset-password"}
 */
$('#js-reset-password').on('click', async function () {
  let data = {}, reset_password = 0;

  /** On efface les messages */
  $('#mise-a-jour-message').html('');

  const oui_non = $('#js-reset-password').is(':checked');

  // Mise à jour de l'attribut aria-checked
  mon_switch_password.attr('aria-checked', oui_non ? 'true' : 'false');

  /** Par défaut on bloque la mise à jour du mot de passe. */
  data = { reset_password };
  if (oui_non === true) {
    data = { 'reset_password': 1 };
    reset_password = 1
  }

  /** On prépare les paramètres pour l'appel de l'API */
  const options = {
      url:  serveur() + `/api/secure/mot-de-passe/mise-a-jour`,
      method: 'POST',
      dataType: 'json',
      data: JSON.stringify(data),
      contentType: content_type,
      headers: {
          'X-API-Custom-403': 'true',
          'X-Internal-Front': 'front-app'
      }
  };

  try {
    const t = await $.ajax(options);
    if (Number(t.code) === http_400 || Number(t.code) === http_500){
        $('#mise-a-jour-message').html(t.message)
        return;
      }
      const r = document.getElementById('js-reset-password');
      r.dataset.resetPassword = reset_password;

      const message = '<span class="open-sans color-rouge">📌Vous devez vous reconnecter pour changer votre mot de passe.</span>';
      if (reset_password===1) {
        $('#mise-a-jour-message').html(message);
      } else {
        $('#mise-a-jour-message').html('');
      }
    } catch(error) {
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur critique inconnue est survenue (Erreur 500).";
    showMessage('critical', message, trace);
  }
});

/**
 * [Description for changeMe]
 *
 * @return void
 *
 * Created at: 07/07/2025 12:30:10 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
/* MODIF 2026-09-14 : `contentType,` (raccourci ES6)
 * référençait une variable inexistante (seul `content_type` est importe) :
 * ReferenceError immédiat, hors du try/catch, a chaque clic sur un avatar
 * -> aucune requête envoyée, aucun changement visuel. Meme bug que celui
 * corrige le 2026-07-19 sur le handler mot-de-passe : `$.ajax()` sans
 * `await`. Le bloc catch référençait en plus `t`/`trace`/`erreur` hors de
 * portée ou jamais déclarés. */
const changeMe = async function(avatar){
  const data = { avatar };
  const options = {
    url: serveur() + `/api/secure/utilisateur/change-me`,
    method: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
    const t = await $.ajax(options);

    if (Number(t.code) !== http_200){
      const hasTrace = !!t.trace;
      const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
      showMessage(t.type, t.message, trace);
      modalSafe.close('#mes-avatars');
      modalSafe.open('#modal-information-utilisateur');
      return;
    }
    showMessage('info', t.message, null);
    modalSafe.close('#mes-avatars');
    modalSafe.open('#modal-information-utilisateur');
  } catch (error) {
    if (typeof error === 'object') {
      sessionStorage.setItem('ma_moulinette_error', `Erreur inattendue : ${JSON.stringify(error, null, 2)}`);
    } else {
      sessionStorage.setItem('ma_moulinette_error', `Erreur inattendue : ${error}`);
    }

    // Gestion d'erreurs génériques
    const trace = prepareTechnicalDetails(error);
    const message = `Une erreur inattendue est survenue (Erreur 500).`;
    showMessage('critical', message, trace);
    modalSafe.close('#mes-avatars');
    modalSafe.open('#modal-information-utilisateur');
  }
}

/** On ouvre la modale des informations personnelles (icône ⚙️ du bandeau) */
$('#bouton-ouvrir-information-utilisateur').on('click', function () {
  modalSafe.open('#modal-information-utilisateur');
});

/** On ouvre la modale des avatars */
$('#bouton-changer-avatar').on('click', function () {
  modalSafe.open('#mes-avatars');
});

/** On ferme proprement la modale */
$('#bouton-fermer-information-utilisateur').on('click', function () {
  modalSafe.close('#modal-information-utilisateur');
});

/** On ferme la modale des avatars sans en choisir un */
$('#bouton-fermer-mes-avatars').on('click', function () {
  modalSafe.close('#mes-avatars');
});

/**
 * [Description for selectAvatar]
 * Validation du choix de l'avatar (souris ET clavier).
 *
 * MODIF 2026-09-14 : les vignettes ne recevaient
 * aucun retour visuel au clic/focus (cf. common.css .thumbnail.selected).
 * On pose ici la classe et l'attribut aria-pressed sur la vignette choisie.
 *
 * @param {HTMLElement} el
 * @return void
 */
const selectAvatar = function(el){
  const $el = $(el);
  const id = $el.attr('id');
  const theme = $el.data('theme');
  const image = $el.data('image');
  const src = $el.attr('src');
  const assets = `${theme}/${image}`;

  $('.thumbnail').removeClass('selected').attr('aria-pressed', 'false');
  $el.addClass('selected').attr('aria-pressed', 'true');

  $('#ajouter-mon-avatar').prop('src', src);
  const data = document.getElementById('ajouter-mon-avatar');
  data.dataset.theme = theme;
  data.dataset.image = image;
  $('#registration_form_avatar').val(assets);

  changeMe(assets);
};

$('.thumbnail').on('click', function(){
  selectAvatar(this);
});

/** Activation clavier (Entrée / Espace) : un <img role="button"> n'active pas
 * nativement au clavier, contrairement à un vrai <button>. */
$('.thumbnail').on('keydown', function(event){
  if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
    event.preventDefault();
    selectAvatar(this);
  }
});
