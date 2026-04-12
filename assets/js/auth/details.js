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
import {serveur} from '../common/properties.js';

/** La gestion des messagesJS */
import { showMessage, prepareTechnicalDetails } from '../common/messageHelper.js';

/** Gestion des modales zurb fundation compatible WCAG  */
import { ModalSafe } from '../common/safeModal.js';

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
 * @type {"#js-reset-password"}
 */
$('#js-reset-password').on('click', function () {
  let data = {}, reset_password = 0;

  /** On efface les messages */
  $('#reset-password-message').html('');

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
      url:  serveur()+`/api/secure/mot-de-passe/mise-a-jour`,
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
      const t = $.ajax(options);
      if (Number(t.code) === http_400 || Number(t.code) === http_500){
        $('#reset-password-message').html(t.message)
        return;
      }
      const r = document.getElementById('js-reset-password');
      r.dataset.resetPassword = reset_password;

      const message = '<span class="open-sans color-rouge">📌Vous devez vous reconnecter pour changer votre mot de passe.</span>';
      if (reset_password===1) {
        $('#reset-password-message').html(message);
      } else {
        $('#reset-password-message').html('');
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
const changeMe = async function(avatar){
  const data = { avatar };
  const options = {
      url:  serveur()+`/api/secure/utilisateur/change-me`,
      method: 'POST',
      dataType: 'json',
      data: JSON.stringify(data),
      contentType,
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app'
      },
  };

  try {
        const t = await $.ajax(options);

        if (t.code !== http_200){
            const hasTrace = !!t.trace;
            const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
            showMessage(t.type, t.message, trace);
            ModalSafe.close('#mes-avatars');
            ModalSafe.open('#modal-information-utilisateur');
            return;
        }
        showMessage('info', t.message, null);
        ModalSafe.close('#mes-avatars');
        ModalSafe.open('#modal-information-utilisateur');
  } catch (erreur) {
        if (typeof error === 'object') {
                  sessionStorage.setItem('ma_moulinette_error', `Erreur inattendue : ${JSON.stringify(erreur, null, 2)}`);
          } else {
                  sessionStorage.setItem('ma_moulinette_error', `Erreur inattendue : ${erreur}`);
          }

        // Gestion d'erreurs génériques
        const message = `Une erreur inattendue est survenue (Erreur 500).`;
        showMessage(t.type, t.message, trace);
            ModalSafe.close('#mes-avatars');
            ModalSafe.open('#modal-information-utilisateur');

        const trace = prepareTechnicalDetails(erreur);
        showMessage('critical', message, trace);
        ModalSafe.close('#mes-avatars');
        ModalSafe.open('#modal-information-utilisateur');
        return;
  }
}

/** On efface les messages */
$('#mise-a-jour-message').html('');

/** On ouvre la modale utilisateur */
$('#container-information-user').on('click', function () {
    ModalSafe.open('#modal-information-utilisateur');
});

/** On ouvre la modale utilisateur */
$('#bouton-changer-avatar').on('click', function () {
    ModalSafe.open('#mes-avatars');
});

/** On ferme proprement la modale */
$('#bouton-fermer-information-utilisateur').on('click', function () {
    ModalSafe.close('#modal-information-utilisateur');
});

/** Validation du choix de l'avatar */
$('.thumbnail').on('click', function(){
    const id = $(this).attr('id');
    const theme = $(`#${id}`).data('theme');
    const image = $(`#${id}`).data('image');
    const src = $(`#${id}`).attr('src');
    const assets = `${theme}/${image}`;

    $('#ajouter-mon-avatar').prop('src', src);
    const data = document.getElementById('ajouter-mon-avatar');
    data.dataset.theme = theme;
    data.dataset.image = image;
    $('#registration_form_avatar').val(assets);

    const t = changeMe(assets);
  });
