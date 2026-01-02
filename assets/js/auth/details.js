/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2025.
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

const mon_switch_password = $('#js-reset-password');

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

/** On récupère la valeur de data-reset-password et on met à jour le switch */
// Initialisation de l'état du switch à l'ouverture
const $r = document.getElementById('js-reset-password');
const init = $r.dataset.resetPassword;

if (init >= un) {
  const $r = $('#js-reset-password');
  $r.prop('checked', true);
  $r.attr('aria-checked', 'true');
}

/** On efface les messages */
$('#reset-password-message').html('');
