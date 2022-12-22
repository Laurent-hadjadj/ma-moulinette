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

// Intégration de jquery
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

/**
 * description
 * On affiche/desactive les indicateurs de variation
 */
$('#js-oui-non').on('click', function () {
  if ($('#js-oui-non').is(':checked')===true) {
    $('.up, .down, .equal').removeClass('hide');
  }
  /** On en fait deux pour être certain de capter l'evenenent */
  if ($('#js-oui-non').is(':checked')===false) {
    if ($('.up, .down, .equal').hasClass('hide')===false) {
      $('.up, .down, .equal').addClass('hide');
    }
  }
});


/**
 * description
 * On affiche les indicateurs de projet de référence
 */
$('.js-affiche-projet-reference').on('click', function () {
  $('#modal-projet-reference').foundation('open');
});
