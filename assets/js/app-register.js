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


import '../css/register.css';

// Intégration de jquery
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

console.log('Register : Chargement de webpack !');

$('#registration_form_courriel').val('');
$('#registration_form_plainPassword').val('');

$('#registration_form_courriel').on('keyup', function () {
  if(this.value !== '') {
    $('label[for="registration_form_courriel"]').addClass('show');
  } else {
    $('label[for="registration_form_courriel"]').removeClass('show');
  }
});
$('#registration_form_plainPassword').on('keyup', function () {
  if(this.value !== '') {
    $('label[for="registration_form_plainPassword"]').addClass('show');
  } else {
    $('label[for="registration_form_plainPassword"]').removeClass('show');
  }
});
