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

let checkOkSvg, checkKoSvg;

checkOkSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -46 417.813 417" class="info-check-ok-svg">';
checkOkSvg +='<path d="M159.988 318.582c-3.988 4.012-9.43 6.25-15.082 6.25s-11.094-2.238-15.082-6.25L9.375 198.113c-12.5-12.5-12.5-32.77 0-45.246l15.082-15.086c12.504-12.5 32.75-12.5 45.25 0l75.2 75.203L348.104 9.781c12.504-12.5 32.77-12.5 45.25 0l15.082 15.086c12.5 12.5 12.5 32.766 0 45.246zm0 0"/>';
checkOkSvg +='</svg>';

checkKoSvg = '<svg xmlns="http://www.w3.org/2000/svg" overflow="visible" x="620" y="239.5" viewBox="0 0 64 64" class="info-check-ko-svg">';
checkKoSvg +='<g class="layer" pointer-events="all"><path d="M45.413 32l12.914-13.593c3.565-3.753 3.565-9.84 0-13.593-3.566-3.753-9.348-3.753-12.914 0L32.5 18.407 19.587 4.814c-3.566-3.753-9.348-3.753-12.914 0s-3.565 9.84 0 13.593L19.587 32 6.673 45.593c-3.565 3.753-3.565 9.84 0 13.593 3.566 3.753 9.348 3.753 12.914 0L32.5 45.593l12.913 13.593c3.566 3.753 9.348 3.753 12.914 0 3.565-3.753 3.565-9.84 0-13.593L45.413 32z"/></g>';
checkKoSvg +='</svg>';

// Nettoyage de formulaire
$('#registration_form_courriel').val('');
$('#registration_form_plainPassword').val('');

// Affichage des libellés
$('#registration_form_nom').on('keyup', function () {
  if(this.value !== '') {
    $('label[for="registration_form_nom"]').addClass('show');
  } else {
    $('label[for="registration_form_nom"]').removeClass('show');
  }
});
$('#registration_form_prenom').on('keyup', function () {
  if(this.value !== '') {
    $('label[for="registration_form_prenom"]').addClass('prenom');
  } else {
    $('label[for="registration_form_prenom"]').removeClass('prenom');
  }
});
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

$('#registration_form_courriel').on('keyup', function()
  {
    /**
     * La longueur d'un mail est de 64+1+255
     * On verifie la plus part des erreurs de saisie avant d'envoyer au contrôleur
     * qui vérifira la conformité de l'adresse de courriel.
     */

    let domaine;
    const courriel=$('#registration_form_courriel').val();
    const arobase=courriel.indexOf('@');
    const point=courriel.indexOf('.');

    // Si le champ est vide
    if ( courriel.length === 0 ) {
      $('#register-info-check-courriel').html('');
      return;
    }

    // Si le permier caractère est @
    if (courriel.length === 1 && arobase === 0 ) {
      $('#register-info-check-courriel').html(checkKoSvg);
      return;
    }

    // Si on à un @ à la 65eme position
    if (courriel.length > 64 && arobase > 0 ){
      $('#register-info-check-courriel').html(checkKoSvg);
      return;
    }

    // Si on a pas de @ avant la 64eme position
    if (courriel.length < 64 && arobase < 0 ){
      $('#register-info-check-courriel').html(checkKoSvg);
      return;
    }

    // Si on a un @ avant 65eme posistion
    if (courriel.length < 64 && arobase > 0 ) {
      domaine=courriel.split('@');
      /* Si le tableau contient plus de 2 éléments on sort */
      if (domaine.length>2) {
        $('#register-info-check-courriel').html(checkKoSvg);
        return;
      }
      $('#register-info-check-courriel').html(checkOkSvg);
    }

    // si le domaine n'est pas correcte
    if (domaine[1].length==1 && point>1) {
      $('#register-info-check-courriel').html(checkKoSvg);
    }

    // On vérifie que le nom de domaine est correcte
    if (domaine[1].length==1 && point>1) {
      $('#register-info-check-courriel').html(checkKoSvg);
    }

    // On verifie que le domaine est <256
    const points=(domaine[1].match(/\./g)||[]).length
    if (points >1) {
      $('#register-info-check-courriel').html(checkKoSvg);
    }

});

// Vérification du mot de passe
$('#registration_form_plainPassword').on('keyup', function()
  {
    const password=$('#registration_form_plainPassword').val();

    if ( password.length === 0 ) { $('#register-info-check').html(''); }
    if ( password.length>0 && password.length<8 ) {
        $('#register-info-check-password').html('');
        $('#register-info-check').html('<span class="register-info-erreur">Taille Min 8.</span>'); }
    if ( password.length>53 ) {
        $('#register-info-check').html('<span class="profil-info-erreur">Taille Max 52.</span>'); }
    if ( password.length>7 && password.length<53 ) {
        $('#register-info-check').html('');
        $('#register-info-check-password').html(checkOkSvg);
      }
      else {
        $('#register-info-check-password').html(checkKoSvg);} }
  );

// Validation du choix de l'avatar
$('.thumbnail').on('click', function()
  {
    let id = $(this).attr("id");
    let theme=$('#'+id).data('theme');
    let image=$('#'+id).data('image');
    let path=theme+'/'+image;
    $('#ajouter-mon-avatar').prop('src', '/build/avatar/'+path+'.png');
    let data = document.getElementById('ajouter-mon-avatar');
    data.dataset.theme=theme;
    data.dataset.image=image;
    $('#registration_form_avatar').val(path+'.png')
    $('#mes-avatars').foundation('close');
  });
