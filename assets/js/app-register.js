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

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';

/** On importe les constantes */
import { zero, deux, huit, trenteDeux, cinquanteDeux  } from './constante.js';

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
    $('label[for="registration_form_prenom"]').addClass('prenom');
  } else {
    $('label[for="registration_form_prenom"]').removeClass('prenom');
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

/** Ajout du label pour le re-motdepasse */
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
    const repassword=$('#registration_form_plainPassword_second').val();

    if (password.length>=huit && password.length<=cinquanteDeux) {
        $('#register-info-check-password').html(checkOkSvg);
      } else if (password.length===zero) {
          $('#register-info-check-password').html('');
        } else {
          $('#register-info-check-password').html(checkKoSvg);
        }

      if ( repassword.length>=huit && repassword.length<=cinquanteDeux ) {
        $('#register-info-check-repassword').html(checkOkSvg);
        } else if (repassword.length===zero) {
        $('#register-info-check-repassword').html('');
        } else {
        $('#register-info-check-repassword').html(checkKoSvg);
      }
  });

/** Validation du choix de l'avatar */
$('.thumbnail').on('click', function(){
    const id = $(this).attr('id');
    const theme=$('#'+id).data('theme');
    const image=$('#'+id).data('image');
    const path=`${theme}/${image}`;

    $('#ajouter-mon-avatar').prop('src', `/build/avatar/${path}.png`);
    const data = document.getElementById('ajouter-mon-avatar');
    data.dataset.theme=theme;
    data.dataset.image=image;
    $('#registration_form_avatar').val(path+'.png');
    $('#mes-avatars').foundation('close');
  });

/** Activation du bouton d'enregistrement */
$('#registration_form_plainPassword_second').on('focus', function(){
  $('#valider-formulaire-enregistrement').removeClass('disabled');
});

  /** Vérification des informations du formulaire */
$('#valider-formulaire-enregistrement').on('click', async ()=>{
  const nomLength=$('#registration_form_nom').val().length;
  const prenomLength=$('#registration_form_prenom').val().length;
  const courrielLength=$('#registration_form_courriel').val().length;
  const courrielValue = document.getElementById("registration_form_courriel");
  const passwordLength=$('#registration_form_plainPassword_first').val().length;
  const repasswordLength=$('#registration_form_plainPassword_second').val().length;
  const passwordValue=$('#registration_form_plainPassword_first').val();
  const repassordValue=$('#registration_form_plainPassword_second').val();

if (nomLength>=2 && prenomLength>=2 && courrielLength>=5 &&
    passwordLength>=8 && repasswordLength>=8 &&
    passwordValue===repassordValue &&
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
    console.log('Stupid IE security will not allow this')
  }
}
