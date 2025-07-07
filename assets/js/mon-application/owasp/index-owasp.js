/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  Intégration du référentiel OWASP 2021 :
 *  Zakaria GUEDDOU <zakaria.gueddou19@gmail.com>
 *
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/common/select2.min.css';
import '../../../styles/mon-application/owasp.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../select2/select2.min.js';
import '../../select2/i18n/fr.js'

import '../../common/foundation.js';
import '../../auth/details.js';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe les constantes */
import {dateOptions, contentType, couleur, note, espace, rien,
  http_200, http_400, http_406,
  listeOwasp2017, listeOwasp2021,
  un, deux, trois, quatre, cinq, six, sept, huit, neuf, dix, onze,
  vingtNeuf, trente, soixanteNeuf, soixanteDix, cent} from '../../common/constante.js';

import {Chart, registerables} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

/** On enregistre les classes et les plugins dans chart.js */
Chart.register(...registerables);
Chart.register(ChartDataLabels);

/**
 * [Description for calculNoteHotspot]
 * Calcul la note des hotpots
 *
 * @param float taux
 *
 * @return Array
 *
 * Created at: 19/12/2022, 21:30:26 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const calculNoteHotspot = function(taux) {
  /** C = couleur, n = note */
  let c, n;
  if (taux > 0.79) {
    c = couleur[un];
    n = note[un];
  }
  if (taux > 0.71 && taux < 0.81) {
      c = couleur[deux];
      n = note[deux];
    }
  if (taux > 0.51 && taux < 0.71) {
      c = couleur[trois];
      n = note[trois];
    }
  if (taux > 0.31 && taux < 0.51) {
      c = couleur[4];
      n = note[quatre];
    }
  if (taux < 0.31) {
      c = couleur[5];
      n = note[cinq];
    }
  return [c, n];
};

/**
 * [Description for injectionOwaspInfo]
 * Fonction qui permet d'injecter dans la page les calculs des Owasp
 *
 * @param string id
 * @param string menace
 * @param string badge
 * @param integer laNote
 *
 * Created at: 19/12/2022, 21:31:50 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const injectionOwaspInfo = function(id, menace, badge, laNote) {
  const i =`<span class="stat-note">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(menace)}</span>
            <span class="badge ${badge}">${laNote}</span>`;
  $(`#a${id}`).html(i);
};

/**
 * [Description for videLeTableau]
 *
 * @return void
 *
 * Created at: 27/03/2024 13:05:58 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const videLeTableau = function() {
  /** réinitialise les valeurs. */
  /** version et la date du projet dans SonarQube */
  $('#js-application-version').html('');

  /** les vulnérabilités */
  $('#nombre-faille-owasp').html('');
  $('#nombre-faille-bloquant').html('');
  $('#nombre-faille-critique').html('');
  $('#nombre-faille-majeur').html('');
  $('#nombre-faille-mineur').html('');

  /** nombre de hotspot au status REVIEWED */
  $('#hotspot-reviewed').html('');
  /** nombre de hotspot au status TO_REVIEW */
  $('#hotspot-to-review').html('');

/**  hotspot OWASP  */
  $('#hotspot-total').html('');
  $('#nombre-hotspot-high').html('');
  $('#nombre-hotspot-medium').html('');
  $('#nombre-hotspot-low').html('');
  $('#note-hotspot').html('');

  /* Hotspot */
  for (let id=0; id<11; id++) {
    $(`#h${id}`).html('');
  }

  /** répartition front/back */
  $('#frontend').html('');
  $('#backend').html('');
  $('#autre').html('');

  /** on supprime le référentiel par défaut */
  sessionStorage.setItem('referential_owasp', '');
}

/**
* [Description for remplissageOwaspInfo]
* Récupération des informations sur les vulnérabilités OWASP.
*
* @param string maven_key
* @param integer referential_owasp
*
* Created at: 19/12/2022, 21:32:27 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
const remplissageOwaspInfo = async function(maven_key, referential_owasp) {

  /** si la clé maven n'est pas défini alors on sort */
  if (maven_key === undefined || referential_owasp === undefined) {
    return;
  }

  const data={maven_key, referential_owasp};

  const options = {
    url: `${serveur()}/api/peinture/owasp/liste`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  const r = await $.ajax(options);
  if (r.code !== http_200) {
    showMessage('primary', `Les données ont été trouvées.`);
  }
  if (r.code === http_400) {
    showMessage('alert', `<strong>[Owasp]</strong> La requête n'est pas conforme (Erreur 400) !`);
    videLeTableau();
    return;
  }
  if (r.code===http_406) {
    showMessage('warning', `Le projet n'a pas été trouvé !`);
    videLeTableau();
    return;
  }

  /** On affiche la version et la date du projet dans SonarQube */
  const date_version = new Intl.DateTimeFormat('default', dateOptions).format(new Date(r.date_version));
  $('#js-application-version').html(`<span class="color-noire open-sans">V${r.version}, (${date_version})</span>`);

  /** On affiche la version du référentiel */
  $('#owasp-version').html(`Référentiel OWASP Actuel : <span class="lead color-noir">${r.referential_owasp}</span>`);

  /* On génère l'histogramme avec les menaces OWASP */
  let dataBar=[];
  for (let i=1; i<11; i++){
    dataBar.push(r['a'+i]);
  }
  dessineMoiUneBarre(r.referential_owasp, dataBar);

  /** On ajoute les valeurs pour les vulnérabilités */
  $('#nombre-faille-owasp').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.total));
  $('#nombre-faille-bloquant').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.bloquant));
  $('#nombre-faille-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.critique));
  $('#nombre-faille-majeur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.majeur));
  $('#nombre-faille-mineur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.mineur));

  let c=[], n=[];

  /* Détails A1 */
  if (parseInt(r.a1Blocker + r.a1Critical + r.a1Major + r.a1Minor,10) === 0){
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a1Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a1Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a1Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a1Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(un, r.a1, c, n);

  /** Détails A2 */
  if (parseInt(r.a2Blocker + r.a2Critical + r.a2Major + r.a2Minor,10) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a2Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a2Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a2Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a2Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(deux, r.a2, c, n);

  /* Détails A3 */
  if (parseInt(r.a3Blocker + r.a3Critical + r.a3Major + r.a3Minor,10) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a3Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a3Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a3Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a3Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(trois, r.a3, c, n);

  /* Détails A4 */
  if (parseInt(r.a4Blocker + r.a1Critical + r.a1Major + r.a1Minor,10) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a4Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a4Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a4Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a4Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(quatre, r.a4, c, n);

  /* Détails A5 */
  if (parseInt(r.a5Blocker + r.a5Critical + r.a5Major + r.a5Minor,10) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a5Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a5Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a5Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a5Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  injectionOwaspInfo(cinq, r.a5, c, n);

  /* Détails A6 */
  if (parseInt(r.a6Blocker + r.a6Critical + r.a6Major + r.a6Minor,10) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a6Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a6Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a6Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a6Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(six, r.a6, c, n);

  /* Détails A7 */
  if (parseInt(r.a7Blocker + r.a7Critical + r.a7Major + r.a7Minor,10) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a7Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a7Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a7Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a7Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(sept, r.a7, c, n);

  /* Détails A8 */
  if (parseInt(r.a8Blocker + r.a8Critical + r.a8Major + r.a8Minor,10) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a8Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a8Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a8Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a8Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(huit, r.a8, c, n);

  /* Détails A9 */
  if (parseInt(r.a9Blocker + r.a9Critical + r.a9Major + r.a9Minor,10) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a9Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a9Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a9Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a9Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(neuf, r.a9, c, n);

  /* Détails A10 */
  if (parseInt(r.a10Blocker + r.a10Critical + r.a10Major + r.a10Minor,10) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (parseInt(r.a10Minor,10) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (parseInt(r.a10Major,10) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (parseInt(r.a10Critical,10) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (parseInt(r.a10Blocker,10) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(dix, r.a10, c, n);
};

/**
 * [Description for remplissageHotspotInfo]
 * Récupération des informations sur les hotspot OWASP
 *
 * @param string maven_key
 * @param integer referential_owasp
 *
 * Created at: 19/12/2022, 21:39:57 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageHotspotInfo = async function(maven_key, referential_owasp) {

  /** si la clé maven n'existe pas alors on sort */
  if (maven_key === undefined || referential_owasp === undefined) {
    return;
  }

  const data = { maven_key, referential_owasp };
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/info`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  const r = await $.ajax(options);
  if (r.code===http_400) {
    const message=`<strong>[hotspot]</strong> La requête n'est pas conforme (Erreur 400) !`;
    showMessage('alert', message);
    videLeTableau();
    return;
  }

  let i='';
  /** On compte le nombre de hotspot au status REVIEWED */
  $('#hotspot-reviewed').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.reviewed));
  /** On compte le nombre de hotspot au status TO_REVIEW */
  $('#hotspot-to-review').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.toReview));
  const hotspotToReview = r.toReview;

  /** On affiche le nombre de hotspot OWASP et par la répartition */
  $('#hotspot-total').html(r.total);
  const nombreHotspot=r.total;
  $('#nombre-hotspot-high').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.high));
  $('#nombre-hotspot-medium').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.medium));
  $('#nombre-hotspot-low').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.low));

  let leTaux=1, laNote=['a', 'A'];
  if ( nombreHotspot !==0 ) {
    leTaux = 1 - (parseInt(hotspotToReview,10) / nombreHotspot);
    laNote = calculNoteHotspot(leTaux);
  }

  const lowerLaNote=laNote[0].toLowerCase();
  i = `<span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(leTaux)}</span><span class="badge ${lowerLaNote}"> ${laNote[1]}</span>`;
  $('#note-hotspot').html(i);
};

/**
* [Description for injectionHotspotListe]
* Fonction qui permet d'injecter dans la page les calcul des hotspot
*
* @param string id
* @param string formatage
* @param string menace
* @param float leTaux
* @param string badge
* @param integer laNote
*
* @return string
*
* Created at: 19/12/2022, 21:41:13 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
const injectionHotspotListe=function(id, formatage, menace, leTaux, badge, laNote) {
  const i = `<span class="stat-note">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(menace)}</span>
  <span class="stat-note">${formatage} ${Intl.NumberFormat('fr-FR', { style: 'percent' }).format(leTaux)}
  </span> <span class="badge ${badge}">${laNote}</span>`;
$(`#h${id}`).html(i);
};

/**
* [Description for remplissageHotspotListe]
* Fonction de remplissage du tableau avec les infos hotspot owasp A1-A10.
*
* @param string maven_key
* @param integer referential_owasp
*
* Created at: 19/12/2022, 21:42:09 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
const remplissageHotspotListe = async function(maven_key, referential_owasp) {
  if (maven_key === undefined || referential_owasp === undefined) {
    return;
  }

  /**
   * On appel le l'API en charge de récupérer la liste des failles de type OWASP
   */
    const data = { maven_key, referential_owasp };
    const options = {
      url: `${serveur()}/api/peinture/owasp/hotspot/liste`, type: 'POST',
            dataType: 'json', data: JSON.stringify(data), contentType };

    const r = await $.ajax(options);
    if (r.code===http_400) {
      const message=`<strong>[Hotspot]</strong> La requête n'est pas conforme (Erreur 400) !`;
      showMessage('alert', message);
      videLeTableau();
      return;
    }

    let leTaux=1, laNote=['a','A'], formatage;
    const nombreHotspot = parseInt(r.menaceA1+r.menaceA2+r.menaceA3+r.menaceA4+
                            r.menaceA5+r.menaceA6+r.menaceA7+r.menaceA8+
                            r.menaceA9+r.menaceA10,10);
    formatage = espace;

    if ( nombreHotspot !== 0 ){
      /* calcul A1 */
      leTaux = 1 - (parseInt(r.menaceA1,10) / nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ( (leTaux*cent)>dix && (leTaux*cent) < cent) {
        formatage = espace+espace+espace;
      } else {
        formatage = rien;
        }
      injectionHotspotListe(un, formatage, r.menaceA1, leTaux, laNote[0], laNote[1]);

      /* calcul A2*/
      leTaux = 1 - (parseInt(r.menaceA2,10) / nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ( (leTaux*cent)>dix && (leTaux*cent) < cent ) {
        formatage = espace + espace + espace;
        } else {
          formatage = rien;
        }
      injectionHotspotListe(deux, formatage, r.menaceA2, leTaux, laNote[0], laNote[1]);

      /* calcul A3 */
      leTaux = 1 - (r.menaceA3/ nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ( (leTaux*cent)>dix && (leTaux*cent) < cent ) {
        formatage = espace + espace + espace;
      } else {
        formatage = rien;
      }
      injectionHotspotListe(trois, formatage, r.menaceA3, leTaux, laNote[0], laNote[1]);

      /* Calcul A4 */
      leTaux = 1 - (r.menaceA4/nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ( (leTaux*cent)>dix && (leTaux*cent) < cent ) {
        formatage = espace + espace + espace;
      } else {
        formatage = rien;
      }
      injectionHotspotListe(quatre, formatage, r.menaceA4, leTaux, laNote[0], laNote[1]);

      /* calcul A5 */
      leTaux = 1 - (r.menaceA5/nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ( (leTaux*cent)>dix && (leTaux*cent) < cent ) {
        formatage = espace + espace + espace;
      } else {
          formatage=rien;
      }
      injectionHotspotListe(cinq, formatage, r.menaceA5, leTaux, laNote[0], laNote[1]);

      /* Calcul A6 */
      leTaux = 1 - (r.menaceA6/nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ( (leTaux*cent)>dix && (leTaux*cent) < cent) {
        formatage = espace + espace + espace;
      } else {
          formatage=rien;
        }
      injectionHotspotListe(six, formatage, r.menaceA6, leTaux, laNote[0], laNote[1]);

      /* Calcul A7 */
      leTaux = 1 - (r.menaceA7 /nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ( (leTaux*cent)>dix && (leTaux*cent) < cent) {
        formatage = espace + espace + espace;
      } else {
          formatage = rien;
      }
      injectionHotspotListe(sept, formatage, r.menaceA7, leTaux, laNote[0], laNote[1]);

      /* Calcul A8 */
      leTaux = 1 - (r.menaceA8/nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ((leTaux*cent)>dix && (leTaux*cent) < cent) {
        formatage = espace + espace + espace;
      } else {
          formatage = rien;
        }
      injectionHotspotListe(huit, formatage, r.menaceA8, leTaux, laNote[0], laNote[1]);

      /* calcul A9 */
      leTaux = 1 - (r.menaceA9/nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ((leTaux*cent)>dix && (leTaux*cent) < cent) {
        formatage = espace + espace + espace;
      } else {
          formatage = rien;
      }
      injectionHotspotListe(neuf, formatage, r.menaceA9, leTaux, laNote[0], laNote[1]);

      /* Calcul A10 */
      leTaux = 1 - (r.menaceA10/nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ((leTaux*cent)>dix && (leTaux*cent) < cent) {
        formatage = espace + espace + espace;
      } else {
          formatage = rien;
      }
      injectionHotspotListe(dix, formatage, r.menaceA10, leTaux, laNote[0], laNote[1]);
    } else {
      for (let i=1; i<onze; i++){
        injectionHotspotListe(i, formatage, 0, leTaux, laNote[0], laNote[1]);
      }
    }
};

/**
 * [Description for injectionHotspotDetails]
 * Injecte les ligne de détails pour les hotspot
 *
 * @param mixed number
 * @param string url
 * @param string color
 * @param string rule
 * @param string severity
 * @param string file
 * @param integer line
 * @param string message
 * @param string status
 *
 * @return string
 *
 * Created at: 19/12/2022, 21:44:32 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const injectionHotspotDetails = function(number,url,color,rule,severity,file,line,message,status){
  const ligne = `<tr>
                  <td class="stat-note">${number}</td>
                  <td><a href="${url}/coding_rules?open=${rule}&q=${rule}">${rule}</a></td>
                  <td class="${color}">${severity}</td>
                  <td class="component">${file}</td>
                  <td>${line}</td>
                  <td>${message}</td>
                  <td>${status}</td>
                </tr>`;
  $('#tbody').append(ligne);
};

/**
 * [Description for injectionModule]
 *
 * @param string module
 * @param integer total
 * @param integer taux
 * @param string bc
 * @param integer zero
 *
 * @return string
 *
 * Created at: 19/12/2022, 21:45:32 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const injectionModule = function (module, total, taux, bc, zero){
  const i = ` <span class="stat-note">${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(taux)}</span>
              <span class="box ${bc} stat-note">${zero}${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(total)}</span>`;
  switch (module) {
    case 'frontend':
      $('#frontend').html(i);
      break;
    case 'backend':
      $('#backend').html(i);
      break;
    case 'autre':
      $('#autre').html(i);
      break;
    default:
      sessionStorage.setItem('Owasp', `Oups !!!, je ne connais pas ${module}.`);
  }
};

/**
 * [Description for remplissageHotspotDetails]
 * Affiche le tableau du détails des hotspot
 *
 * @param string maven_key
 * @param integer referential_owasp
 *
 * Created at: 19/12/2022, 21:46:30 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageHotspotDetails = async function(maven_key, referential_owasp) {
  /** Si la clé maven n'est pas défini on ne fait rien */
  if (maven_key === undefined || referential_owasp === undefined) {
    return;
  }

  const data={maven_key, referential_owasp };
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/details`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  const r =await  $.ajax(options)
  if (r.code === http_400) {
    const message = `<strong>[Details]</strong> La requête n'est pas conforme (Erreur 400) !`;
    showMessage('alert', message);
    videLeTableau();
    return;
  }

    let number = 0, monNumber, ligne, c, frontend = 0, backend = 0, autre = 0;
    let vide, too, totalABC, zero = '', bc;
    const serveurURL = $('#js-serveur').data('serveur');

    if (r.details.menaces === undefined || r['details']['menaces'].length == 0) {
        /** On met ajour la répartition par module */
        vide = `<span class="stat-note">
        ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>
              <span class="stat-note">
              ${Intl.NumberFormat('fr-FR', { style: 'percent' }).format(0)}</span>`;
        $('#frontend').html(vide);
        $('#backend').html(vide);
        $('#autre').html(vide);

        /** On ajoute une ligne dans le tableau */
        ligne = `<tr class="text-center">
                  <td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>Pas de faille.</td>
                </tr>`;
        $('#tbody').html(ligne);
      } else {
      /** On efface le tableau et on ajoute les lignes */
      /** On calcul l'impact sur les modules */
      $('#tbody').html('');
      for ( const detail of r.details){
        number++;
        if (number < dix) {
          monNumber = '0' + number;
          } else {
          monNumber = number;
        }
        if (detail.severity === 'LOW') {
          c = 'text-center note-c';
        }
        if (detail.severity === 'MEDIUM') {
          c = 'text-center note-d';
        }
        if (detail.severity === 'HIGH') {
          c = 'text-center note-e';
        }

        if (detail.frontend === 1) {
          frontend++;
        }
        if (detail.backend === 1) {
          backend++;
        }
        if (detail.autre === 1) {
          autre++;
        }

        injectionHotspotDetails(monNumber, serveurURL, c, detail.rule, detail.severity, detail.file, detail.line, detail.message, detail.status);
      }

    /** Met à jour la répartition par module */
    totalABC = parseInt((frontend + backend + autre),10);
    const moduleVert = 'note-a';
    const moduleOrange = 'note-d';
    const moduleRouge = 'note-e';

    if ((frontend<dix)) {
      zero='00';
    }
    if (frontend>neuf && frontend<cent) {
      zero='0';
    }

    /** Calcul pour le frontend */
    too = (frontend / totalABC);
    if (frontend < cent) {
      zero='00';
    }
    if (frontend > neuf && frontend < cent) {
      zero='0';
    }
    if (too * cent < trente) {
      bc = moduleVert;
    }
    if (too * cent > vingtNeuf && too * cent < soixanteDix) {
      bc = moduleOrange;
    }
    if (too * cent > soixanteNeuf) {
      bc = moduleRouge;
    }
    injectionModule('frontend', frontend, too, bc, zero);

    /** Calcul pour le backend */
    too=(backend / totalABC);
    if (backend < dix) {
      zero = '00';
    }
    if (backend > neuf && backend < cent) {
      zero = '0';
    }
    if (too * cent < trente) {
      bc = moduleVert;
    }
    if (too * cent > vingtNeuf && too * cent < soixanteDix) {
      bc = moduleOrange;
    }
    if (too * cent > soixanteNeuf) {
      bc = moduleRouge;
    }
    injectionModule('backend', backend, too, bc, zero);

    /** Calcul pour le backend */
    too=(autre / totalABC);
    if (autre < dix) {
      zero = '00';
    }
    if (autre > neuf && autre < cent) {
      zero = '0';
    }
    if (too * cent < trente) {
      bc = moduleVert;
    }
    if (too * cent > vingtNeuf && too * cent < soixanteDix) {
      bc = moduleOrange;
    }
    if (too * cent > soixanteNeuf) {
      bc = moduleRouge;
    }
    injectionModule('autre', autre, too, bc, zero);
  }
};

/**
 * [Description for remplissageDetailsHotspotOwasp]
 * Permet d'afficher le détails de chaque hotspot
 *
 * @param string maven_key
 * @param string menace
 * @param string titre
 *
 * Created at: 19/12/2022, 21:49:48 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageDetailsHotspotOwasp = async function(maven_key, menace, titre) {
  if (maven_key === undefined) {
    return;
  }

  const data={ maven_key, menace };
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/severity`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  const r = await $.ajax(options);
  if (r.code===http_400) {
    const message=`[Severity] La requête n'est pas conforme (Erreur 400) !`;
    showMessage('alert', message);
    return;
  }

    /** on affiche le titre en fonction du référentiel */
    const x = sessionStorage.getItem('referential_owasp');
    if (x === undefined || x == '') { return; }

    if (x === 2017) {
      $('.details-titre').html(listeOwasp2017[titre]);
    } else {
      $('.details-titre').html(listeOwasp2021[titre]);
    }

    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.high));
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.medium));
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.low));
};

$('.js-details').on('click', function () {
  const id = $(this).attr('id').split('-');
  const k_key=sessionStorage.getItem('projet');
  if (id[1] === 'a1') {
    remplissageDetailsHotspotOwasp(k_key, 'a1', un);
  }
  if (id[1] === 'a2') {
    remplissageDetailsHotspotOwasp(k_key, 'a2', deux);
  }
  if (id[1] === 'a3') {
    remplissageDetailsHotspotOwasp(k_key, 'a3', trois);
  }
  if (id[1] === 'a4') {
    remplissageDetailsHotspotOwasp(k_key, 'a4', quatre);
  }
  if (id[1] === 'a5') {
    remplissageDetailsHotspotOwasp(k_key, 'a5', cinq);
  }
  if (id[1] === 'a6') {
    remplissageDetailsHotspotOwasp(k_key, 'a6', six);
  }
  if (id[1] === 'a7') {
    remplissageDetailsHotspotOwasp(k_key, 'a7', sept);
  }
  if (id[1] === 'a8') {
    remplissageDetailsHotspotOwasp(k_key, 'a8',huit);
  }
  if (id[1] === 'a9') {
    remplissageDetailsHotspotOwasp(k_key, 'a9', neuf);
  }
  if (id[1] === 'a10') {
    remplissageDetailsHotspotOwasp(k_key, 'a10', dix);
  }
  $('#details').foundation('open');
});

  /**
   * [Description for selectAnalyseVersion]
   * Ajoute une classe active au bouton sélectionné et appelle les fonctions de remplissage.
   *
   * @param mixed referential_owasp
   *
   * Created at: 20/11/2024 19:46:23 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  const selectAnalyseVersion = function (referential_owasp) {
    /** Affiche les vulnérabilités par criticité */
    remplissageOwaspInfo(key, referential_owasp);
    /** Affiche les hotspot par criticité */
    remplissageHotspotInfo(key, referential_owasp);
    /** On rempli le tableau avec les données des menaces owasp */
    remplissageHotspotListe(key, referential_owasp);
    /** On rempli le tableau avec led données des hotspot */
    remplissageHotspotDetails(key, referential_owasp);
    //owaspPieChart.update();
  }

/**
 * [Description for selectReferentialOwasp]
 *
 * @param string version
 *
 * Created at: 21/11/2024 19:06:35 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const selectReferentialOwasp = function(version){
  if (version === '2017') {
    $('#owasp-2017').removeClass('hide');
    $('#owasp-2021').addClass('hide');
  } else if (version === '2021') {
    $('#owasp-2017').addClass('hide');
    $('#owasp-2021').removeClass('hide');
  }
}

/*************** Main du programme **************/
/** On récupère la clé du projet */
const key=sessionStorage.getItem('projet');
const projet=key.split(':');

/** On met à jour la page */
$('#js-application').html(projet[1]);

/** On affiche le référentiel owasp le plus récent */
$('#js-owasp-select').val('2021');
$('#js-owasp-select').trigger('change');
selectReferentialOwasp('2021');

/** On appel les fonctions de remplissage pour la version OWASP*/
$('#version-2017').on('click', ()=>{
  sessionStorage.setItem('referential_owasp', 2017);
  $('#version-2017').addClass('active');
  $('#version-2021').removeClass('active');
  if ($('#version-2017').hasClass('disable') === false){
    selectAnalyseVersion('2017');
    sessionStorage.setItem('referential_owasp', 2021);
  }
});

/** On gère les boutons d'affichage des résultats */
$('#version-2021').on('click', ()=>{
  if ($('#version-2021').hasClass('disable') === false){
    $('#version-2017').removeClass('active');
    $('#version-2021').addClass('active');
    selectAnalyseVersion('2021');
  }
});

/** on gère le changement de référentiel */
$('select[name="owasp"]').on('change', function () {
  const version=$('select[name="owasp"]').val();
  selectReferentialOwasp(version);
});

/**
 * [Description for dessineMoiUneBarre]
 *
 * @param mixed referential
 *
 * @return [type]
 *
 * Created at: 26/11/2024 19:20:43 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const dessineMoiUneBarre = function (referential, data){

  // Données statiques pour les pourcentages de chaque type de vulnérabilité
  const labels = ['A1','A2','A3','A4','A5','A6','A7','A8','A9','A10'];

  const ctx = document.getElementById('owasp-bar-chart').getContext('2d');
  const owaspPieChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
          {
              label: `Répartition des menaces OWASP ${referential}`,
              data: data,
              backgroundColor: [
                  'rgba(255, 99, 132, 0.2)',
                  'rgba(54, 162, 235, 0.2)',
                  'rgba(255, 206, 86, 0.2)',
                  'rgba(75, 192, 192, 0.2)',
                  'rgba(153, 102, 255, 0.2)',
                  'rgba(255, 159, 64, 0.2)',
                  'rgba(199, 199, 199, 0.2)',
                  'rgba(83, 102, 255, 0.2)',
                  'rgba(255, 203, 64, 0.2)',
                  'rgba(93, 99, 199, 0.2)'
              ],
              borderColor: [
                  'rgba(255, 99, 132, 1)',
                  'rgba(54, 162, 235, 1)',
                  'rgba(255, 206, 86, 1)',
                  'rgba(75, 192, 192, 1)',
                  'rgba(153, 102, 255, 1)',
                  'rgba(255, 159, 64, 1)',
                  'rgba(199, 199, 199, 1)',
                  'rgba(83, 102, 255, 1)',
                  'rgba(255, 203, 64, 1)',
                  'rgba(93, 99, 199, 1)'
              ],
              borderWidth: 1
          }
      ]
    },
    options: {
      responsive: true,
      plugins: {
          legend: {
              position: 'top',
              labels: {
                font: {
                    size: 24,
                    weight: 'bold'
                }
              }
            },
          tooltip: {
              callbacks: {
                  label: function(context) {
                      let label = context.label || '';
                      if (label) {
                          label += ': ';
                      }
                      if (context.parsed !== null) {
                          label += context.parsed + '%';
                      }
                      return label;
                  }
              }
          }
      }
    }
  });

}
