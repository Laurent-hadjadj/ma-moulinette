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

/** Import des dépendances */
import '../css/batch.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

/** On importe les constatntes */
import { mille } from './constante';

/** On gére l'affichage des jobs */
const jsAutomatique = '.js-automatique';
const jsManuel = '.js-manuel';

$(jsAutomatique).on('click', function() {
  if ($(jsAutomatique).hasClass('active')) {
    $('.js-automatique').removeClass('active').addClass('bouton-automatique');
    $('.automatique').hide(mille);
  } else {
    $(jsAutomatique).removeClass('bouton-automatique').addClass('active');
    $('.automatique').show(mille);
  }
});

$(jsManuel).on('click', function() {
  if ($(jsManuel).hasClass('active')) {
    $(jsManuel).removeClass('active').addClass('bouton-manuel');
    $('.manuel').hide(mille);
  } else {
    $(jsManuel).removeClass('bouton-manuel').addClass('active');
    $('.manuel').show(mille);
  }
});
