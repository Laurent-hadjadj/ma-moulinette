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
  /* MODIF 2026-09-13 : la quasi-totalité des
   * sélecteurs ci-dessous ciblait les anciens IDs (sans préfixe js-),
   * retirés lors du renommage des IDs du 2026-05-18. clean_screen() ne
   * nettoyait donc quasiment plus rien à l'écran : chaque $('#...')
   * matchait un ensemble jQuery vide, sans erreur JS (no-op silencieux).
   * Réalignement complet sur les IDs réels (templates/projet/*.html.twig)
   * et les data-attributes posés par peinture.js (remplissage /
   * afficheHotspotDetails). */
  export const clean_screen = function(type) {
  /** Bloc information générale */
  $('#js-nom-projet').text('');
  $('#js-key-analyse').text('').removeAttr('data-analyse-key');
  $('#js-clef-projet').text('');
  $('#js-version-release').text('').removeAttr('data-release');
  $('#js-version-snapshot').text('').removeAttr('data-snapshot');
  $('#js-version-autre').text('').removeAttr('data-autre data-label data-dataset');
  $('#js-version').text('');
  $('#js-date-version').text('').removeAttr('data-date-version');

  /** NoSonar / SuppressWarning / no-pmd / check-style */
  $('#js-no-sonar-total').text('')
    .removeAttr('data-s1309 data-nosonar data-no-pmd data-check-style data-java-no-sonar data-python-no-sonar data-php-no-sonar');
  $('#js-suppress-warning-modale').text('');
  $('#js-no-pmd-modale').text('');
  $('#js-check-style-modale').text('');
  $('#js-no-sonar-java-modale').text('');
  $('#js-no-sonar-python-modale').text('');
  $('#js-no-sonar-php-modale').text('');
  $('#tableau-liste-nosonar-detail').html('');

  /** To do */
  $('#js-todo-liste').text('').removeAttr('data-todo');
  $('#js-java').text('').removeAttr('data-java');
  $('#js-javascript').text('').removeAttr('data-javascript');
  $('#js-typescript').text('').removeAttr('data-typescript');
  $('#js-php').text('').removeAttr('data-php');
  $('#js-python').text('').removeAttr('data-python');
  $('#js-ruby').text('').removeAttr('data-ruby');
  $('#js-html').text('').removeAttr('data-html');
  $('#js-xml').text('').removeAttr('data-xml');
  $('#tableau-liste-detail').html('').removeAttr('data-liste-fichier');

  /** Logger */
  $('#js-logger-liste').text('');
  $('#js-logger-total').text('');
  $('#js-logger-info').text('').removeAttr('data-logger-info');
  $('#js-logger-warn').text('').removeAttr('data-logger-warn');
  $('#js-logger-error').text('').removeAttr('data-logger-error');
  $('#js-logger-debug').text('').removeAttr('data-logger-debug');
  $('#js-logger-info-pct').text('');
  $('#js-logger-warn-pct').text('');
  $('#js-logger-error-pct').text('');
  $('#js-logger-debug-pct').text('');
  $('#js-logger-info-bar').removeAttr('style');
  $('#js-logger-warn-bar').removeAttr('style');
  $('#js-logger-error-bar').removeAttr('style');
  $('#js-logger-debug-bar').removeAttr('style');
  $('#js-affiche-logger-detail').removeAttr('data-logger-breakdown data-logger-details');

  /** Actuator */
  $('#js-actuator-pastille')
    .removeClass('pastille-grise pastille-verte pastille-rouge')
    .addClass('pastille-grise')
    .removeAttr('data-actuator-maven-key data-actuator-json aria-label title');
  $('#js-actuator-message').text('');
  $('#tableau-actuator').html('');

  /** Distribution langage + mesures */
  $('#js-distribution-langage').html('');
  $('#js-nombre-ligne').text('').removeAttr('data-nombre-ligne');
  $('#js-nombre-ligne-de-code').text('').removeAttr('data-nombre-ligne-de-code');
  $('#js-nombre-fichier').text('').removeAttr('data-nombre-fichier');
  $('#js-nombre-classe').text('').removeAttr('data-nombre-classe');
  $('#js-nombre-fonction').text('').removeAttr('data-nombre-fonction');
  $('#js-nombre-statement').text('');

  /** Complexité */
  $('#js-complexity-ratio').text('');
  $('#js-cognitive-complexity-ratio').text('');
  $('#js-note-complexity').text('');
  $('#js-note-cognitive-complexity').text('');

  $('#js-coverage').text('').removeAttr('data-coverage');
  $('#js-ratio-dette-technique').text('')
    .removeClass('couleur-vert couleur-orange couleur-rouge couleur-bordeaux')
    .removeAttr('data-sqale-debt-ratio');
  $('#js-duplicated-lines-density').text('').removeAttr('data-duplicated-lines-density');
  $('#js-tests').text('').removeAttr('data-tests');
  $('#js-violations').text('').removeAttr('data-violations');
  $('#js-dette').text('').removeAttr('data-dette-minute');
  $('#js-dette-reliability').text('');
  $('#js-dette-vulnerability').text('');
  $('#js-dette-code-smell').text('');

  $('#js-nombre-bug-total').text('').removeAttr('data-nombre-bug');
  $('#js-nombre-bug-real').text('');
  $('#js-separator-bug').text('');
  $('#js-nombre-vulnerability').text('').removeAttr('data-nombre-vulnerability');
  $('#js-nombre-mauvaise-pratique').text('')
    .removeClass('couleur-rouge')
    .removeAttr('data-nombre-code-smell');
  $('#js-separator-code-smell').text('');
  $('#js-nombre-mauvaise-pratique-real').text('');

  $('#js-nombre-frontend').text('').removeAttr('data-nombre-frontend');
  $('#js-nombre-frontend-percent').text('');
  $('#js-nombre-backend').text('').removeAttr('data-nombre-backend');
  $('#js-nombre-backend-percent').text('');
  $('#js-nombre-autre').text('').removeAttr('data-nombre-autre');
  $('#js-nombre-autre-percent').text('');
  $('#js-nombre-inconnu').text('').removeAttr('data-nombre-inconnu');
  $('#js-nombre-inconnu-percent').text('');

  $('#js-nombre-violation-bloquant').text('').removeAttr('data-nombre-violations-bloquant');
  $('#js-nombre-violation-critique').text('').removeAttr('data-nombre-violations-critique');
  $('#js-nombre-violation-info').text('').removeAttr('data-nombre-violations-info');
  $('#js-nombre-violation-majeur').text('').removeAttr('data-nombre-violations-majeur');
  $('#js-nombre-violation-mineur').text('').removeAttr('data-nombre-violations-mineur');

  $('#js-note-reliability').text('');
  $('#js-note-security').text('');
  $('#js-note-sqale').text('');
  $('#js-note-menace-potentielle').text('');

  /** Phase L.7 — 6 indicateurs supplémentaires */
  $('#js-alert-status').text('');
  $('#js-note-coverage').text('');
  $('#js-note-duplication').text('');
  $('#js-comment-lines').text('');
  $('#js-comment-lines-density').text('');
  $('#js-note-comment-lines').text('');
  $('#js-test-errors').text('');
  $('#js-test-failures').text('');
  $('#js-skipped-tests').text('');
  $('#js-test-success-density').text('');
  $('#js-accepted-issues').text('');
  $('#js-false-positive-issues').text('');

  /** Menaces potentielles (hotspots) — le conteneur est vidé/reconstruit par
   * afficheHotspotDetails(), les cellules to-review/reviewed n'ont donc pas
   * besoin d'être ciblées individuellement. */
  $('#js-tableau-menace-potentielle').html('');
  $('#js-menace-potentielle-totale').text('').removeAttr('data-menace-potentielle-totale');

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
