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


import '../css/reset.css';

/** Import de la classe de gestion de la qualité du mot de passe */
import './app-password.js';
// app-password.min.css --> voir le fichier change.css

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

/** On importe les constantes */
import { zero, huit, cinquanteDeux  } from './constante.js';

/**
 * checkOkSvg
 *
 * @member [type]
 */
const checkOkSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -46 417.813 417" class="info-check-ok-svg">
<path d="M159.988 318.582c-3.988 4.012-9.43 6.25-15.082 6.25s-11.094-2.238-15.082-6.25L9.375
198.113c-12.5-12.5-12.5-32.77 0-45.246l15.082-15.086c12.504-12.5 32.75-12.5 45.25 0l75.2 75.203L348.104 9.781c12.504-12.5 32.77-12.5 45.25 0l15.082 15.086c12.5 12.5 12.5 32.766 0
45.246zm0 0"/></svg>`;

/**
 * checkKoSvg
 *
 * @member [type]
 */
const checkKoSvg = `<svg xmlns="http://www.w3.org/2000/svg" overflow="visible" x="620" y="239.5" viewBox="0 0 64 64" class="info-check-ko-svg">
<g class="layer" pointer-events="all">
<path d="M45.413 32l12.914-13.593c3.565-3.753 3.565-9.84 0-13.593-3.566-3.753-9.348-3.753-12.914 0L32.5 18.407 19.587
4.814c-3.566-3.753-9.348-3.753-12.914 0s-3.565 9.84 0 13.593L19.587 32 6.673 45.593c-3.565 3.753-3.565 9.84 0 13.593 3.566 3.753 9.348
3.753 12.914 0L32.5 45.593l12.913 13.593c3.566 3.753 9.348 3.753 12.914 0 3.565-3.753 3.565-9.84 0-13.593L45.413 32z"/></g></svg>`;

/** Nettoyage de formulaire */
$('#reset_password_form_plainPassword_first').val('');
$('#reset_password_form_plainPassword_second').val('');
$('#message-erreur-valider').val('');

/** Ajout du label pour le mot de passe. On ne fait pas de contrôle. */
$('#reset_password_form_ancienMotDePasse').on('keyup', function(){
  if (this.value !== '') {
    $('label[for="reset_password_form_ancienMotDePasse"]').addClass('affiche');
  } else {
    $('label[for="reset_password_form_ancienMotDePasse"]').removeClass('affiche');
  }
});

/** Ajout du label pour le mot de passe */
$('#reset_password_form_plainPassword_first').on('keyup', function(){
  if (this.value !== '') {
    $('label[for="reset_password_form_plainPassword_first"]').addClass('affiche');
  } else {
    $('label[for="reset_password_form_plainPassword_first"]').removeClass('affiche');
  }
});

  /** On contrôle la qualité du mot de passe  */
  $('#reset_password_form_plainPassword_first').password({
    showPercent: true, showText: true, animate: true, animateSpeed: 'fast',
    field: false, fieldPartialMatch: true, minimumLength: 1, useColorBarImage: true,
  });

/** Ajout du label pour le re-motdepasse */
$('#reset_password_form_plainPassword_second').on('keyup', function(){
  if (this.value !== '') {
    $('label[for="reset_password_form_plainPassword_second"]').addClass('affiche');
  } else {
    $('label[for="reset_password_form_plainPassword_second"]').removeClass('affiche');
  }
});

/** Vérification du mot de passe */
$('#reset_password_form_plainPassword_first, #reset_password_form_plainPassword_second').on(
  'keyup', ()=>{
    const password=$('#reset_password_form_plainPassword_first').val();
    const repassword=$('#reset_password_form_plainPassword_second').val();

    if (password.length>=huit && password.length<=cinquanteDeux) {
        $('#reset-info-check-password').html(checkOkSvg);
      } else if (password.length===zero) {
          $('#reset-info-check-password').html('');
        } else {
          $('#reset-info-check-password').html(checkKoSvg);
        }

      if ( repassword.length>=huit && repassword.length<=cinquanteDeux ) {
        $('#reset-info-check-repassword').html(checkOkSvg);
        } else if (repassword.length===zero) {
        $('#reset-info-check-repassword').html('');
        } else {
        $('#reset-info-check-repassword').html(checkKoSvg);
      }
  });

/** Activation du bouton d'enregistrement */
$('#reset_password_form_plainPassword_second').on('focus', function(){
  $('#valider-formulaire-enregistrement').removeClass('disabled-custom');
});

  /** Vérification des informations du formulaire */
$('#valider-formulaire-enregistrement').on('click', async ()=>{
  const initialPasswordLength=$('#reset_password_form_ancienMotDePasse').val().length;
  const passwordLength=$('#reset_password_form_plainPassword_first').val().length;
  const repasswordLength=$('#reset_password_form_plainPassword_second').val().length;
  const passwordValue=$('#reset_password_form_plainPassword_first').val();
  const repasswordValue=$('#reset_password_form_plainPassword_second').val();

if ( initialPasswordLength>0 && passwordLength>=8 && repasswordLength>=8 &&
    passwordValue===repasswordValue) {
      $('#message-erreur-valider').html('');
      $('#valider-formulaire-enregistrement').attr('type', 'submit');
      const link = document.getElementById('valider-formulaire-enregistrement');
      link.click()
  } else {
    const message='<ul><li>[001] - Le formulaire contient des erreurs !!!</li></ul>';
    $('#message-erreur-valider').html(message);
  }
});

/*
  Switch actions
*/
$('.unmask0, .unmask1, .unmask2').on('click', function(){

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
    sessionStorage.setItem('info','Stupid IE security will not allow this !!!')
  }
}
