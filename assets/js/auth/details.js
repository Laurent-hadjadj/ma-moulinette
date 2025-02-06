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

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

/** On importe les constantes */
import { http_200, http_400, http_500, un, contentType } from '../common/constante.js';

/* On importe les paramètres serveur. */
import {serveur} from '../common/properties.js';

/**
 * description
 * On active ou non la mise à jour du mot de passe.
 *
 * @type {"#js-identifiant-oui-non"}
 */
$('#js-identifiant-oui-non').on('click', function () {
  let data={}, init=0;
  /** On efface les messages */
  $('#mise-a-jour-message').html('');

  const oui_non = $('#js-identifiant-oui-non').is(':checked');

  /** Par défaut on bloque la mise à jour du mot de passe. */
  data={ 'init': 0 };
  if (oui_non===true) {
    data={ 'init': 1 };
    init=1
  }

  const options = {
    url: `${serveur()}/api/mot-de-passe/mise-a-jour`,
          type: 'POST', dataType: 'json', data: JSON.stringify(data), contentType
  };

  return new Promise(resolve => {
      $.ajax(options).then(t=> {
        if (t.code===http_400 || t.code===http_500){
          $('#mise-a-jour-message').html(t.message)
          return;
        }
        const message='<span class="color-rouge">Vous devez vous reconnecter pour changer votre mot de passe.</span>';
        if (t.code===http_200) {
          const r=document.getElementById('js-identifiant-oui-non');
          r.dataset.init=init;
        }
        if (init===1) {
          $('#mise-a-jour-message').html(message);
        } else {
          $('#mise-a-jour-message').html('');
        }
        resolve();
      });
    });
});

/** On récupère la valeur de data-init et on met à jour le switch */
const r=document.getElementById('js-identifiant-oui-non');
const init=r.dataset.init;
  if (init>=un) {
    $('#js-identifiant-oui-non').trigger('click');
  }

  /** On efface les messages */
  $('#mise-a-jour-message').html('');
