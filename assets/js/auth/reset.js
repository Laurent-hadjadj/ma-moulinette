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
import '../../styles/auth/reset.css';
import '../../styles/auth/password.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../common/foundation.js';

/** Import de la classe de gestion de la qualité du mot de passe */
import './password.js'

/** On importe les constantes */
import { zero, huit, cinquanteDeux  } from '../common/constante.js';

/**
 * checkOkSvg
 *
 * @member void
 */
const checkOkSvg = `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewbox="0 0 32 32" class="info-check-ok-svg"><title>Valide</title><desc>Vérification OK.</desc><path d="M12.36 31.31c-.3.39-.71.61-1.14.61s-.84-.22-1.14-.61L.96 19.51c-.95-1.22-.95-3.21 0-4.43L2.1 13.6c.95-1.22 2.48-1.22 3.43 0l5.69 7.36L26.6 1.06c.95-1.22 2.48-1.22 3.43 0l1.14 1.48c.95 1.22.95 3.21 0 4.43L12.36 31.3zm0 0"/></svg>`;

/**
 * checkKoSvg
 *
 * @member void
 */
const checkKoSvg = `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewbox="0 0 32 32" class="info-check-ko-svg"><title>IncorrecteValide</title><desc>Vérification KO.</desc><path d="M23.17 15.99l7.15-7.12a5.028 5.028 0 000-7.12c-1.97-1.96-5.17-1.96-7.15 0l-7.14 7.12-7.14-7.12c-1.97-1.96-5.17-1.96-7.15 0s-1.97 5.15 0 7.12l7.15 7.12-7.15 7.12a5.028 5.028 0 000 7.12c1.97 1.96 5.17 1.96 7.15 0l7.14-7.12 7.14 7.12c1.97 1.96 5.17 1.96 7.15 0a5.028 5.028 0 000-7.12l-7.15-7.12z"/></svg>`;

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

/** Ajout du label pour le re-mot-de-passe */
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
    const rePassword=$('#reset_password_form_plainPassword_second').val();

    if (password.length>=huit && password.length<=cinquanteDeux) {
        $('#reset-info-check-password').html(checkOkSvg);
      } else if (password.length===zero) {
          $('#reset-info-check-password').html('');
        } else {
          $('#reset-info-check-password').html(checkKoSvg);
        }

      if ( rePassword.length>=huit && rePassword.length<=cinquanteDeux ) {
        $('#reset-info-check-re-password').html(checkOkSvg);
        } else if (rePassword.length===zero) {
        $('#reset-info-check-re-password').html('');
        } else {
        $('#reset-info-check-re-password').html(checkKoSvg);
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
  const rePasswordLength=$('#reset_password_form_plainPassword_second').val().length;
  const passwordValue=$('#reset_password_form_plainPassword_first').val();
  const rePasswordValue=$('#reset_password_form_plainPassword_second').val();

if ( initialPasswordLength>0 && passwordLength>=8 && rePasswordLength>=8 &&
    passwordValue===rePasswordValue) {
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
$('.unmask0, .unmask1, .unmask2').on('click', function () {
  const $input = $(this).prev('input');
  const $button = $(this);
  const isPassword = $input.attr('type') === 'password';

  // Changer le type d'input
  $input.attr('type', isPassword ? 'text' : 'password');

  // Accessibilité et retour utilisateur
  $button.attr('aria-pressed', !isPassword);
  $button.attr('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
  $button.text(isPassword ? 'Masquer' : 'Afficher');

  return false;
});
