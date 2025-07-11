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
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/accueil.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';

import '../../auth/details.js';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

/** La gestion des messagesJS */
import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';
/** On importe les constantes */
import { http_400, http_401, http_403, http_404, http_500, http_504, contentType } from '../../common/constante.js';

/**
  * [Description for sonarIsUp]
  * Vérifie si le serveur SonarQube est UP
  * Affiche la version du serveur
  *
  * @return void
  *
  */
const sonarIsUp = async function() {
  /**  si le bouton de mise à jour des projets est désactivé on sort */
  if ($('.refresh-bd').hasClass('bouton-disabled')){
    return { code: http_401 };
  }

  const options = {
    url: `${serveur()}/api/status`, type: 'POST', dataType: 'json',  contentType };
  try
  {
    return await $.ajax(options);
  } catch(error) {
    // 📌 Vérification des erreurs
    if (error.status === http_404){
      showMessage('alert', `<strong>[Accueil]</strong> La requête est incorrecte (Erreur 404).`);
    }
    if (error.status === http_500 || error.status === 'DOWN'){
      showMessage('alert', `<strong>[Accueil]</strong> État du serveur SonarQube : <strong>DOWN</strong>.`);
    }
    return;
  }
};

/**
  * [Description for miseAJourListe]
  * Récupération de la liste des projets.
  *
  * @return void
  *
  */
const miseAJourListe = async function() {
  if ($('.refresh-bd').hasClass('bouton-disabled') === true){
    return;
  }

  const options = {
    url: `${serveur()}/api/accueil/projet`, type: 'POST', dataType: 'json', contentType };
  try {
        const t = await $.ajax(options);
        // 📌 Vérification des erreurs
        const errorCodes = [http_400, http_401, http_403, http_404, http_500];
        if (errorCodes.includes(t.code)){
            showMessage(t.type, t.message);
            return;
          }

        /** On affiche le nombre de projet */
        $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nombre));
        /** On efface le plus|moins */
        $('#js-moins, #js-plus').html('');
        /** On ferme la callout */
        $('#info-close').trigger('click');

        /** On met à jour le nombre de projet public */
        $('#js-public').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.public));
        /** On met à jour le nombre de projet privée */
        if (isNaN(t.private)) {
          $('#js-private').html('-');
          } else {
            $('#js-private').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.private));
          }

        /** On affiche le nombre de projet dans la zone tags */
        $('#js-tag-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nombre));

          /** On affiche un message à l'utilisateur */
          showMessage(t.type, t.message);
          setTimeout(() => { hideMessage(); }, 5000);
  } catch(error) {
    showMessage('alert', `<strong>[Accueil]</strong> Une erreur inattendue s'est produite lors de la mise à jour des projets.<br>${error.message}`);
  }
};

/**
 * [Description for miseAJourTags]
 * On met à jour le nombre de projet et le nombre de tags
 * @return void
 *
 * Created at: 19/05/2024 11:18:10 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const miseAJourTags = async function() {
  if ($('.refresh-bd').hasClass('bouton-disabled') === true){
    return;
  }

  const options = {
    url: `${serveur()}/api/accueil/tags`, type: 'POST', dataType: 'json', contentType };

    try {
      const t = await $.ajax(options);
      // 📌 Vérification des erreurs
      const errorCodes = [http_400, http_401, http_403, http_404, http_500];
      if (errorCodes.includes(t.code)){
          showMessage(t.type, t.message);
          return;
        }

      /** On affiche le nombre de tags */
      $('#js-tag-nombre').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nombre_tag));
    } catch (error) {
      showMessage('alert', `<strong>[Accueil]</strong> Une erreur inattendue s'est produite lors de la récupération du nombre de tags.<br>${error.message}`);
    }
};

/********* Événement *******/

/**
 * description
 * Événement : on recharge la liste des projets.
 */
$('.refresh-bd').on('click', ()=> {
  sonarIsUp()
    .then((t)=> {
      /** On regarde si le serveur SonarQube est disponible */
      const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_504];
      if (errorCodes.includes(t.code) || t.message === 'Internal Server Error'){
        /** l'URL n'a pas été trouvé, l'utilisateur n'a pas les droits */
        if (t.code === http_404 || t.code === http_401) {
          return;
        }
        /** On affiche le message d'erreur du serveur */
        showMessage(t.type, t.message);
        return;
      }

      /** si le serveur est disponible */
      miseAJourListe();
      miseAJourTags();
    });
});

/**
 * description
 * Événement : On ouvre le tableau de suivi pour le projet.
 */
$('.tableau-de-board-svg').on('click', function(e) {
  const id = e.currentTarget.id;

  /* On récupère la clé maven du projet. */
  const element = document.getElementById(id);
  const mavenKey = element.dataset.mavenkey;
  if (mavenKey !== ''){
    window.location.href = '/suivi/set?maven_key='+mavenKey;
    } else {
      sessionStorage.set('error', "La clé maven n'est pas correcte !! !");
  }
});
