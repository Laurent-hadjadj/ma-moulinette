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

  import {dateOptions} from '../../common/constante.js';
  import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

  /**
   * [Description for log]
   * Affiche la log.
   *
   * @param mixed txt
   *
   * @return void
   *
   * Created at: 13/12/2022, 12:58:45 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   */
  const log = function(txt) {
    const textarea = document.getElementById('log');
    textarea.scrollTop = textarea.scrollHeight;
    textarea.value += `${new Intl.DateTimeFormat('default',
    dateOptions).format(new Date())} ${txt}\n`;
  };

  /**
  * [Description for enregistrement]
  * Fonction de remplissage des tableaux.
  *
  * @param mixed mavenKey
  *
  * @return [type]
  *
  * Created at: 13/12/2022, 12:59:18 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  */
  export const clean_screen = function(type) {
    console.log('type', type);
  $('#nom-projet').text('').removeAttr('data-nom-projet');
  $('#key-analyse').text('').removeAttr('data-analyse-key');
  $('#clef-projet').text('').removeAttr('data-clef-projet');
  $('#distribution-langage').text('');

  $('#version-release').text('').removeAttr('data-release');
  $('#version-snapshot').text('').removeAttr('data-snapshot');
  $('#version-autre').text('').removeAttr('data-autre');

  $('#date-version').text('').removeAttr('data-date-version');
  $('#suppress-warning').text('').removeAttr('data-s1309');
  $('#no-sonar').text('').removeAttr('data-nosonar');
  $('#todo-liste').text('').removeAttr('data-todo');

  $('#logger-liste').text('').removeAttr('data-logger-liste');
  $('#js-logger-info').text('').removeAttr('data-logger-info');
  $('#js-logger-warn').text('').removeAttr('data-logger-warn');
  $('#js-logger-error').text('').removeAttr('data-logger-error');
  $('#js-logger-debug').text('').removeAttr('data-logger-debug');

  $('#nombre-ligne').text('').removeAttr('data-nombre-ligne');
  $('#nombre-ligne-de-code').text('').removeAttr('data-nombre-ligne-de-code');
  $('#nombre-fichier').text('').removeAttr('data-nombre-fichier');
  $('#nombre-classe').text('').removeAttr('data-nombre-classe');
  $('#nombre-fonction').text('').removeAttr('data-nombre-fonction');

  $('#coverage').text('').removeAttr('data-coverage');
  $('#ratio-dette-technique').text('').removeAttr('data-sqale-debt-ratio');
  $('#duplicated-lines-density').text('').removeAttr('data-duplicated-lines-density');
  $('#tests').text('').removeAttr('data-tests');
  $('#violations').text('').removeAttr('data-violations');
  $('#js-dette').text('').removeAttr('data-dette-minute');

  $('#nombre-bug').text('').removeAttr('data-nombre-bug');
  $('#nombre-vulnerability').text('').removeAttr('data-nombre-vulnerability');
  $('#nombre-mauvaise-pratique').text('').removeAttr('data-nombre-code-smell');

  $('#nombre-frontend').text('').removeAttr('data-nombre-frontend');
  $('#nombre-backend').text('').removeAttr('data-nombre-backend');
  $('#nombre-autre').text('').removeAttr('data-nombre-autre');
  $('#nombre-inconnu').text('').removeAttr('data-nombre-inconnu');

  $('#nombre-anomalie-bloquant').text('').removeAttr('data-nombre-anomalie-bloquant');
  $('#nombre-anomalie-critique').text('').removeAttr('data-nombre-anomalie-critique');
  $('#nombre-anomalie-info').text('').removeAttr('data-nombre-anomalie-info');
  $('#nombre-anomalie-majeur').text('').removeAttr('data-nombre-anomalie-majeur');
  $('#nombre-anomalie-mineur').text('').removeAttr('data-nombre-anomalie-mineur');

  $('#note-reliability').text('').removeAttr('data-note-reliability');
  $('#note-security').text('').removeAttr('data-note-security');
  $('#note-sqale').text('').removeAttr('data-note-sqale');
  $('#note-menace-potentielle').text('').removeAttr('data-note-menace-potentielle');

  $('#menace-potentielle-to-review-high').text('').removeAttr('data-menace-potentielle-to-review-high');
  $('#menace-potentielle-to-review-medium').text('').removeAttr('data-nombre-anomalie-critique');
  $('#menace-potentielle-to-review-low').text('').removeAttr('data-menace-potentielle-to-review-low');
  $('#menace-potentielle-reviewed-high').text('').removeAttr('data-menace-potentielle-reviewed-high');
  $('#menace-potentielle-reviewed-medium').text('').removeAttr('data-menace-potentielle-reviewed-medium');
  $('#menace-potentielle-reviewed-low').text('').removeAttr('data-menace-potentielle-reviewed-low');
  $('#menace-potentielle-totale').text('').removeAttr('data-menace-potentielle-totale');

  $('#js-bug-blocker').text('').removeAttr('data-bug-blocker');
  $('#js-bug-critical').text('').removeAttr('data-bug-critical');
  $('#js-bug-major').text('').removeAttr('data-bug-major');
  $('#js-bug-minor').text('').removeAttr('data-bug-minor');
  $('#js-bug-info').text('').removeAttr('data-bug-info');

  $('#js-vulnerability-blocker').text('').removeAttr('data-vulnerability-blocker');
  $('#js-vulnerability-critical').text('').removeAttr('data-vulnerability-critical');
  $('#js-vulnerability-major').text('').removeAttr('data-vulnerability-major');
  $('#js-vulnerability-minor').text('').removeAttr('data-vulnerability-minor');
  $('#js-vulnerability-info').text('').removeAttr('data-vulnerability-info');

  $('#js-code-smell-blocker').text('').removeAttr('data-code-smell-blocker');
  $('#js-code-smell-critical').text('').removeAttr('data-code-smell-critical');
  $('#js-code-smell-major').text('').removeAttr('data-code-smell-major');
  $('#js-code-smell-minor').text('').removeAttr('data-code-smell-minor');
  $('#js-code-smell-info').text('').removeAttr('data-code-smell-info');

  const message = (type == 'collecte') ? 'la nouvelle collecte' : `l'affichage des résultats`;
  log(` - ℹ️ Effacement des données effectuée avant ${message}.`);
}
