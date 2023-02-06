/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

import '../css/cosui.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

const ouiNon='#js-oui-non';
const upDownEqual='.up, .down, .equal';

/**
 * description
 * On affiche/desactive les indicateurs de variation
 */
/** Faux positif : sonarLint(javascript:S1192) */
$(ouiNon).on('click', function () {
  if ($(ouiNon).is(':checked')===true) {
    $(upDownEqual).removeClass('hide');
  }

  /** On en fait deux pour être certain de capter l'evenenent */
  if ( $(ouiNon).is(':checked') === false && $(upDownEqual).hasClass('hide') === false ) {
      $(upDownEqual).addClass('hide');
    }
});


/**
 * description
 * On affiche les indicateurs de projet de référence
 */
$('.js-affiche-projet-reference').on('click', function () {
  $('#modal-projet-reference').foundation('open');
});
