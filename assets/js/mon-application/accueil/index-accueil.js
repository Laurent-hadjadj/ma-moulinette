/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
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

/** Gestion des modales zurb foundation compatible WCAG  */
import { modalSafe } from '../../common/safeModal.js';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

/** On importe l'encodage ROT13 utilisé pour les jetons de navigation (Suivi, COSUI...) */
import { encode } from '../../common/encode.js';

/** La gestion des messagesJS */
import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';
/** On importe les constantes */
import { http_400, http_401, http_403, http_404, http_500, http_504, content_type } from '../../common/constante.js';

// on contrôle si le module secrets.local.js est bien chargé pour récupérer le token
let token;
try {
  const module = await import("../../common/secrets.local.js");
  token = module.token;
} catch (error) {
  const trace = prepareTechnicalDetails(error);
  const message = "Le module n'a pas été chargé correctement (Erreur 500).";
  showMessage('critical', message, trace);
}

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
  if ($('#bouton-mise-a-jour-referential').hasClass('bouton-disabled')){
    return { code: http_401 };
  }

  const options = {
    url: `${serveur()}/api/secure/status`,
    type: 'POST',
    dataType: 'json',
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
    return await $.ajax(options);
  } catch(error) {
    // 📌 Vérification des erreurs
    if (error.status === http_404){
      const message = `La requête est incorrecte (Erreur 404).`;
      showMessage('error', message);
    }
    if (error.status === http_500 || error.status === 'DOWN'){
      const message = `État du serveur SonarQube : <strong>DOWN</strong> (Erreur 500).`;
      showMessage('critical', message);
    }
    return;
  }
};

/**
  * [Description for miseAJourListe]
  * Récupération de la liste des projets.
  * ROLE_REFERENTIAL est nécessaire pour accéder à cette route.
  *
  * @return void
  *
  */
const miseAJourListe = async function() {
  if ($('#bouton-mise-a-jour-referential').hasClass('bouton-disabled') === true){
    return;
  }

  const options = {
    url: `${serveur()}/api/secure/accueil/projet`,
    type: 'POST',
    dataType: 'json',
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
        const t = await $.ajax(options);
        // 📌 Vérification des erreurs, on traite la 403 dans le catch
        const errorCodes = [http_400, http_401, http_404, http_500];
        if (errorCodes.includes(t.code)){
            const hasTrace = !!t.trace;
            const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
            showMessage(t.type, t.message, trace);
            return;
          }

        /** On affiche le nombre de projet */
        $('#js-nombre-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nombre));
        /** On efface le plus|moins */
        $('#js-moins, #js-plus').html('');

        /** On met à jour le nombre de projet public */
        $('#js-public').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.public));
        /** On met à jour le nombre de projet privée */
        if (t.private === undefined || Number.isNaN(t.private)) {
          $('#js-private').html('-');
          } else {
            $('#js-private').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.private));
          }

        /** On affiche le nombre de projet dans la zone tags */
        $('#js-tag-projet').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nombre));

          /** On affiche un message à l'utilisateur */
          const hasTrace = !!t.trace;
          const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
          showMessage(t.type, t.message, trace);
          setTimeout(() => { hideMessage(); }, 5000);
  } catch(error) {
    if (error.status === http_403) {
      const message = `Vous n'avez pas les droits nécessaires pour accéder à cette ressource (Erreur 403).`;
      showMessage('warning', message);
      return;
    }

    const message = `Une erreur inattendue s'est produite lors de la mise à jour des projets (Erreur 500).`
    const trace = prepareTechnicalDetails(error);
    console.log(trace);
    if (typeof(trace) === 'object' && trace.message && trace.code && trace.type) {
      showMessage(trace.type, trace.message);
      return;
    }
    showMessage('critical', message, trace);
  }
};

/**
 * [Description for miseAJourTags]
 * On met à jour le nombre de projet et le nombre de tags
 * ROLE_REFERENTIAL est nécessaire pour accéder à cette route.
 *
 * @return void
 *
 * Created at: 19/05/2024 11:18:10 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const miseAJourTags = async function() {
  if ($('#bouton-mise-a-jour-referential').hasClass('bouton-disabled') === true){
    return;
  }

  const options = {
    url: `${serveur()}/api/secure/accueil/tags`,
    type: 'POST',
    dataType: 'json',
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

    try {
      const t = await $.ajax(options);
      // 📌 Vérification des erreurs, on gère la 403 dans le catch
      const errorCodes = [http_400, http_401, http_404, http_500];
      if (errorCodes.includes(t.code)){
          const hasTrace = !!t.trace;
          const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
          showMessage(t.type, t.message, trace);
          return;
        }

      /** On affiche le nombre de tags */
      $('#js-tag-nombre')
        .html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nombre_tag))
        .removeClass('couleur-rouge')
        .addClass('couleur-bleu');
    } catch (error) {
      if (error.status === http_403) {
        const message = `Vous n'avez pas les droits nécessaires pour accéder à cette ressource (Erreur 403).`;
        showMessage('warning', message);
        return;
      }
      const message = `Une erreur inattendue s'est produite lors de la récupération du nombre de tags (Erreur 500).`;
      const trace = prepareTechnicalDetails(error);
      if (typeof(trace) === 'object' && trace.message && trace.code && trace.type) {
        showMessage(trace.type, trace.message);
        return;
      }
      showMessage('critical', message, trace);
    }
};

/********* Événement *******/

/**
 * description
 * Événement : on recharge la liste des projets.
 */
$('#bouton-mise-a-jour-referential').on('click', ()=> {
  void sonarIsUp()
    .then((t)=> {
      /** On regarde si le serveur SonarQube est disponible */
      const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_504];
      if (errorCodes.includes(t.code) || t.message === 'Internal Server Error'){
        /** l'URL n'a pas été trouvé, l'utilisateur n'a pas les droits */
        if (t.code === http_404 || t.code === http_401) {
          return;
        }
        /** On affiche le message d'erreur du serveur */
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        return;
      }

      /** si le serveur est disponible */
      void miseAJourListe();
      void miseAJourTags();
    })
    .catch((error) => {
      const message = `Une erreur inattendue s'est produite lors de la vérification de la disponibilité de SonarQube (Erreur 500).`;
      const trace = prepareTechnicalDetails(error);
      showMessage('critical', message, trace);
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
    /** on créé un hash avec la méthode reduce() comme clé de salt */
    const salt = mavenKey.split('').reduce((hash, char) => {
      return char.codePointAt(0) + (hash << 6) + (hash << 16) - hash;
    }, 0);

    /** on créé un token pour encoder les paramètres */
    const param = `${salt}|${mavenKey}`;
    const token = encode(btoa(param));
    window.location.href = `${serveur()}/suivi/set?token=${token}`;
    } else {
      sessionStorage.setItem('ma_moulinette_accueil_error', "La clé maven n'est pas correcte !! !");
  }
});

/** On ajoute la version du serveur SonarQube dans la sessionStorage pour être utilisé par le JS */
const element = document.getElementById("version-sonar");
const version_sonar = element.dataset.versionSonar ?? "8";
sessionStorage.setItem('ma_moulinette_version_sonar', version_sonar);

/** On ouvre la modale d'information sur les tags */
$('#bouton-ouvrir-information-tag').on('click', function () {
  modalSafe.open('#modal-information-tag');
});

/** On ferme la modale d'information sur les tags */
$('#bouton-fermer-information-tag').on('click', function () {
  modalSafe.close('#modal-information-tag');
});
