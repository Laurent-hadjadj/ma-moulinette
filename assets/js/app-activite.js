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
import '../css/activite.css';

/** Intégration de jquery */
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

// On importe les paramètres serveur
import {serveur} from './properties.js';

/** On importe les constantes */
import {http_200, http_202, http_400, http_403, contentType, paletteCouleur, matrice, dateOptions, dateOptionsShort} from './constante.js';

import './foundation.js';

const refreshActivite=async function() {
  const optionsRefresh = {
        url: `${serveur()}/api/activite/sauvegarde`, type: 'POST',
        dataType: 'json', contentType };
  console.log(optionsRefresh);
  /** On appel l'API */
  const t = await $.ajax(optionsRefresh);

}

$('.js-activite-refresh').on('click', ()=>{
  refreshActivite();
});
