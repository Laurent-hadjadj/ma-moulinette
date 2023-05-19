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

import './foundation.js';

/** On importe les constantes */
import {contentType} from './constante.js';

/* On importe les paramètres serveur. */
import {serveur} from './properties.js';


/**
 * [Description for modifierStatut]
 *
 * @param mixed etat
 *
 * @return [type]
 *
 * Created at: 17/05/2023, 15:55:05 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const modifierStatut = function(statut, categorie) {
  /** on récupère les préférences */
  const data={ statut, categorie, mode: 'null' }
  const options = {
     url: `${serveur()}/api/preference/statut`, type: 'POST',
          dataType: 'json', data:  JSON.stringify(data), contentType };
  console.log(options);
  return $.ajax(options).then();
}

/**
 * [Description for bookmark]
 *
 * @return [type]
 *
 * Created at: 15/05/2023, 13:15:06 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const bookmark=function(){
  /** on récupère les préférences */
  const data={ categorie: 'bookmark' }
  const options = {
    url: `${serveur()}/api/preference/categorie`, type: 'GET',
          dataType: 'json', data, contentType };

  return $.ajax(options).then(r=> {
    $('#js-modal-bookmark-statut').html(`<span class="option-false"><strong>Désactivée.</strong></span>`);
    /** On a un bookmark */
    if (r.statut.bookmark && r['bookmark'].length>0){
      $('#js-modal-bookmark-statut').html(`<span class="option-true"><strong>Activée.</strong></span>`);
      $('#js-modal-bookmark-nom').html(`<span>${r.bookmark}</span>`);
    } else {
      if (r['bookmark'].length>0) {
        $('#js-modal-bookmark-nom').html(`<span>${r.bookmark}</span>`);
      } else {
        $('#js-modal-bookmark-nom').html(`<span>AUNCUN</span>`);
      }
    }

    /** On ouvre la fenêtre modal */
    $('#modal-bookmark').foundation('open');

  });
}

/**
 * [Description for favori]
 *
 * @return [type]
 *
 * Created at: 18/05/2023, 16:59:18 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const favori=function(){
  /** on récupère les préférences */
  const data={ categorie: 'favori' }
  const options = {
    url: `${serveur()}/api/preference/categorie`, type: 'GET',
          dataType: 'json', data, contentType };

  return $.ajax(options).then(r=> {
    $('#js-modal-favori-statut').html(`<span class="option-false"><strong>Désactivée.</strong></span>`);

    /** On a un favori */
    if (r.statut.favori && r['favori'].length>0){
      $('#js-modal-favori-statut').html(`<span class="option-true"><strong>Activée.</strong></span>`);
      /** On construit la liste des favoris */
      $('#tableau-liste-favori').html('');
      let n=1, zero='';

      r.favori.forEach(l => {
        if (n<10) {
          zero='0';
        }

        $('#tableau-liste-favori').append('<tr>');
        $('#tableau-liste-favori').append('<td class="preference-compteur-tableau">'+zero+n+'</td>');
        $('#tableau-liste-favori').append('<td class="preference-ligne-tableau">'+l+'</td>');
        $('#tableau-liste-favori').append('</tr>');
        n=n+1;
      });
    } else {
      if (r['favori'].length>0) {
        /** On construit la liste des favoris */
      $('#tableau-liste-favori').html('');
      let n=1, zero='';
      r.favori.forEach(l => {
        if (n<10) {
          zero='0';
        }
        $('#tableau-liste-favori').append('<tr>');
        $('#tableau-liste-favori').append('<td class="preference-compteur-tableau">'+zero+n+'</td>');
        $('#tableau-liste-favori').append('<td class="preference-ligne-tableau">'+l+'</td>');
        $('#tableau-liste-favori').append('</tr>');
        n=n+1;
      });
      }
    }

    /** On ouvre la fenêtre modal */
    $('#modal-favori').foundation('open');

  });
}

/**
 * [Description for projet]
 *
 * @return [type]
 *
 * Created at: 19/05/2023, 10:48:28 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const projet=function(){
  /** on récupère les préférences */
  const data={ categorie: 'projet' }
  const options = {
    url: `${serveur()}/api/preference/categorie`, type: 'GET',
          dataType: 'json', data, contentType };

  return $.ajax(options).then(r=> {
    $('#js-modal-projet-statut').html(`<span class="option-false"><strong>Désactivée.</strong></span>`);

    /** On a un favori */
    if (r.statut.projet && r['projet'].length>0){
      $('#js-modal-projet-statut').html(`<span class="option-true"><strong>Activée.</strong></span>`);
      /** On construit la liste des projets */
      $('#tableau-liste-projet').html('');
      let n=1, zero='';

      r.projet.forEach(l => {
        if (n<10) {
          zero='0';
        }

        $('#tableau-liste-projet').append('<tr>');
        $('#tableau-liste-projet').append('<td class="preference-compteur-tableau">'+zero+n+'</td>');
        $('#tableau-liste-projet').append('<td class="preference-ligne-tableau">'+l+'</td>');
        $('#tableau-liste-projet').append('</tr>');
        n=n+1;
      });
    } else {
      if (r['projet'].length>0) {
        /** On construit la liste des projets */
      $('#tableau-liste-projet').html('');
      let n=1, zero='';
      r.projet.forEach(l => {
        if (n<10) {
          zero='0';
        }
        $('#tableau-liste-projet').append('<tr>');
        $('#tableau-liste-projet').append('<td class="preference-compteur-tableau">'+zero+n+'</td>');
        $('#tableau-liste-projet').append('<td class="preference-ligne-tableau">'+l+'</td>');
        $('#tableau-liste-projet').append('</tr>');
        n=n+1;
      });
      }
    }

    /** On ouvre la fenêtre modal */
    $('#modal-projet').foundation('open');

  });
}

/*************** EVENEMENT *************/
/**
 * Description
 * On active ou pas le bookmark
 */
$('#js-switch-bookmark').on('click', ()=> {
  const ouinon = $('#js-switch-bookmark').is(':checked');
  if (ouinon===true) {
    /** on active l'option */
    modifierStatut(true, 'bookmark');
  } else {
    /** on désactive l'option */
    modifierStatut(false, 'bookmark');
  }
});

/**
 * Description
 * On active ou pas les favoris
 */
$('#js-switch-favori').on('click', ()=> {
  const ouinon = $('#js-switch-favori').is(':checked');
  if (ouinon===true) {
    /** on active l'option */
    modifierStatut(true, 'favori');
  } else {
    /** on désactive l'option */
    modifierStatut(false, 'favori');
  }
});

/**
 * Description
 * On active ou pas les favoris
 */
$('#js-switch-projet').on('click', ()=> {
  const ouinon = $('#js-switch-projet').is(':checked');
  if (ouinon===true) {
    /** on active l'option */
    modifierStatut(true, 'projet');
  } else {
    /** on désactive l'option */
    modifierStatut(false, 'projet');
  }
});

/*************** Main du programme **************/

/** Affiche les informations de la catégorie */
$('.js-preference-information').on('click', e=> {
  /** On récupère l'ID du bouton. */
  switch(e.currentTarget.id) {
    case 'js-projet':
      projet();
      break;
    case 'js-favori':
      favori();
      break;
    case 'js-bookmark':
      bookmark();
      break;
    default:
      console.log("Je n'ai pas trouvé la lumière.");
  }

});
