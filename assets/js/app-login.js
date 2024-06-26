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
import '../css/login.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

import browserUpdate from 'browser-update';

/* Vérification du navigateur */
const configurationOptions = {
  required: { i: 11, e: -3, c: -3, f: -3, o: -3, s: -3 },
  insecure: true,
  unsupported: true,
  api: 2024.2,
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
$('.afficher-masquer').on('click', function(){

  if($(this).prev('input').attr('type') == 'password')
    changeType($(this).prev('input'), 'text');
  else
    changeType($(this).prev('input'), 'password');
  return false;
});

function changeType(x, type) {
  if(x.prop('type') == type)
  return x; //That was easy.
  try {
    return x.prop('type', type); //Stupid IE security will not allow this
  } catch(e) {
    console.log('Stupid IE security will not allow this')
  }
}
