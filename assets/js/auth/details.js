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

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

/** On importe les constantes */
import { http_200, un, contentType } from '../common/constante.js';

/* On importe les paramètres serveur. */
import {serveur} from '../common/properties.js';

/** La gestion des messagesJS */
import { showMessage, prepareTechnicalDetails } from '../common/messageHelper.js';

  /**
   * [Description for getResetPasswordChange]
   *
   * @return [type]
   *
   * Created at: 07/07/2025 12:30:10 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  const getResetPasswordChange = async function(reset_password){
    const data = { reset_password };
    const options = {
        url:  serveur()+`/api/mot-de-passe/mise-a-jour`,
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
              const trace = prepareTechnicalDetails(t.trace);
              showMessage(t.type, t.message, trace);
            return;
          }

          if (reset_password === un) {
            const message = '<span class="color-rouge">Vous devez vous reconnecter pour changer votre mot de passe.</span><br>';
            $('#mise-a-jour-message').html(message);
          } else {
            $('#mise-a-jour-message').html('');
          }
    } catch (erreur) {
          if (typeof error === 'object') {
                    sessionStorage.setItem('ma_moulinette_error', `Erreur inattendue : ${JSON.stringify(erreur, null, 2)}`);
            } else {
                    sessionStorage.setItem('ma_moulinette_error', `Erreur inattendue : ${erreur}`);
            }

          // Gestion d'erreurs génériques
          const message = `Une erreur inattendue est survenue (Erreur 500).`;
          const trace = prepareTechnicalDetails(erreur);
          showMessage('critical', message, trace);
    }
  }

  /**
 * description
 * On active ou non la mise à jour du mot de passe.
 *
 * @type {"#js-identifiant-oui-non"}
 */
$('#js-identifiant-oui-non').on('click', function () {
  /** On efface les messages */
  $('#mise-a-jour-message').html('');

  const $switch = $('#js-identifiant-oui-non');
  const $oui_non = $switch.is(':checked');

  // Mise à jour de l'attribut aria-checked
  $switch.attr('aria-checked', $oui_non ? 'true' : 'false');
  const OuiNonConverter = $oui_non ? 1 : 0;
  /** On appel l'api pour mettre à jour */
  getResetPasswordChange(OuiNonConverter);
});

/** On récupère la valeur de data-reset-password et on met à jour le switch */
// Initialisation de l'état du switch à l'ouverture
const $r = document.getElementById('js-identifiant-oui-non');
const resetPassword = $r.dataset.resetPassword;

if (resetPassword >= un) {
  const $r = $('#js-identifiant-oui-non');
  $r.prop('checked', true);
  $r.attr('aria-checked', 'true');
}

/** On efface les messages */
$('#mise-a-jour-message').html('');
