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


const poubelleSVG=`
<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="poubelle-svg">
  <path d="M187.1 1.1c-12.8 2.5-25.7 12.5-31.1 24.1-3.7 7.9-5 14.6-5 25.8v8.8l-29.2.5C91 60.7 88.9 61 77 66c-17.1 7.2-32.7 22.8-40 40-4.1 9.7-5.1 15.2-5.7 29.7l-.6 14.3h450.6l-.6-14.3c-.6-14.5-1.6-20-5.7-29.7-7.3-17.2-22.9-32.8-40-40-11.9-5-14-5.3-44.7-5.7l-29.3-.5V51c0-4.8-.5-11.1-1-14.1-3.4-18-17.9-32.5-35.9-35.9-6.6-1.2-130.6-1.2-137 .1zM323 32c6 3.1 8 7.8 8 19v9H181v-9c0-11 2-15.9 7.8-18.9 3.6-1.9 6-2 67-2.1 61.5 0 63.4.1 67.2 2zM62.6 183.7c.3 2.1 6.3 68.4 13.4 147.3 13.6 151.8 13.5 150.5 19.4 159.9 6.4 10 18 17.9 29.5 20.1 7.3 1.4 254.9 1.4 262.2 0 11.5-2.2 23.1-10.1 29.5-20.1 5.9-9.4 5.8-8.1 19.4-159.9 7.1-78.9 13.1-145.2 13.4-147.3l.4-3.7H62.2l.4 3.7zM176 212c4.3 2.2 6.5 5.5 7.4 11 1.1 6.9 9.9 215.6 9.2 218.5-1 4-6.9 9.3-11.2 10.1-8.3 1.5-16.7-4.5-17.8-12.8-.6-4.6-9.6-204.9-9.6-213.4 0-11.8 11.5-18.8 22-13.4zm87 0c2.6 1.3 4.7 3.4 6 6 2 3.9 2 5.7 2 113s0 109.1-2 113c-2.3 4.5-8 8-13 8s-10.7-3.5-13-8c-2-3.9-2-5.7-2-113s0-109.1 2-113c3.7-7.3 12.4-9.9 20-6zm89.3 1.3c6.6 5 6.6-2 1.3 116.6-2.6 59.2-5.2 109.2-5.7 111.3-1.8 6.9-9.9 11.8-17.4 10.4-4.2-.8-10.1-6.2-11.1-10.1-.7-2.9 8.1-211.6 9.2-218.5.9-5.4 3.5-9.1 7.9-11.2 5.2-2.5 11.4-1.9 15.8 1.5z"/>
</svg>`

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
 * Récupération de la liste et suppression d'un favori
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
    /** Par défaut on affiche pas de favoris */
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
        let ligne="";
        ligne=`<tr id="ligne-favori-${n}">
                <td class="preference-compteur-tableau">${zero}${n}</td>
                <td id="mavenkey-favori-${n}" class="preference-ligne-tableau">${l}</td>
                <td id="poubelle-favori-${n}" class="js-poubelle text-center">${poubelleSVG}</td>
              </tr>`;
        $('#tableau-liste-favori').append(ligne);
        n=n+1;
      });
    }

    /** On ouvre la fenêtre modal */
    $('#modal-favori').foundation('open');

    /** On récupère l'ID de la ligne que l'on veut supprimer */
    $('.js-poubelle').on('click', e=>{
      const tempoId=$(e.currentTarget).attr('id')
      const id=tempoId.split("-");
      $(`#ligne-favori-${id[2]}`).hide()
      /** On appel le service de suppresion du favori */
      const mavenKey=$(`#mavenkey-favori-${id[2]}`).text();
      const data={ mode: 'null', mavenKey }
      const options = {
        url: `${serveur()}/api/preference/favori/delete`, type: 'POST',
              dataType: 'json', data: JSON.stringify(data), contentType };
        $.ajax(options).then(r=>{console.log(r)});
    });
  });
}

/**
 * [Description for version]
 * Récupération de la liste et suppression d'un favori
 *
 * @return [type]
 *
 * Created at: 12/06/2023, 13:48:34 (Europe/Paris)
 * @author    Laurent HADJADJ <laurent_h@me.com>
 * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const version=function(){
  /** on récupère les préférences */
  const data={ categorie: 'version' }
  const options = {
    url: `${serveur()}/api/preference/categorie`, type: 'GET',
          dataType: 'json', data, contentType };

  return $.ajax(options).then(r=> {
    /** Par défaut on affiche pas de version */
    $('#js-modal-version-statut').html(`<span class="option-false"><strong>Désactivée.</strong></span>`);

    /** On a une version ? */
    let n=1, m, lignes, ligne, debut, fin;
    if (r.statut.version && r.version.length>0){
      $('#js-modal-version-statut').html(`<span class="option-true"><strong>Activée.</strong></span>`);

      $('.js-accordion').html('');
      r.version.forEach(o => {
        for (const [key, value] of Object.entries(o)) {
          debut=`
              <li class="accordion-item" data-accordion-item>
                <a href="#" class="accordion-title open-sans accordion-custom">${key}</a>
                <div class="accordion-content" data-tab-content>
                <table><thead><tr>
                  <th width="50" scope="col" class="open-sans text-left">N°</th>
                  <th scope="col" class="open-sans text-center">version</th>
                  <th scope="col" class="open-sans text-center">Action</th>
                </tr></thead><tbody class="open-sans">`;
          lignes='';
          m=1;
          value.forEach(e => {
            ligne=`
            <tr id="ligne-version-${n}${m}">
              <td class="preference-compteur-tableau">${m}</td>
              <td id="version-${n}${m}" class="preference-ligne-tableau" data-index="${parseInt(n,10)-1}" data-key="${key}">${e}</td>
              <td id="poubelle-version-${n}${m}" class="js-poubelle text-center">${poubelleSVG}</td>
            </tr>`;
            lignes=lignes+ligne;
            m=m+1;
          });

          fin=`</tbody></table></div></li>`;
          n=n+1;
          $('.js-accordion').append(debut+lignes+fin);
          }
      });
    }

    /** On ouvre la fenêtre modal */
    $('#modal-version').foundation('open');
    /** On initialise l'accordéon */
    $('.js-accordion').foundation('_init')

      /** On récupère l'ID de la ligne que l'on veut supprimer */
      $('.js-poubelle').on('click', e=>{
        const tempoId=$(e.currentTarget).attr('id')
        const id=tempoId.split("-");
        $(`#ligne-version-${id[2]}`).hide()

        /** On prépare les données avant l'appel Ajax */
        const mode='null'
        const t = document.getElementById(`version-${id[2]}`);
        const mavenKey=t.dataset.key;
        const index=t.dataset.index;
        const version=$(`#version-${id[2]}`).text();

        const data={ mode, index, mavenKey, version }
        const options = {
        /** On appel le service de suppresion du favori */
        url: `${serveur()}/api/preference/version/delete`, type: 'POST',
              dataType: 'json', data: JSON.stringify(data), contentType };
        console.log(options);
        $.ajax(options).then(r=>{console.log(r)});
    });
  });
}


/**
 * [Description for projet]
 * Modifier la liste des projets
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
 * On active ou pas les projets
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

/**
 * Description
 * On active ou pas les projets
 */
$('#js-switch-version').on('click', ()=> {
  const ouinon = $('#js-switch-version').is(':checked');
  if (ouinon===true) {
    /** on active l'option */
    modifierStatut(true, 'version');
  } else {
    /** on désactive l'option */
    modifierStatut(false, 'version');
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
    case 'js-version':
      version();
    break;
    default:
      console.log("Je n'ai pas trouvé la lumière.");
  }

});
