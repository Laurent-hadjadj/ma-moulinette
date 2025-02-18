/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) Lilmod & Lelamed - 2015-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

/**
 * [Description for showMessage]
 *  Affiche les messages JS
 *
 * @param string type
 * @param string message
 *
 * @return void
 *
 * Created at: 20/12/2024 14:13:03 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
export const showMessage = function(type, message) {
  const messageElement = $('#message-box');
  const textElement = $('#message-text');
  const closeElement = $('#message-close-button');

  /* Réinitialise les classes d'alerte et le style inline */
  messageElement.removeClass('alert primary secondary success warning default hide');
  closeElement.removeClass('alert primary secondary success warning default');
  messageElement.css('display', ''); // Réinitialise le style inline

  /** Ajoute la classe correspondante */
  messageElement.addClass(type);
  closeElement.addClass(type);

  /** Ajouter le message */
  textElement.html(message);
  /** Affiche l'élément */
  messageElement.removeClass('hide');
}

/**
  * [Description for hideMessage]
  * Masque le message
  *
  * @return [type]
  *
  * Created at: 19/01/2025 13:59:14 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
  */
export function hideMessage() {
  const messageElement = $('#message-box');
  // Masque l'élément
  messageElement.addClass('hide');
  //'messageElement.css('display', 'none');
}

/**
 * [Description for afficheMessage]
 * Détermine si le message est un string ou un tableau
 *
 * @param mixed t
 *
 * @return void
 *
 * Created at: 14/03/2024 10:11:15 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
export const typeMessage = function(t){
  let message;
  if (typeof t.message === 'string') {
    message = t.message;
  } else {
    message = t.message[0];
  }
  return message;
}

/** Exemple pour afficher un message */
// -- showMessage('alert', `<strong>[Titre]</strong> - message`);
/** Exemple pour normaliser un message */
// showMessage(t.type, typeMessage(t.message))
/** Exemple pour masquer un message */
// -- setTimeout(() => { hideMessage(); }, 5000);
