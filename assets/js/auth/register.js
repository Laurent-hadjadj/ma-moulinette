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

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../styles/common/common.css';
import '../../styles/common/police.css';
import '../../styles/auth/register.css';
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
import { zero, deux, huit, trenteDeux, cinquanteDeux  } from '../common/constante.js';

/**
 * checkOkSvg
 *
 * @member [type]
 */
const checkOkSvg = `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewbox="0 0 32 32" class="info-check-ok-svg"><title>Valide</title><desc>Vérification OK.</desc><path d="M12.36 31.31c-.3.39-.71.61-1.14.61s-.84-.22-1.14-.61L.96 19.51c-.95-1.22-.95-3.21 0-4.43L2.1 13.6c.95-1.22 2.48-1.22 3.43 0l5.69 7.36L26.6 1.06c.95-1.22 2.48-1.22 3.43 0l1.14 1.48c.95 1.22.95 3.21 0 4.43L12.36 31.3zm0 0"/></svg>`;

/**
 * checkKoSvg
 *
 * @member [type]
 */
const checkKoSvg = `<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewbox="0 0 32 32" class="info-check-ko-svg"><title>IncorrecteValide</title><desc>Vérification KO.</desc><path d="M23.17 15.99l7.15-7.12a5.028 5.028 0 000-7.12c-1.97-1.96-5.17-1.96-7.15 0l-7.14 7.12-7.14-7.12c-1.97-1.96-5.17-1.96-7.15 0s-1.97 5.15 0 7.12l7.15 7.12-7.15 7.12a5.028 5.028 0 000 7.12c1.97 1.96 5.17 1.96 7.15 0l7.14-7.12 7.14 7.12c1.97 1.96 5.17 1.96 7.15 0a5.028 5.028 0 000-7.12l-7.15-7.12z"/></svg>`;

/** Nettoyage de formulaire */
$('#registration_form_plainPassword_first').val('');
$('#registration_form_plainPassword_second').val('');
$('#message-erreur-valider').val('');

/** Ajout du label pour le nom */
$('#registration_form_nom').on('keyup', function(){
  const nomLength=$('#registration_form_nom').val().length;

  if (this.value !== '') {
    $('label[for="registration_form_nom"]').addClass('show');
  } else {
    $('label[for="registration_form_nom"]').removeClass('show');
  }

if (nomLength>=deux && nomLength<=trenteDeux) {
  $('#register-info-check-nom').html(checkOkSvg);
} else if (nomLength===zero) {
    $('#register-info-check-nom').html('');
  } else {
    $('#register-info-check-nom').html(checkKoSvg);
  }


});

/** Ajout du label pour le prénom */
$('#registration_form_prenom').on('keyup', function(){
  const prenomLength=$('#registration_form_prenom').val().length;

  if (this.value !== '') {
    $('label[for="registration_form_prenom"]').addClass('show-prenom');
  } else {
    $('label[for="registration_form_prenom"]').removeClass('show-prenom');
  }
  if (prenomLength>=deux && prenomLength<=trenteDeux) {
    $('#register-info-check-prenom').html(checkOkSvg);
  } else if (prenomLength===zero) {
      $('#register-info-check-prenom').html('');
    } else {
      $('#register-info-check-prenom').html(checkKoSvg);
    }
});

/** Ajout du label pour le courriel */
$('#registration_form_courriel').on('keyup', function(){
  if (this.value !== '') {
    $('label[for="registration_form_courriel"]').addClass('show');
  } else {
    $('label[for="registration_form_courriel"]').removeClass('show');
  }

  const courrielValue = document.getElementById("registration_form_courriel");
  if (courrielValue.checkValidity()) {
    $('#register-info-check-courriel').html(checkOkSvg);
  } else {
  $('#register-info-check-courriel').html(checkKoSvg);
  }
});

/** Ajout du label pour le mot de passe */
$('#registration_form_plainPassword_first').on('keyup', function(){
  if (this.value !== '') {
    $('label[for="registration_form_plainPassword_first"]').addClass('show');
  } else {
    $('label[for="registration_form_plainPassword_first"]').removeClass('show');
  }
});

  /** On contrôle la qualité du mot de passe  */
  $('#registration_form_plainPassword_first').password({
    showPercent: true, showText: true, animate: true, animateSpeed: 'fast', minimumLength: 8, useColorBarImage: true,
  });

/** Ajout du label pour le re-mot-de-passe */
$('#registration_form_plainPassword_second').on('keyup', function(){
  if (this.value !== '') {
    $('label[for="registration_form_plainPassword_second"]').addClass('show');
  } else {
    $('label[for="registration_form_plainPassword_second"]').removeClass('show');
  }
});

/** Vérification du mot de passe */
$('#registration_form_plainPassword_first, #registration_form_plainPassword_second').on(
  'keyup', ()=>{
    const password=$('#registration_form_plainPassword_first').val();
    const rePassword=$('#registration_form_plainPassword_second').val();

    if (password.length>=huit && password.length<=cinquanteDeux) {
        $('#register-info-check-password').html(checkOkSvg);
      } else if (password.length===zero) {
          $('#register-info-check-password').html('');
        } else {
          $('#register-info-check-password').html(checkKoSvg);
        }

      if ( rePassword.length>=huit && rePassword.length<=cinquanteDeux ) {
        $('#register-info-check-re-password').html(checkOkSvg);
        } else if (rePassword.length===zero) {
        $('#register-info-check-re-password').html('');
        } else {
        $('#register-info-check-re-password').html(checkKoSvg);
      }
  });

/** Validation du choix de l'avatar */
$('.thumbnail').on('click', function(){
    const id = $(this).attr('id');
    const theme=$(`#${id}`).data('theme');
    const image=$(`#${id}`).data('image');
    const src=$(`#${id}`).attr('src');
    const assets=`${theme}/${image}`;

    $('#ajouter-mon-avatar').prop('src', src);
    const data = document.getElementById('ajouter-mon-avatar');
    data.dataset.theme=theme;
    data.dataset.image=image;
    $('#registration_form_avatar').val(assets);
    $('#mes-avatars').foundation('close');
  });

/** Activation du bouton d'enregistrement */
$('#registration_form_plainPassword_second').on('focus', function(){
  $('#valider-formulaire-enregistrement').removeClass('disabled-custom');
});

  /** Vérification des informations du formulaire */
$('#valider-formulaire-enregistrement').on('click', async ()=>{
  const nomLength=$('#registration_form_nom').val().length;
  const prenomLength=$('#registration_form_prenom').val().length;
  const courrielLength=$('#registration_form_courriel').val().length;
  const courrielValue = document.getElementById("registration_form_courriel");
  const passwordLength=$('#registration_form_plainPassword_first').val().length;
  const rePasswordLength=$('#registration_form_plainPassword_second').val().length;
  const passwordValue=$('#registration_form_plainPassword_first').val();
  const rePassordValue=$('#registration_form_plainPassword_second').val();

if (nomLength>=2 && prenomLength>=2 && courrielLength>=5 &&
    passwordLength>=8 && rePasswordLength>=8 &&
    passwordValue===rePassordValue &&
    courrielValue.checkValidity()) {
      $('#message-erreur-valider').html('');
      $('#message-erreur-courriel').html('');
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
$('.unmask1, .unmask2').on('click', function(){
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
    sessionStorage.setItem('info', 'Stupid IE security will not allow this !!!')
  }
}
