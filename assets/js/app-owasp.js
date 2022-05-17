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

console.log('Owasp : Chargement de webpack !');

const contentType='application/json; charset=utf-8';
const note = ['', 'A', 'B', 'C', 'D', 'E'];
const couleur = ['', 'badge-vert1', 'badge-vert2', 'badge-jaune', 'badge-orange', 'badge-rouge'];

const listeOwasp2017 = [
  '', 'A1 - Attaques d\'injection', 'A2 - Authentification défaillante', 'A3 - Fuites de données sensibles',
  'A4 - Entités externes XML (XXE)', 'A5 - Contrôle d\'accès défaillant', 'A6 - Configurations défaillantes',
  'A7 - Attaques cross-site scripting (XSS)', 'A8 - Désérialisation sans validation', 'A9 - Composants tiers vulnérables',
  'A10 - Journalisation et surveillance insuffisantes'];

/**
 * description
 * Calcul la note des hotspots
 *
 * @param {*} taux
 * @returns
 */
const calculNoteHotspot=function(taux) {
  let c, n;
  if (taux > 0.79) {
    c = couleur[1];
    n = note[1];
  }
  if (taux > 0.71 && taux < 0.81) {
      c = couleur[2];
      n = note[2];
    }
  if (taux > 0.51 && taux < 0.71) {
      c = couleur[3];
      n = note[3];
    }
  if (taux > 0.31 && taux < 0.51) {
      c = couleur[4];
      n = note[4];
    }
  if (taux < 0.31) {
      c = couleur[5];
      n = note[5];
    }
  return [c, n];
};

/**
 *
 * description
 * Fonction qui permet d'injecter dans la page les calcul des Owasp
 *
 * @param {*} id
 * @param {*} menace
 * @param {*} badge
 * @param {*} laNote
 */
const injectionOwaspInfo=function(id, menace, badge, laNote) {
  const i =`<span class="stat-note">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(menace)}</span>
            <span class="badge ${badge}">${laNote}</span>`;
  $(`#a${id}`).html(i);
};

/**
 * description
 * Récupération des informations sur les vulnérabilités OWASP.
 *
 * @param {*} idMaven
 * @returns
 */
const remplissageOwaspInfo=function(idMaven) {
  if (idMaven === undefined) {
    return;
  }

  const data={'mavenKey': idMaven };
  const options = {
    url: 'http://localhost:8000/api/peinture/owasp/liste', type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(options).then(r => {
    if (r.code === 406){
        console.info('Le projet n\'existe pas..');
        return;
      }
    // On ajoute les valeurs pour les vulnérabilités
    $('#nombre-faille-owasp').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.total));
    $('#nombre-faille-bloquant').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.bloquant));
    $('#nombre-faille-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.critique));
    $('#nombre-faille-majeur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.majeur));
    $('#nombre-faille-mineur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.mineur));

    let c=[],n=[];

    /* Détails A1 */
    if (parseInt(r.a1Blocker + r.a1Critical + r.a1Major + r.a1Minor,10) === 0){
      c = couleur[1];
      n = note[1];
    }
    if (parseInt(r.a1Minor,10) > 1) {
      c = couleur[2];
      n = note[2];
    }
    if (parseInt(r.a1Major,10) > 1) {
      c = couleur[3];
      n = note[3];
     }
    if (parseInt(r.a1Critical,10) > 1) {
      c = couleur[4];
      n = note[4];
    }
    if (parseInt(r.a1Blocker,10) > 1) {
      c = couleur[5];
      n = note[5];
    }
    injectionOwaspInfo(1, r.a1, c, n);

    // Détails A2
    if (parseInt(r.a2Blocker + r.a2Critical + r.a2Major + r.a2Minor,10) === 0) {
      c = couleur[1];
      n = note[1];
    }
    if (parseInt(r.a2Minor,10) > 1) {
      c = couleur[2];
      n = note[2];
    }
    if (parseInt(r.a2Major,10) > 1) {
      c = couleur[3];
      n = note[3];
    }
    if (parseInt(r.a2Critical,10) > 1) {
      c = couleur[4];
      n = note[4];
    }
    if (parseInt(r.a2Blocker,10) > 1) {
      c = couleur[5];
      n = note[5];
    }

    injectionOwaspInfo(2, r.a2, c, n);

    /* Détails A3 */
    if (parseInt(r.a3Blocker + r.a3Critical + r.a3Major + r.a3Minor,10) === 0) {
      c = couleur[1];
      n = note[1];
    }
    if (parseInt(r.a3Minor,10) > 1) {
      c = couleur[2];
      n = note[2];
    }
    if (parseInt(r.a3Major,10) > 1) {
      c = couleur[3];
      n = note[3];
    }
    if (parseInt(r.a3Critical,10) > 1) {
      c = couleur[4];
      n = note[4];
    }
    if (parseInt(r.a3Blocker,10) > 1) {
      c = couleur[5];
      n = note[5];
    }
    injectionOwaspInfo(3, r.a3, c, n);

    /* Détails A4 */
    if (parseInt(r.a4Blocker + r.a1Critical + r.a1Major + r.a1Minor,10) === 0) {
      c = couleur[1];
      n = note[1];
    }
    if (parseInt(r.a4Minor,10) > 1) {
       c = couleur[2];
       n = note[2];
      }
    if (parseInt(r.a4Major,10) > 1) {
      c = couleur[3];
      n = note[3];
    }
    if (parseInt(r.a4Critical,10) > 1) {
      c = couleur[4];
      n = note[4];
    }
    if (parseInt(r.a4Blocker,10) > 1) {
      c = couleur[5];
      n = note[5];
    }
    injectionOwaspInfo(4, r.a4, c, n);

    /* Détails A5 */
    if (parseInt(r.a5Blocker + r.a5Critical + r.a5Major + r.a5Minor,10) === 0) {
      c = couleur[1];
      n = note[1];
    }
    if (parseInt(r.a5Minor,10) > 1) {
      c = couleur[2];
      n = note[2];
    }
    if (parseInt(r.a5Major,10) > 1) {
      c = couleur[3];
      n = note[3];
    }
    if (parseInt(r.a5Critical,10) > 1) {
      c = couleur[4];
      n = note[4];
    }
    if (parseInt(r.a5Blocker,10) > 1) {
      c = couleur[5];
      n = note[5];
    }
    injectionOwaspInfo(5, r.a5, c, n);

    /* Détails A6 */
    if (parseInt(r.a6Blocker + r.a6Critical + r.a6Major + r.a6Minor,10) === 0) {
      c = couleur[1];
      n = note[1];
    }
    if (parseInt(r.a6Minor,10) > 1) {
      c = couleur[2];
      n = note[2];
    }
    if (parseInt(r.a6Major,10) > 1) {
      c = couleur[3];
      n = note[3];
    }
    if (parseInt(r.a6Critical,10) > 1) {
      c = couleur[4];
      n = note[4];
    }
    if (parseInt(r.a6Blocker,10) > 1) {
      c = couleur[5];
      n = note[5];
    }
    injectionOwaspInfo(6, r.a6, c, n);

    /* Détails A7 */
    if (parseInt(r.a7Blocker + r.a7Critical + r.a7Major + r.a7Minor,10) === 0) {
       c = couleur[1];
       n = note[1];
      }
    if (parseInt(r.a7Minor,10) > 1) {
      c = couleur[2];
      n = note[2];
    }
    if (parseInt(r.a7Major,10) > 1) {
      c = couleur[3];
      n = note[3];
    }
    if (parseInt(r.a7Critical,10) > 1) {
      c = couleur[4];
      n = note[4];
    }
    if (parseInt(r.a7Blocker,10) > 1) {
      c = couleur[5];
      n = note[5];
    }
    injectionOwaspInfo(7, r.a7, c, n);

    /* Détails A8 */
    if (parseInt(r.a8Blocker + r.a8Critical + r.a8Major + r.a8Minor,10) === 0) {
      c = couleur[1];
      n = note[1];
    }
    if (parseInt(r.a8Minor,10) > 1) {
       c = couleur[2];
       n = note[2];
      }
    if (parseInt(r.a8Major,10) > 1) {
      c = couleur[3];
      n = note[3];
    }
    if (parseInt(r.a8Critical,10) > 1) {
      c = couleur[4];
      n = note[4];
    }
    if (parseInt(r.a8Blocker,10) > 1) {
      c = couleur[5];
      n = note[5];
    }
    injectionOwaspInfo(8, r.a8, c, n);

    /* Détails A9 */
    if (parseInt(r.a9Blocker + r.a9Critical + r.a9Major + r.a9Minor,10) === 0) {
      c = couleur[1];
      n = note[1];
    }
    if (parseInt(r.a9Minor,10) > 1) {
      c = couleur[2];
      n = note[2];
    }
    if (parseInt(r.a9Major,10) > 1) {
      c = couleur[3];
      n = note[3];
    }
    if (parseInt(r.a9Critical,10) > 1) {
      c = couleur[4];
      n = note[4];
    }
    if (parseInt(r.a9Blocker,10) > 1) {
      c = couleur[5];
      n = note[5];
    }
    injectionOwaspInfo(9, r.a9, c, n);

    /* Détails A10 */
    if (parseInt(r.a10Blocker + r.a10Critical + r.a10Major + r.a10Minor,10) === 0) {
      c = couleur[1];
      n = note[1];
    }
    if (parseInt(r.a10Minor,10) > 1) {
      c = couleur[2];
      n = note[2];
    }
    if (parseInt(r.a10Major,10) > 1) {
      c = couleur[3];
      n = note[3];
     }
    if (parseInt(r.a10Critical,10) > 1) {
       c = couleur[4];
       n = note[4];
      }
    if (parseInt(r.a10Blocker,10) > 1) {
       c = couleur[5];
       n = note[5];
      }
      injectionOwaspInfo(10, r.a10, c, n);
  });
};

/**
 * description
 * Récupération des informations sur les hotspots OWASP
 *
 * @param {*} idMaven
 * @returns
 */
const remplissageHotspotInfo=function(idMaven) {
  if (idMaven === undefined) {
    return;
   }

  const data={'mavenKey': idMaven };
  const options = {
    url: 'http://localhost:8000/api/peinture/owasp/hotspot/info/', type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(options).then(r=> {
  let i='';
  // On compte le nombre de hotspot au status REVIEWED
  $('#hotspot-reviewed').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.reviewed));
  // On compte le nombre de hotspot au status TO_REVIEW
  $('#hotspot-to-review').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.toReview));
  const hotspotToReview = r.toReview;

 // On affiche le nombre de hotspot OWASP et par la répartition
  $('#hotspot-total').html(r.total);
  const hotspotTotal=r.total;
  $('#nombre-hotspot-high').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.high));
  $('#nombre-hotspot-medium').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.medium));
  $('#nombre-hotspot-low').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.low));

  let leTaux=1, laNote=['badge-vert1', 'A'];
  if ( hotspotTotal !==0 ) {
    leTaux = 1 - (parseInt(hotspotToReview,10) / hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
  }

  i = `<span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(leTaux)}</span>
       <span class="badge ${laNote[0]}"> ${laNote[1]}</span>`;
  $('#note-hotspot').html(i);
  });
};

/**
 * description
 * Fonction qui permet d'injecter dans la page les calcul des hotspots
 *
 * @param {*} id
 * @param {*} espace
 * @param {*} menace
 * @param {*} badge
 * @param {*} note
 */
const injectionHotspotListe=function(id, espace, menace, leTaux, badge, laNote) {
  const i = `<span class="stat-note">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(menace)}</span>
  <span class="stat-note">${espace} ${Intl.NumberFormat('fr-FR', { style: 'percent' }).format(leTaux)}
  </span> <span class="badge ${badge}">${laNote}</span>`;
$(`#h${id}`).html(i);
};

/**
 * description
 * Fonction de remplissage du tableau avec les infos hotspot owasp A1-A10.
 *
 * @param {*} idMaven
 * @returns
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
    url: 'http://localhost:8000/api/peinture/owasp/hotspot/liste', type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(options).then(r=> {
  let leTaux=1, laNote=['badge-vert1', 'A'], espace='';
  const hotspotTotal=r.menaceA1+r.menaceA2+r.menaceA3+r.menaceA4+r.menaceA5+
                     r.menaceA6+r.menaceA7+r.menaceA8+r.menaceA9+r.menaceA10;

  const html1='&nbsp;';
  const html2='&nbsp;&nbsp;&nbsp;';

  if ( hotspotTotal !==0 ){
    /* calcul A1 */
    leTaux = 1 - (parseInt(r.menaceA1,10)/hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
    } else {
        espace='';
      }
    if ( leTaux*100===100) {
      espace=html1;
    }
    injectionHotspotListe(1, espace, r.menaceA1, leTaux, laNote[0], laNote[1]);

    /* calcul A2*/
    leTaux = 1 - (parseInt(r.menaceA2,10)/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
      } else {
        espace='';
      }
    if ( leTaux*100===100) {
      espace=html1;
    }
    injectionHotspotListe(2, espace, r.menaceA2, leTaux, laNote[0], laNote[1]);

    /* calcul A3 */
    leTaux = 1 - (r.menaceA3/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
    } else {
      espace='';
    }
    if ( leTaux*100===100) {
      espace=html1;
     }
     injectionHotspotListe(3, espace, r.menaceA3, leTaux, laNote[0], laNote[1]);

    /* Calcul A4 */
    leTaux = 1 - (r.menaceA4/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
    } else {
      espace='';
    }
    if ( leTaux*100===100) {
      espace=html1;
    }
    injectionHotspotListe(4, espace, r.menaceA4, leTaux, laNote[0], laNote[1]);

    /* calcul A5 */
    leTaux = 1 - (r.menaceA5/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
    } else {
        espace='';
    }
    if ( leTaux*100===100) {
      espace=html1;
    }
    injectionHotspotListe(5, espace, r.menaceA5, leTaux, laNote[0], laNote[1]);

    /* Calcul A6 */
    leTaux = 1 - (r.menaceA6/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
    } else {
        espace='';
      }
    if ( leTaux*100===100) {
      espace=html1;
    }
    injectionHotspotListe(6, espace, r.menaceA6, leTaux, laNote[0], laNote[1]);

    /* Calcul A7 */
    leTaux = 1 - (r.menaceA7 / hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ( (leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
    } else {
         espace='';
    }
    if ( leTaux*100===100) {
      espace=html1;
    }
    injectionHotspotListe(7, espace, r.menaceA7, leTaux, laNote[0], laNote[1]);

    /* Calcul A8 */
    leTaux = 1 - (r.menaceA8/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ((leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
    } else {
        espace='';
      }
    if ( leTaux*100===100) {
      espace=html1;
    }
    injectionHotspotListe(8, espace, r.menaceA8, leTaux, laNote[0], laNote[1]);

    /* calcul A9 */
    leTaux = 1 - (r.menaceA9/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ((leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
    } else {
        espace='';
      }
    if (leTaux*100===100) {
       espace=html1;
      }
      injectionHotspotListe(9, espace, r.menaceA9, leTaux, laNote[0], laNote[1]);

    /* Calcul A10 */
    leTaux = 1 - (r.menaceA10/ hotspotTotal);
    laNote = calculNoteHotspot(leTaux);
    if ((leTaux*100)>10 && (leTaux*100)<100) {
      espace=html2;
    } else {
        espace='';
    }
    if (leTaux*100===100) {
      espace=html1;
    }
    injectionHotspotListe(10, espace, r.menaceA10, leTaux, laNote[0], laNote[1]);
   } else {
    for (let i=1; i<11; i++){
      injectionHotspotListe(i, espace, 0, leTaux, laNote[0], laNote[1]);
    }
   }
  });
};

/**
 *
 *  Description
 *  Injecte les ligne de détails pour les hotpsots
 *
 * @param {*} numero
 * @param {*} url
 * @param {*} couleur
 * @param {*} rule
 * @param {*} severity
 * @param {*} file
 * @param {*} line
 * @param {*} message
 * @param {*} status
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
 * injectionModule
 *
 * @param {*} module
 * @param {*} total
 * @param {*} taux
 * @param {*} bc
 * @param {*} zero
 */
const injectionModule=function (module,total, taux, bc, zero){
console.log(module, taux, bc, zero);
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
 * description
 * Affiche le tableau du détails des hotspots
 *
 * @param {*} idMaven
 * @returns
 */
const remplissageHotspotDetails=function(idMaven) {
  if (idMaven === undefined) {
    return;
  }

  const data={'mavenKey': idMaven };
  const options = {
    url: 'http://localhost:8000/api/peinture/owasp/hotspot/details', type: 'GET',
    dataType: 'json', data, contentType };

  $.ajax(options).then(r=> {
    let numero=0, monNumero, ligne, c, frontend=0, backend=0, autre=0;
    let vide, too, totalABC, zero='', bc;
    const serveur=$('#js-serveur').data('serveur');
    if (r.details==='vide') {
        // On met ajour la répartition par module
        vide = `<span class="stat-note">
        ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>
              <span class="stat-note">
              ${Intl.NumberFormat('fr-FR', { style: 'percent' }).format(0)}</span>`;
        $('#frontend').html(vide);
        $('#backend').html(vide);
        $('#autre').html(vide);

        // On ajoute une ligne dans le tableau
        ligne = `<tr class="text-center">
                  <td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>Pas de failles</td>
                 </tr>`;
         $('#tbody').html(ligne);
      } else {
      // On efface le tableau et on ajoute les lignes
      // On calcul l'impact sur les modules
      $('#tbody').html('');
      for ( const detail of r.details){
        numero++;
        if (numero < 10) {
          monNumero = '0' + numero;
          } else {
          monNumero = numero;
        }
        if (detail.severity === 'LOW') {
          c = 'severity-jaune';
        }
        if (detail.severity === 'MEDIUM') {
          c = 'severity-orange';
        }
        if (detail.severity === 'HIGH') {
          c = 'severity-rouge';
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

        injectionHotspotDetails(monNumero, serveur, c, detail.rule, detail.severity,
                                detail.file, detail.line, detail.message, detail.status);
      }

    // Met à jour la répartition par module
    totalABC=parseInt(frontend+backend+autre,10);
    const moduleVert='module-vert';
    const moduleOrange='module-orange';
    const moduleRouge='module-rouge';

    if ((frontend <10)) {
      zero='00';
    }
    if (frontend >9 && frontend <100) {
      zero='0';
    }

    // Calcul pour le frontend
    too=(frontend/totalABC);
    if (frontend <10) {
      zero='00';
    }
    if (frontend >9 && frontend <100) {
      zero='0';
    }
    if (too*100 <30) {
      bc=moduleVert;
    }
    if (too*100 >29 && too*100 <70) {
      bc=moduleOrange;
    }
    if (too*100 >69) {
      bc=moduleRouge;
    }
    injectionModule('frontend',frontend, too, bc, zero);

    // Calcul pour le backend
    too=(backend/totalABC);
    if (backend<10) {
      zero='00';
    }
    if (backend>9 && backend<100) {
      zero='0';
    }
    if (too*100 <30) {
      bc=moduleVert;
    }
    if (too*100 >29 && too*100 <70) {
       bc=moduleOrange;
      }
    if (too*100 >69) {
      bc=moduleRouge;
    }
    injectionModule('backend',backend, too, bc, zero);

    // Calcul pour le backend
    too=(autre/totalABC);
    if (autre<10) {
      zero='00';
    }
    if (autre>9 && autre<100) {
      zero='0';
    }
    if (too*100 <30) {
      bc=moduleVert;
    }
    if (too*100 >29 && too*100 <70) {
      bc=moduleOrange;
    }
    if (too*100 >69) {
      bc=moduleRouge;
    }
    injectionModule('autre',autre, too, bc, zero);
  }
 });
};

/**
 * Description
 * Permet d'afficher le détails de chaque hotspot
 *
 * @param {*} idMaven
 * @param {*} menace
 * @param {*} titre
 * @returns
 */
const remplissageDetailsHotspotOwasp=function(idMaven, menace, titre) {
  if (idMaven === undefined) {
    return;
  }

  const data={'mavenKey': idMaven, menace };
  const options = {
    url: 'http://localhost:8000/api/peinture/owasp/hotspot/severity', type: 'GET',
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
    remplissageDetailsHotspotOwasp(kkey, 'a1',1);
  }
  if (id[1] === 'a2') {
    remplissageDetailsHotspotOwasp(kkey, 'a2',2);
  }
  if (id[1] === 'a3') {
    remplissageDetailsHotspotOwasp(kkey, 'a3',3);
  }
  if (id[1] === 'a4') {
    remplissageDetailsHotspotOwasp(kkey, 'a4',4);
  }
  if (id[1] === 'a5') {
    remplissageDetailsHotspotOwasp(kkey, 'a5',5);
  }
  if (id[1] === 'a6') {
    remplissageDetailsHotspotOwasp(kkey, 'a6',6);
  }
  if (id[1] === 'a7') {
    remplissageDetailsHotspotOwasp(kkey, 'a7',7);
  }
  if (id[1] === 'a8') {
    remplissageDetailsHotspotOwasp(kkey, 'a8',8);
  }
  if (id[1] === 'a9') {
    remplissageDetailsHotspotOwasp(kkey, 'a9',9);
  }
  if (id[1] === 'a10') {
    remplissageDetailsHotspotOwasp(kkey, 'a10',10);
  }
  $('#details').foundation('open');
});

/*************** Main du programme **************/
// On récupère la clé du projet
const key=localStorage.getItem('projet');
const projet=key.split(':');
// On met à jour la page
$('#js-application').html(projet[1]);

// On appel les fonctions de remplissage
remplissageOwaspInfo(key);
remplissageHotspotInfo(key);
remplissageHotspotListe(key);
remplissageHotspotDetails(key);
