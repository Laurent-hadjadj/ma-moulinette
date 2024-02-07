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
import '../css/preference.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import 'select2';
import 'select2/dist/js/i18n/fr.js';

import './foundation.js';
import './app-authentification-details.js';

/** On importe les constantes */
import {contentType} from './constante.js';

/* On importe les paramètres serveur. */
import {serveur} from './properties.js';

/**
 * [Description for match]
 * Propriétés du selecteur de recherche.
 *
 * @param mixed params
 * @param mixed data
 *
 * @return [type]
 *
 * Created at: 28/03/2023, 19:43:50 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const match=function(params, data) {
  if ($.trim(params.term) === '') {
    return data;
  }
  if (typeof data.text === 'undefined') {
    return null;
  }

  if (data.text.indexOf(params.term) > -1) {
    const modifiedData = $.extend({}, data, true);
    modifiedData.text += ' (trouvé)';
    return modifiedData;
  }
  return null;
};

/**
 * [Description for selectProjet]
 * Création du selecteur de projet.
 *
 * @return [type]
 *
 * Created at: 28/03/2023, 19:41:10 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const selectProjet=function() {
  let data={};
  /** On récupère le filtre */
  const nombreEquipe=$("input[id^='check-']").length;
  /** mode normal = null, en mode test = @TEST */
  data[0]='null';
  for (let i=1; i<parseInt(nombreEquipe,10)+1; i++){
    if ($(`#check-${i}`).prop('checked')) {
      data[i]= $(`#check-${i}`).data('equipe');
    }
  }

  const options = {
    url: `${serveur()}/api/filtre/projet`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };
    return $.ajax(options)
    .then(function (r) {
      console.log(r);
      if (r['liste'].length != 0)
      {
        $('.js-projet').select2({
          matcher: match,
          placeholder: 'Cliquez pour ouvrir la liste',
          allowClear: false,
          tags: true,
          width: '100%',
          minimumInputLength: 2,
          minimumResultsForSearch: 20,
          language: 'fr',
          data: r.liste});

        /** On a trouvé des projets pour l'équipe. */
        $('.js-projet').prop('disabled', false);
        $('#container-liste-projet').removeClass('hide');
        $('#container-bouton-liste-projet').removeClass('hide');
        $('#container-nom-liste-projet').removeClass('hide');

        /** On affiche un message */
        if ($('#callout-message').hasClass('hide')) {
          $('#callout-message').addClass('success');
          $('#callout-message').removeClass('hide')
        } else {
          $('#callout-message').removeClass('alert')
          $('#callout-message').addClass('success');
        }
        $('.js-message').html(r.message);
      } else {
        /** On a pas trouvé de projet pour l'équipe. */
        $('.js-projet').prop('disabled', true);
        $('#container-liste-projet').addClass('hide');
        $('#container-bouton-liste-projet').addClass('hide');
        $('#container-nom-liste-projet').addClass('hide');
        /** On affiche un message */
        if ($('#callout-message').hasClass('hide')) {
          $('#callout-message').addClass('alert');
          $('#callout-message').removeClass('hide')
        } else {
          $('#callout-message').removeClass('success')
          $('#callout-message').addClass('alert');
        }
        $('.js-message').html(r.message);
    }
  });
};

/** on active la liste */
$('.js-preference-ajouter-favoris-enable').on('click', ()=> {

  /** On récupère le filtre */
  const nombreEquipe=$("input[id^='check-']").length;

  /** Si on a pas d'équipe */
  if (nombreEquipe===0) { return; }

  /** On regarde si au moins une équipe est choisi */
  let nombreEquipeChecked=0;
  for (let i=1; i<parseInt(nombreEquipe,10)+1; i++){
    if ($(`#check-${i}`).prop('checked')) {
      nombreEquipeChecked =+1;
    }
  }

  if(nombreEquipeChecked===0) {
    const message="<strong>[Préference-004]</strong> Vous devez choisir au moins une équipe !";
    /** On affiche un message */
    if ($('#callout-message').hasClass('hide')) {
      $('#callout-message').addClass('alert');
      $('#callout-message').removeClass('hide')
    } else {
      $('#callout-message').removeClass('success')
      $('#callout-message').addClass('alert');
    }
    if ($('.js-projet').prop('disabled')===false) {
      $('.js-projet').prop('disabled', true);
      $('#container-liste-projet').addClass('hide');
      $('#container-bouton-liste-projet').addClass('hide');
      $('#container-nom-liste-projet').addClass('hide');
    }
    $('.js-message').html(message);
    return;
  } else {
    /* On met à jour la liste des projets disponibles */
    selectProjet();
  }
});

/*************** Main du programme **************/
/** On efface la liste */
$('.js-preference-effacer').on('click', ()=> {
  /** On éfface le nom de la liste */
  $('#nom-liste').val('');
  /** On éfface la liste de projet */
  $('.js-projet').val(null).trigger('change');
});

/** On enregistre la liste */
$('.js-preference-valider').on('click', ()=> {
  /** On récupère le liste de favoris */
  const liste=$('.js-projet').select2('data');
  console.log(liste[0].text);
  /** On récupère le nom de la liste de favoris */
  const nom=$('#nom-liste').val();
  console.log(nom);
});
