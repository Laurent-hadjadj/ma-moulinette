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

/** On importe les paramètres serveur */
import {serveur} from './properties.js';

/** On importe les constantes */
import {contentType, couleur, note, espace, rien,
        http_406, listeOwasp2017,
        un, deux, trois, quatre, cinq, six, sept, huit, neuf, dix, onze,
        vingtNeuf, trente, soixanteNeuf, soixanteDix, cent} from './constante';

/**
 * [Description for calculNoteHotspot]
 * Calcul la note des hotspots
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
 * @param mixed id
 * @param mixed menace
 * @param mixed badge
 * @param mixed laNote
 *
 * @return [type]
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
* [Description for remplissageOwaspInfo]
* Récupération des informations sur les vulnérabilités OWASP.
*
* @param mixed idMaven
*
* @return [type]
*
* Created at: 19/12/2022, 21:32:27 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
const remplissageOwaspInfo=function(idMaven) {
  if (idMaven === undefined) {
    return;
  }

  const data={'mavenKey': idMaven };
  const options = {
    url: `${serveur()}/api/peinture/owasp/liste`, type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(options).then(r => {
    /** 406 = code HTTP */
    if (r.code === http_406){
        console.info('Le projet n\'existe pas..');
        return;
      }
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
    /** on injecte : ID, Menance, Badge Note */
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

    /** on injecte : ID, Menance, Badge Note */
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
    /** on injecte : ID, Menance, Badge Note */
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
    /** on injecte : ID, Menance, Badge Note */
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
    /** on injecte : ID, Menance, Badge Note */
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
    /** on injecte : ID, Menance, Badge Note */
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
    /** on injecte : ID, Menance, Badge Note */
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
    /** on injecte : ID, Menance, Badge Note */
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
    /** on injecte : ID, Menance, Badge Note */
    injectionOwaspInfo(dix, r.a10, c, n);
  });
};


/**
 * [Description for remplissageHotspotInfo]
 * Récupération des informations sur les hotspots OWASP
 *
 * @param mixed idMaven
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:39:57 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageHotspotInfo=function(idMaven) {
  if (idMaven === undefined) {
    return;
  }

  const data={'mavenKey': idMaven };
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/info/`, type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(options).then(r=> {
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
      <span class="badge ${lowerLaNote}"> ${laNote[1]}</span>`;
  $('#note-hotspot').html(i);
  });
};

/**
* [Description for injectionHotspotListe]
* Fonction qui permet d'injecter dans la page les calcul des hotspots
*
* @param mixed id
* @param mixed espace
* @param mixed menace
* @param mixed leTaux
* @param mixed badge
* @param mixed laNote
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
* @param mixed idMaven
*
* @return [type]
*
* Created at: 19/12/2022, 21:42:09 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
const remplissageHotspotListe=function(idMaven) {
  if (idMaven === undefined) {
    return;
  }

/**
 * On appel le l'API en charge de récupérer la liste des failles de type OWASP
 */
  const data={'mavenKey': idMaven };
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/liste`, type: 'GET',
          dataType: 'json', data, contentType };

  $.ajax(options).then(r => {
  let leTaux=1, laNote=['a','A'], formatage;
  const hotspotTotal=parseInt(r.menaceA1+r.menaceA2+r.menaceA3+r.menaceA4+
                              r.menaceA5+r.menaceA6+r.menaceA7+r.menaceA8+
                              r.menaceA9+r.menaceA10,10);

  if ( hotspotTotal!==0 ){
    /* calcul A1 */
    leTaux = 1 - (parseInt(r.menaceA1,10)/hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*cent)>dix && (leTaux*cent)<cent) {
      formatage=espace+espace+espace;
    } else {
      formatage=rien;
      }
    if ( leTaux*cent===cent) {
      formatage=espace;
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
    if ( leTaux*cent===cent) {
      formatage=espace;
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
    if ( leTaux*cent===cent) {
      formatage=espace;
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
    if ( leTaux*cent===cent) {
      formatage=espace;
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
    if ( leTaux*cent===cent) {
      formatage=espace;
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
    if ( leTaux*cent===cent) {
      formatage=espace;
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
    if ( leTaux*cent===cent) {
      formatage=espace;
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
    if ( leTaux*cent===cent) {
      formatage=espace;
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
    if (leTaux*cent===cent) {
      formatage=espace;
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
    if (leTaux*cent===cent) {
      formatage=espace;
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
 * [Description for injectionHotspotDetails]
 * Injecte les ligne de détails pour les hotpsots
 *
 * @param mixed numero
 * @param mixed url
 * @param mixed color
 * @param mixed rule
 * @param mixed severity
 * @param mixed file
 * @param mixed line
 * @param mixed message
 * @param mixed status
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
 * @param mixed module
 * @param mixed total
 * @param mixed taux
 * @param mixed bc
 * @param mixed zero
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
      console.info(`Oops !!!, je ne connais pas ${module}.`);
  }
};

/**
 * [Description for remplissageHotspotDetails]
 * Affiche le tableau du détails des hotspots
 *
 * @param mixed idMaven
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:46:30 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageHotspotDetails=function(idMaven) {
  if (idMaven === undefined) {
    return;
  }

  const data={'mavenKey': idMaven };
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/details`, type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(options).then(r => {
    let numero=0, monNumero, ligne, c, frontend=0, backend=0, autre=0;
    let vide, too, totalABC, zero='', bc;
    const serveurURL=$('#js-serveur').data('serveur');
    if (r.details==='vide') {
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
 * @param mixed idMaven
 * @param mixed menace
 * @param mixed titre
 *
 * @return [type]
 *
 * Created at: 19/12/2022, 21:49:48 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageDetailsHotspotOwasp=function(idMaven, menace, titre) {
  if (idMaven === undefined) {
    return;
  }

  const data={'mavenKey': idMaven, menace };
  const options = {
    url: `${serveur()}/api/peinture/owasp/hotspot/severity`, type: 'GET',
          dataType: 'json', data, contentType };

  $.ajax(options).then(r=> {
  $('.details-titre').html(listeOwasp2017[titre]);
  $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.high.total));
  $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.medium.total));
  $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.low.total));
  });
};

$('.js-details').on('click', function () {
  const id = $(this).attr('id').split('-');
  const kkey=localStorage.getItem('projet');
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
const key=localStorage.getItem('projet');
const projet=key.split(':');
/** On met à jour la page */
$('#js-application').html(projet[1]);

/** On appel les fonctions de remplissage */
remplissageOwaspInfo(key);
remplissageHotspotInfo(key);
remplissageHotspotListe(key);
remplissageHotspotDetails(key);
