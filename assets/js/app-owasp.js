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


import '../css/owasp.css';

// Intégration de jquery
import $ from 'jquery';

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import './foundation.js';
import './app-authentification-details.js';

// chart
import Chart from 'chart.js/auto';

/** On importe les paramètres serveur */
import {serveur} from './properties.js';

/** On importe les constantes */
import {contentType, couleur, note, espace, rien,
        http_200, http_400, http_406, listeOwasp2017,
        un, deux, trois, quatre, cinq, six, sept, huit, neuf, dix, onze,
        vingtNeuf, trente, soixanteNeuf, soixanteDix, cent} from './constante';

/* Construction des callbox de type success */
const callboxInformation='<div id="js-message" class="callout alert-callout-border primary" data-closable="slide-out-right" role="alert"><p class="open-sans color-bleu padding-right-1"><span class="lead"></span>Information ! </strong>';
const callboxSuccess='<div id="js-message" class="callout alert-callout-border success" data-closable="slide-out-right" role="alert"><span class="open-sans color-bleu padding-right-1"<span class="lead">Bravo ! </span>';
const callboxWarning='<div id="js-message" class="callout alert-callout-border warning" data-closable="slide-out-right" role="alert"><span class="open-sans padding-right-1 color-bleu"><span class="lead">Attention ! </span>';
const callboxError='<div id="js-message" class="callout alert-callout-border alert" data-closable="slide-out-right"><span class="open-sans padding-right-1 color-bleu"><span class="lead">Oups ! </span>';
const callboxFermer='</span><button class="close-button" aria-label="Fermer la fenêtre" type="button" data-close><span aria-hidden="true">&times;</span></button></div>';

/**
 * [Description for calculNoteHotspot]
 * Calcul la note des hotpots
 * @param mixed taux
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:30:26 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const calculNoteHotspot=function(taux) {
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
 * Fonction qui permet d'injecter dans la page les calcul des Owasp
 *
 * @param string id
 * @param string menace
 * @param string badge
 * @param integer laNote
 *
 * Created at: 19/12/2022, 21:31:50 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const injectionOwaspInfo=function(id, menace, badge, laNote) {
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
  /** version et la date du projet dans sonarqube */
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
}


/**
* [Description for remplissageOwaspInfo]
* Récupération des informations sur les vulnérabilités OWASP.
*
* @param string idMaven
*
* Created at: 19/12/2022, 21:32:27 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
const remplissageOwaspInfo=function(idMaven, referentielVersion) {

  /** si la clé maven n'est pas défini alors on sort */
  if (idMaven === undefined || referentielVersion === undefined) {
    return;
  }
  const data={'maven_key': idMaven, 'referentiel_version': referentielVersion};
  const options = {
    url: `${serveur()}/api/peinture/owasp/liste`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  $.ajax(options).then(r => {
    if (r.code===!http_200) {
      const message=`Les données ont été trouvées.`;
      $('#message').html(callboxInformation+message+callboxFermer);
    }
    if (r.code===http_400) {
      const message=`[Owasp] La requête n'est pas conforme (Erreur 400) !`;
      $('#message').html(callboxError+message+callboxFermer);
      videLeTableau();
      return;
    }
    if (r.code===http_406) {
      const message=`La version n'a pas été trouvé !`;
      $('#message').html(callboxWarning+message+callboxFermer);
      videLeTableau();
      return;
    }

    /** On affiche la version et la date du projet dans sonarqube */
    $('#js-application-version').html(`<span class="color-noire open-sans">V${r.version}, (${r.date_version})</span>`);

    /** On affiche la version du référentiely */
    $('#owasp-version').html(`Référentiel OWASP Actuel : ${r.referentiel_version}`);

    /** On ajoute les valeurs pour les vulnérabilités */
    $('#nombre-faille-owasp').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.total));
    $('#nombre-faille-bloquant').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.bloquant));
    $('#nombre-faille-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.critique));
    $('#nombre-faille-majeur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.majeur));
    $('#nombre-faille-mineur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.mineur));

    let c=[],n=[];

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
  });
};

/**
 * [Description for remplissageHotspotInfo]
 * Récupération des informations sur les hotspot OWASP
 *
 * @param string idMaven
 *
 * Created at: 19/12/2022, 21:39:57 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageHotspotInfo=function(idMaven, referentielVersion) {

  /** si la clé maven n'existe pas alors on sort */
  if (idMaven === undefined || referentielVersion === undefined) {
    return;
  }

  const data={'maven_key': idMaven, 'referentiel_version': referentielVersion};
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/info`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  $.ajax(options).then(r=> {
    if (r.code===http_400) {
      const message=`[hotspot] La requête n'est pas conforme (Erreur 400) !`;
      $('#message').html(callboxError+message+callboxFermer);
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
    const hotspotTotal=r.total;
    $('#nombre-hotspot-high').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.high));
    $('#nombre-hotspot-medium').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.medium));
    $('#nombre-hotspot-low').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.low));

    let leTaux=1, laNote=['a', 'A'];
    if ( hotspotTotal !==0 ) {
      leTaux = 1 - (parseInt(hotspotToReview,10) / hotspotTotal);
      laNote = calculNoteHotspot(leTaux);
    }

    const lowerLaNote=laNote[0].toLowerCase();
    i = `<span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(leTaux)}</span>
          <span class="badge note-${lowerLaNote}"> ${laNote[1]}</span>`;
    $('#note-hotspot').html(i);
    });
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
* @return [type]
*
* Created at: 19/12/2022, 21:41:13 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
const injectionHotspotListe=function(id, formatage, menace, leTaux, badge, laNote) {
  const i = `<span class="stat-note">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(menace)}</span>
  <span class="stat-note">${formatage} ${Intl.NumberFormat('fr-FR', { style: 'percent' }).format(leTaux)}
  </span> <span class="badge note-${badge}">${laNote}</span>`;
$(`#h${id}`).html(i);
};

/**
* [Description for remplissageHotspotListe]
* Fonction de remplissage du tableau avec les infos hotspot owasp A1-A10.
*
* @param string idMaven
*
* @return [type]
*
* Created at: 19/12/2022, 21:42:09 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
const remplissageHotspotListe=function(idMaven, referentielVersion) {
  if (idMaven === undefined || referentielVersion === undefined) {
    return;
  }

/**
 * On appel le l'API en charge de récupérer la liste des failles de type OWASP
 */
  const data={'maven_key': idMaven, 'referentiel_version': referentielVersion};
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/liste`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };
  $.ajax(options).then(r => {
    console.log('résultat', r);
    if (r.code===http_400) {
      const message=`[Hotspot] La requête n'est pas conforme (Erreur 400) !`;
      $('#message').html(callboxError+message+callboxFermer);
      videLeTableau();
      return;
    }
  let leTaux=1, laNote=['a','A'], formatage;
  const hotspotTotal=parseInt(r.menaceA1+r.menaceA2+r.menaceA3+r.menaceA4+
                              r.menaceA5+r.menaceA6+r.menaceA7+r.menaceA8+
                              r.menaceA9+r.menaceA10,10);

  formatage=espace;
  
  if ( hotspotTotal!==0 ){
    /* calcul A1 */
    leTaux = 1 - (parseInt(r.menaceA1,10)/hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
      formatage=rien;
      }
    injectionHotspotListe(un, formatage, r.menaceA1, leTaux, laNote[0], laNote[1]);

    /* calcul A2*/
    leTaux = 1 - (parseInt(r.menaceA2,10)/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
      } else {
        formatage=rien;
      }
    injectionHotspotListe(deux, formatage, r.menaceA2, leTaux, laNote[0], laNote[1]);

    /* calcul A3 */
    leTaux = 1 - (r.menaceA3/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
      formatage=rien;
    }
    injectionHotspotListe(trois, formatage, r.menaceA3, leTaux, laNote[0], laNote[1]);

    /* Calcul A4 */
    leTaux = 1 - (r.menaceA4/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
      formatage=rien;
    }
    injectionHotspotListe(quatre, formatage, r.menaceA4, leTaux, laNote[0], laNote[1]);

    /* calcul A5 */
    leTaux = 1 - (r.menaceA5/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
        formatage=rien;
    }
    injectionHotspotListe(cinq, formatage, r.menaceA5, leTaux, laNote[0], laNote[1]);

    /* Calcul A6 */
    leTaux = 1 - (r.menaceA6/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
        formatage=rien;
      }
    injectionHotspotListe(six, formatage, r.menaceA6, leTaux, laNote[0], laNote[1]);

    /* Calcul A7 */
    leTaux = 1 - (r.menaceA7 / hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
        formatage=rien;
    }
    injectionHotspotListe(sept, formatage, r.menaceA7, leTaux, laNote[0], laNote[1]);

    /* Calcul A8 */
    leTaux = 1 - (r.menaceA8/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ((leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
        formatage=rien;
      }
    injectionHotspotListe(huit, formatage, r.menaceA8, leTaux, laNote[0], laNote[1]);

    /* calcul A9 */
    leTaux = 1 - (r.menaceA9/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ((leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
        formatage=rien;
    }
    injectionHotspotListe(neuf, formatage, r.menaceA9, leTaux, laNote[0], laNote[1]);

    /* Calcul A10 */
    leTaux = 1 - (r.menaceA10/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ((leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
        formatage=rien;
    }
    injectionHotspotListe(dix, formatage, r.menaceA10, leTaux, laNote[0], laNote[1]);
  } else {
    for (let i=1; i<onze; i++){
      injectionHotspotListe(i, formatage, 0, leTaux, laNote[0], laNote[1]);
    }
  }
  });
};

/**
 * [Selection du référentiel ]
 * Created at: 24/05/2024, 15:33:47 (Europe/Paris)
 * @author     Zakaria GUEDDOU <zakaria.gueddou19@gmail.com>
 */
    document.getElementById('owasp-select').addEventListener('change', function () {
        const selectedValue = this.value;
        const owasp2017 = document.getElementById('owasp-2017');
        const owasp2021 = document.getElementById('owasp-2021');

        if (selectedValue === '2017') {
            owasp2017.style.display = 'block';
            owasp2021.style.display = 'none';
        } else if (selectedValue === '2021') {
            owasp2017.style.display = 'none';
            owasp2021.style.display = 'block';
        }
    });
    
// Données statiques pour les pourcentages de chaque type de vulnérabilité
const data2017 = [12, 15, 10, 8, 5, 20, 10, 8, 6, 6]; // Exemple de pourcentages pour 2017
const data2021 = [10, 18, 9, 12, 7, 15, 11, 8, 5, 5]; // Exemple de pourcentages pour 2021
const labels = [
    'Injection',
    'Broken Authentication',
    'Sensitive Data Exposure',
    'XML External Entities (XXE)',
    'Broken Access Control',
    'Security Misconfiguration',
    'Cross-Site Scripting (XSS)',
    'Insecure Deserialization',
    'Using Components with Known Vulnerabilities',
    'Insufficient Logging & Monitoring'
];

const ctx = document.getElementById('owasp-pie-chart').getContext('2d');
const owaspPieChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Répartition OWASP',
                data: data2017, // Données par défaut
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
                position: 'top'
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

document.getElementById('owasp-select').addEventListener('change', function () {
    const selectedValue = this.value;
    const owasp2017 = document.getElementById('owasp-2017');
    const owasp2021 = document.getElementById('owasp-2021');
    const owaspPieChartContainer = document.getElementById('owasp-pie-chart-container');

    if (selectedValue === '2017') {
        owasp2017.style.display = 'block';
        owasp2021.style.display = 'none';
        owaspPieChart.data.datasets[0].data = data2017;
        owaspPieChart.data.datasets[0].label = 'Répartition OWASP 2017';
        owaspPieChart.update();
        owaspPieChartContainer.style.display = 'block';
    } else if (selectedValue === '2021') {
        owasp2017.style.display = 'none';
        owasp2021.style.display = 'block';
        owaspPieChart.data.datasets[0].data = data2021;
        owaspPieChart.data.datasets[0].label = 'Répartition OWASP 2021';
        owaspPieChart.update();
        owaspPieChartContainer.style.display = 'block';
    } else {
        owaspPieChartContainer.style.display = 'none';
    }
});


/**
 * [Description for injectionHotspotDetails]
 * Injecte les ligne de détails pour les hotspot
 *
 * @param mixed numero
 * @param string url
 * @param string color
 * @param string rule
 * @param string severity
 * @param string file
 * @param integer line
 * @param string message
 * @param string status
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:44:32 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const injectionHotspotDetails=function(numero,url,color,rule,severity,file,line,message,status){
  const ligne = `<tr>
                  <td class="stat-note">${numero}</td>
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
 * @return [type]
 *
 * Created at: 19/12/2022, 21:45:32 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const injectionModule=function (module, total, taux, bc, zero){
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
      sessionStorage.set('Owasp : ' `Oups !!!, je ne connais pas ${module}.`);
  }
};

/**
 * [Description for remplissageHotspotDetails]
 * Affiche le tableau du détails des hotspot
 *
 * @param string idMaven
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:46:30 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageHotspotDetails=function(idMaven, referentielVersion) {
  /** Si la clé maven n'est pas défini on ne fait rien */
  if (idMaven === undefined || referentielVersion === undefined) {
    return;
  }

  const data={'maven_key': idMaven, 'referentiel_version': referentielVersion};
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/details`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  $.ajax(options).then(r => {
    if (r.code===http_400) {
      const message=`[Details] La requête n'est pas conforme (Erreur 400) !`;
      $('#message').html(callboxError+message+callboxFermer);
      videLeTableau();
      return;
    }

    let numero=0, monNumero, ligne, c, frontend=0, backend=0, autre=0;
    let vide, too, totalABC, zero='', bc;
    const serveurURL=$('#js-serveur').data('serveur');

    if (r.details.menaces===undefined || r['details']['menaces'].length == 0) {
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
        numero++;
        if (numero < dix) {
          monNumero = '0' + numero;
          } else {
          monNumero = numero;
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

        injectionHotspotDetails(monNumero, serveurURL, c, detail.rule, detail.severity,
                                detail.file, detail.line, detail.message, detail.status);
      }

    /** Met à jour la répartition par module */
    totalABC=parseInt(frontend+backend+autre,10);
    const moduleVert='note-a';
    const moduleOrange='note-d';
    const moduleRouge='note-e';

    if ((frontend<dix)) {
      zero='00';
    }
    if (frontend>neuf && frontend<cent) {
      zero='0';
    }

    /** Calcul pour le frontend */
    too=(frontend/totalABC);
    if (frontend<cent) {
      zero='00';
    }
    if (frontend >neuf && frontend <cent) {
      zero='0';
    }
    if (too*cent<trente) {
      bc=moduleVert;
    }
    if (too*cent>vingtNeuf && too*cent<soixanteDix) {
      bc=moduleOrange;
    }
    if (too*cent>soixanteNeuf) {
      bc=moduleRouge;
    }
    injectionModule('frontend',frontend, too, bc, zero);

    /** Calcul pour le backend */
    too=(backend/totalABC);
    if (backend<dix) {
      zero='00';
    }
    if (backend>neuf && backend<cent) {
      zero='0';
    }
    if (too*cent<trente) {
      bc=moduleVert;
    }
    if (too*cent>vingtNeuf && too*cent<soixanteDix) {
      bc=moduleOrange;
    }
    if (too*cent>soixanteNeuf) {
      bc=moduleRouge;
    }
    injectionModule('backend',backend, too, bc, zero);

    /** Calcul pour le backend */
    too=(autre/totalABC);
    if (autre<dix) {
      zero='00';
    }
    if (autre>neuf && autre<cent) {
      zero='0';
    }
    if (too*cent<trente) {
      bc=moduleVert;
    }
    if (too*cent>vingtNeuf && too*cent<soixanteDix) {
      bc=moduleOrange;
    }
    if (too*cent>soixanteNeuf) {
      bc=moduleRouge;
    }
    injectionModule('autre', autre, too, bc, zero);
  }
  });
};

/**
 * [Description for remplissageDetailsHotspotOwasp]
 * Permet d'afficher le détails de chaque hotspot
 *
 * @param string idMaven
 * @param string menace
 * @param string titre
 *
 * Created at: 19/12/2022, 21:49:48 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageDetailsHotspotOwasp=function(idMaven, menace, titre) {
  if (idMaven === undefined) {
    return;
  }

  const data={'maven_key': idMaven, menace};
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/severity`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  $.ajax(options).then(r=> {
    if (r.code===http_400) {
      const message=`[Severity] La requête n'est pas conforme (Erreur 400) !`;
      $('#message').html(callboxError+message+callboxFermer);
      return;
    }
    $('.details-titre').html(listeOwasp2017[titre]);
    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.high.total));
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.medium.total));
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.low.total));
  });
};

$('.js-details').on('click', function () {
  const id = $(this).attr('id').split('-');
  const kkey=sessionStorage.getItem('projet');
  if (id[1] === 'a1') {
    remplissageDetailsHotspotOwasp(kkey, 'a1', un);
  }
  if (id[1] === 'a2') {
    remplissageDetailsHotspotOwasp(kkey, 'a2', deux);
  }
  if (id[1] === 'a3') {
    remplissageDetailsHotspotOwasp(kkey, 'a3', trois);
  }
  if (id[1] === 'a4') {
    remplissageDetailsHotspotOwasp(kkey, 'a4', quatre);
  }
  if (id[1] === 'a5') {
    remplissageDetailsHotspotOwasp(kkey, 'a5', cinq);
  }
  if (id[1] === 'a6') {
    remplissageDetailsHotspotOwasp(kkey, 'a6', six);
  }
  if (id[1] === 'a7') {
    remplissageDetailsHotspotOwasp(kkey, 'a7', sept);
  }
  if (id[1] === 'a8') {
    remplissageDetailsHotspotOwasp(kkey, 'a8',huit);
  }
  if (id[1] === 'a9') {
    remplissageDetailsHotspotOwasp(kkey, 'a9', neuf);
  }
  if (id[1] === 'a10') {
    remplissageDetailsHotspotOwasp(kkey, 'a10', dix);
  }
  $('#details').foundation('open');
});

/*************** Main du programme **************/
/** On récupère la clé du projet */
const key=sessionStorage.getItem('projet');
const projet=key.split(':');
/** On met à jour la page */
$('#js-application').html(projet[1]);

/** On appel les fonctions de remplissage */
document.addEventListener('DOMContentLoaded', function() {
  const button2017 = document.getElementById('version2017');
  const button2021 = document.getElementById('version2021');

  const key = sessionStorage.getItem('projet');
  const projet = key.split(':');

  /** On met à jour la page */
  $('#js-application').html(projet[1]);

  // Ajoute une classe active au bouton sélectionné et appelle les fonctions de remplissage
  function selectVersion(referentielVersion) {
    if (referentielVersion === '2017') {
      button2017.classList.add('active');
      button2021.classList.remove('active');
    } else if (referentielVersion === '2021') {
      button2017.classList.remove('active');
      button2021.classList.add('active');
    }
    remplissageOwaspInfo(key, referentielVersion);
    remplissageHotspotInfo(key, referentielVersion);
    remplissageHotspotListe(key, referentielVersion);
    remplissageHotspotDetails(key, referentielVersion);
  }

  // Événements de clic pour les boutons
  button2017.addEventListener('click', function() {
    selectVersion('2017');
  });

  button2021.addEventListener('click', function() {
    selectVersion('2021');
  });
});
