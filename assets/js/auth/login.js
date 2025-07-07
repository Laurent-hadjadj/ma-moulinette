/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../styles/common/common.css';
import '../../styles/common/police.css';
import '../../styles/auth/login.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../common/foundation.js';

import browserUpdate from 'browser-update';

/* Vérification du navigateur */
const configurationOptions = {
  required: { i: 11, e: -3, c: -3, f: -3, o: -3, s: -3 },
  insecure: true,
  unsupported: true,
  api: 2025.7,
  reminder: 24 };

/* Chargement de browser update */
browserUpdate([configurationOptions]);

/* Execution automatique */
$(function () {
  const showClass = 'show';

  $('input').on('checkval', function () {
    const label = $(this).prev('label');
    if (this.value !== '') {
      label.addClass(showClass);
    } else {
      label.removeClass(showClass);
    }
  }).on('keyup', function () {
    $(this).trigger('checkval');
  });
});

/*
  Switch actions
*/
$('.afficher-masquer').on('click', function () {
  const $input = $(this).prev('input');
  const $button = $(this);
  const isPassword = $input.attr('type') === 'password';
  // Changer le type
  $input.attr('type', isPassword ? 'text' : 'password');

  // Mise à jour des attributs d'accessibilité
  $button.attr('aria-pressed', !isPassword);
  $button.attr('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
  $button.text(isPassword ? 'Masquer' : 'Afficher');

  return false;
});
