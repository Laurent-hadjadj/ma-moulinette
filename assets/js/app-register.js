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
