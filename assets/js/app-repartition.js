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

import '../css/repartition.css';

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
import { contentType, cent, mille, soixante } from './constante.js';

/* Construction des callbox pour les messages utilisateurs */
const callboxInformation='<div class="callout primary text-justify" data-closable="slide-out-right"><p style="color:#00445b;" class="open-sans" cell">Information ! ';
const callboxError='<div class="callout alert text-justify" data-closable="slide-out-right"><p style="color:#00445b;" class="open-sans" cell">Ooups ! ';
const callboxFermer='<button class="close-button" aria-label="Fermer la fenêtre" type="button" data-close><span aria-hidden="true">&times;</span></button></div>';

/** On récupère la clé maven de la clé de l'application. */
const t0 = document.getElementById('app');
const _mavenkey=t0.dataset.application;

/**
 * [Description for timestamp]
 * On lance le service de suppression des données pour le projet
 *
 * @param mixed bypass
 * @param bypass
 * @returns [type]
 *
 * Created at: 19/12/2022, 22:30:53 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const timestamp=function(bypass) {
  /**
   * On récupère la version du setup
   *  Si le projet n'a pas de setup (i.e. la valeur est à NaN), on en créé un.
   *  Si le projet à déjà un setup, on en créé un aussi pour ajouter une nouvelle version
   */

  /** On récupère les propriétés de l'élément. */
  const t99 = document.getElementById('js-setup');
  /** On renvoie la valeur du setup. */
  if (bypass==='by-pass') {
      return $('#js-setup').text();
    }

  /**
   * Si on a pas de setup pour le projet, on en ajoute un
   * on passe du statut 'NaN' à 'nouveau'
   */
  if ($('#js-setup').text()==='NaN') {
      t99.dataset.setup=Date.now();
      $('#js-setup').text(t99.dataset.setup);
      t99.dataset.statut='nouveau';
      const message1=`Je n'ai pas trouvé de setup. Un nouveau setup a été créé : ${t99.dataset.setup}.`;
      $('#message').html(callboxInformation+message1+callboxFermer);
      return t99.dataset.setup;

    }

  /**
   * Si le setup existe et que son statut est égal à 'nouveau'
   * alors on renvoie le même numéro de setup
   */
  if ($('#js-setup').text()!=='NaN' && t99.dataset.statut==='nouveau') {
      return t99.dataset.setup;
  }

  /**
   * Si le setup existe et que son statut est égal à 'actuel'
   * alors on renvoie un nouveau setup et on passe au statut 'nouveau'
   */
  if  ($('#js-setup').text()!=='NaN' && t99.dataset.statut==='actuel'){
      t99.dataset.setup=Date.now();
      const archive=$('#js-setup').text();
      const message2=`La version ${archive} est archivée. Un nouveau setup a été créé : ${t99.dataset.setup}.`;
      $('#js-setup').text(t99.dataset.setup);
      t99.dataset.statut='nouveau';
      $('#message').html(callboxInformation+message2+callboxFermer);
      return t99.dataset.setup;
  }

  /** si je suis perdu, j'affiche une alerte !!! */
  const message3=`Je suis perdu !!!`;
  $('#message').html(callboxError+message3+callboxFermer);
};

/**
  * [Description for clear]
  * On lance le service de suppression des données pour le projet
  * On fait un PUT et non un DELETE
  *
  * @param mixed mavenKey
  * @param mavenKey
  * @returns [type]
  *
  * Created at: 19/12/2022, 22:32:17 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  */
const clear=function(mavenKey) {

  /** On bind les variables */
  const data = { mavenKey, mode:'null' };
  const options = {
      url: `${serveur()}/api/projet/repartition/clear`,
      type: 'PUT', dataType: 'json', data: JSON.stringify(data), contentType,
    };

  /** On appel le web service. */
  return $.ajax(options).then( t => {
    if (t.code!=='OK') {
      const message=`Je n'ai pas réussi à supprimer les données du projet (${t.code}).`;
      $('#message').html(callboxError+message+callboxFermer);
    } else {
      const message='Les données du projet on été effacées.';
      $('#message').html(callboxInformation+message+callboxFermer);
    }
  });
};

/**
 * [Description for analyse]
 * On lance le service d'analyse des données pour le projet
 *
 * @param mixed mavenKey
 * @param mixed type
 * @param mixed severity
 * @param mixed css
 * @param mavenKey
 * @param type
 * @param severity
 * @param css
 * @returns [type]
 *
 * Created at: 19/12/2022, 22:33:18 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const analyse=function(mavenKey, type, severity, css){

  /**
   * Traitement lancé au moment moment de l'appel.
   *
   * @param a
   */
  const startAnalyse = a => {
    let dino;
    if (a==='BUG'){
        dino='Bug :';
      }
    if (a==='VULNERABILITY'){
        dino='Vulnérabilité :';
      }
    if (a==='CODE_SMELL'){
      dino='Mauvaise Pratique :';
    }
    $('#analyse-animation').addClass('sp-volume');
    $('#analyse-texte').html(dino + ' Analyse en cours...');
  };

  /** Traitement lancé à la fin du 'appel. */
  const stopAnalyse = () => {
    $('#analyse-animation').removeClass('sp-volume');
    $('#analyse-texte').html('<span class="open-sans">Satut : Fin du traitement.</span>');
  };

  /** On récupère le setup affiché à l'écran. */
  const setup=timestamp('by-pass');

  /** On déclare les options du web services. */
  const data = { mavenKey, type, severity, setup, mode:'null' };
  const options = {
    url: `${serveur()}/api/projet/repartition/analyse`, type: 'PUT',
    dataType: 'json', data: JSON.stringify(data), contentType,
    beforeSend: function () { setTimeout(() => startAnalyse(type), 1); },
    complete: function () { setTimeout(() => stopAnalyse(), 1); }
  };

  /** On appel le web service. */
  /** On utilise une promise et un callback */
  return new Promise(resolve => {
    $.ajax(options).then( t => {

      let alert, idc;

      /** On récupère les valeurs des anomalies */
      const t2 = document.getElementById('bug-bloquant');
      const t3 = document.getElementById('bug-critique');
      const t4 = document.getElementById('bug-info');
      const t5 = document.getElementById('bug-majeur');
      const t6 = document.getElementById('bug-mineur');
      const t8 = document.getElementById('vulnerabilite-bloquant');
      const t9 = document.getElementById('vulnerabilite-critique');
      const t10 = document.getElementById('vulnerabilite-info');
      const t11 = document.getElementById('vulnerabilite-majeur');
      const t12 = document.getElementById('vulnerabilite-mineur');
      const t14 = document.getElementById('mauvaise-pratique-bloquant');
      const t15 = document.getElementById('mauvaise-pratique-critique');
      const t16 = document.getElementById('mauvaise-pratique-info');
      const t17 = document.getElementById('mauvaise-pratique-majeur');
      const t18 = document.getElementById('mauvaise-pratique-mineur');

     /* On calcule la somme des anomalies */
      const somme=t.repartition.frontend+t.repartition.backend+t.repartition.autre;
      /* On regarde si la somme est =0 et erreur > 0 */
      let parser='OK';
      if (somme===0 && t.repartition.erreur>0) {
        parser='KO';
        const erreur=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(t.repartition.erreur);
        const message2=`Il y a eu ${erreur} erreurs lors de l'analyse.`;
        $('#message-analyse').html(callboxError+message2+callboxFermer);
      } else {
        const message2=`Tout va bien.`;
        $('#message-analyse').html(callboxInformation+message2+callboxFermer);
      }

      /* On affiche le tableau pour la fiabilité si le parser est OK */
      let labelSeverity;
      if (type==='BUG' && parser==='OK'){
        if (severity==='BLOCKER') {
          labelSeverity='Bloquant';
          if (t2.dataset.nombreBugBloquant==='0'){
            idc='-';
          } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t2.dataset.nombreBugBloquant,10));
            }
          }
        if (severity==='CRITICAL') {
          labelSeverity='Critique';
          if (t3.dataset.nombreBugCritique==='0'){
            idc='-';
          } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t3.dataset.nombreBugCritique,10));
            }
          }
        if (severity==='INFO') {
          labelSeverity='Info';
          if (t4.dataset.nombreBugInfo==='0'){
            idc='-';
          } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t4.dataset.nombreBugInfo,10));
            }
          }
        if (severity==='MAJOR') {
          labelSeverity='Majeur';
          if (t5.dataset.nombreBugMajeur==='0'){
            idc='-';
          } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t5.dataset.nombreBugMajeur,10));
            }
        }
        if (severity==='MINOR') {
          labelSeverity='Mineur';
          if (t6.dataset.nombreBugMineur==='0'){
            idc='-';
          } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t6.dataset.nombreBugMineur,10));
            }
        }
        if (idc !=='100 %' && idc !=='-') {
          alert='texte-rouge';
          } else {
          alert='texte-vert';
        }

        const tabBug=`<tr>
            <td class="${css}"><strong>${labelSeverity}</strong></td>
            <td id="presenation-01" class="text-center">${t.repartition.frontend}</td>
            <td id="metier-01" class="text-center">${t.repartition.backend}</td>
            <td id="autre-01" class="text-center">${t.repartition.autre}</td>
            <td id="indice-confience-01" class="text-center ${alert}">${idc}</td></tr>`;
            $('#mon-bo-tableau1').append(tabBug);
      }

      /* On affiche le tableau pour la sécurité si le parser est OK */
      if (type==='VULNERABILITY' && parser==='OK'){
        if (severity==='BLOCKER') {
          if (t8.dataset.nombreVulnerabiliteBloquant==='0'){
            idc='-';
          } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t8.dataset.nombreVulnerabiliteBloquant,10));
            }
        }
        if (severity==='CRITICAL'){
          if (t9.dataset.nombreVulnerabiliteCritique==='0'){
            idc='-';
          } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t9.dataset.nombreVulnerabiliteCritique,10));
            }
          }
        if (severity==='INFO'){
          if (t10.dataset.nombreVulnerabiliteInfo==='0'){
            idc='-';
          } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t10.dataset.nombreVulnerabiliteInfo,10));
            }
          }
      if (severity==='MAJOR'){
        if (t11.dataset.nombreVulnerabiliteMajeur==='0'){
          idc='-';
        } else {
            idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t11.dataset.nombreVulnerabiliteMajeur,10));
          }
        }
    if (severity==='MINOR') {
      if (t12.dataset.nombreVulnerabiliteMineur==='0'){
        idc='-';
      } else {
          idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t12.dataset.nombreVulnerabiliteMineur,10));
        }
      }
      if (idc !=='100 %' && idc !=='-') {
        alert='texte-rouge';
      } else {
        alert='texte-vert';
      }

      const tabVulnerability=`
      <tr>
        <td class="${css}"><strong>${severity}</strong></td>
        <td id="presenation-01" class="text-center">${t.repartition.frontend}</td>
        <td id="metier-01" class="text-center">${t.repartition.backend}</td>
        <td id="autre-01" class="text-center">${t.repartition.autre}</td>
        <td id="indice-confience-01" class="text-center ${alert}">${idc}</td></tr>`;
        $('#mon-bo-tableau2').append(tabVulnerability);
      }

      /* On affiche le tableau pour la maintenabilité si le parser est OK */
      if (type==='CODE_SMELL' && parser==='OK'){
        if (severity==='BLOCKER'){
          if (t14.dataset.nombreMauvaisePratiqueBloquant==='0'){
              idc='-';
            } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t14.dataset.nombreMauvaisePratiqueBloquant,10));
              }
          }
        if (severity==='CRITICAL') {
          if (t15.dataset.nombreMauvaisePratiqueCritique==='0'){
              idc='-';
            } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t15.dataset.nombreMauvaisePratiqueCritique,10));
            }
          }
        if (severity==='INFO'){
          if (t16.dataset.nombreMauvaisePratiqueInfo==='0'){
            idc='-';
          } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t16.dataset.nombreMauvaisePratiqueInfo,10));
            }
          }
        if (severity==='MAJOR'){
          if (t17.dataset.nombreMauvaisePratiqueMajeur==='0'){
            idc='-';
          } else {
                  idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t17.dataset.nombreMauvaisePratiqueMajeur,10));
              }
          }
      if (severity==='MINOR'){
        if (t18.dataset.nombreMauvaisePratiqueMinor==='0'){
          idc='-';
        } else {
              idc=new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(somme/parseInt(t18.dataset.nombreMauvaisePratiqueMineur,10));
            }
          }
      if (idc !=='100 %' && idc !=='-') {
          alert='texte-rouge';
        } else {
          alert='texte-vert';
        }
      const tabCodeSmell=`<tr>
        <td class="${css}"><strong>${severity}</strong></td>
        <td id="presenation-01" class="text-center">${t.repartition.frontend}</td>
        <td id="metier-01" class="text-center">${t.repartition.backend}</td>
        <td id="autre-01" class="text-center">${t.repartition.autre}</td>
        <td id="indice-confience-01" class="text-center ${alert}">${idc}</td></tr>`;
        $('#mon-bo-tableau3').append(tabCodeSmell);
      }
    resolve();
    });
  });
};

/**
 * [Description for collecte]
 * On lance la collecte et on affiche la répartition
 *
 * @param mixed mavenKey
 * @param mixed type
 * @param mixed severity
 * @param mixed start
 * @param mixed stop
 * @param mixed counter
 * @param mixed timer
 * @param mavenKey
 * @param type
 * @param severity
 * @param start
 * @param stop
 * @param counter
 * @param timer
 * @returns [type]
 *
 * Created at: 19/12/2022, 22:46:29 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const collecte=function(mavenKey, type, severity, start, stop, counter, timer) {

  /** Début de l'animation */
  const startCollecte = () => {
    $('#collecte-animation').addClass('sp-volume');
  };

  /** Fin de l'animation */
  const stopCollecte = () => {
    $('#collecte-animation').removeClass('sp-volume');
  };

  /**
   * Initialisation de la barre de progression et mise à jour dynamique
   *
   * @param progress
   */
  const changeProgress = progress => {
    $('.progress-meter').css('width', `${progress}%`);
    $('.progress-meter-text').text(`${progress}%`);
  };

  /** On test si on est arrivé à la fin du traitement */
  if (mavenKey==='NaN') {
    setTimeout(() => changeProgress(stop), mille);
    return;
  }

  let typeTime;
  if (type==='BUG') {
    typeTime='bug';
  }
  if (type==='VULNERABILITY') {
    typeTime='vulnerabilite';
  }
  if (type==='CODE_SMELL') {
    typeTime='mauvaise-pratique';
  }

  /**
   * On affiche la durée d'execution
   *
   * @param value
   */
  const changeTimer = value => {
    const minute = Math.floor(value/soixante);
    const restSeconds = value%soixante;
    const newTimer=`${minute}.${restSeconds}`;
    const typeTimeLower=typeTime.toLowerCase();
    $(`#js-${typeTimeLower}-time`).html(newTimer);
  };

  /** On récupère le setup par défaut de l'application */
  const setup=timestamp('collecte');

  /** Déclaration des parametres de l'appel du service */
  const data = { mavenKey, type, severity, setup, mode:'null' };
  const options = {
    url: `${serveur()}/api/projet/repartition/collecte`,
    type: 'PUT',dataType: 'json', data: JSON.stringify(data), contentType,
    beforeSend: function () {
      setTimeout(() => startCollecte(), mille);
      setTimeout(() => changeProgress(start), mille); },
    complete: function () {
      setTimeout(() => stopCollecte(), mille);
      setTimeout(() => changeProgress(stop), mille);
      setTimeout(() => changeTimer(timer), mille);
    }
  };

  /** La requête utilise une promise et un callback. */
  return new Promise(resolve => {
    $.ajax(options).then( t=> {
      /** On affiche le nombre d'anomalie */
      $('#nombre-anomalie').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(counter));

      /** On injecte la temps passé */
      const typeTimeLower=typeTime.toLowerCase();
      const t1 = document.getElementById(`js-${typeTimeLower}-time`);
      t1.dataset.timer=t.temps;
    resolve();
    });
  });
};

/**
 * [Description for resultat]
 * On récupère le nombre d'anomalie par type
 *
 * @param mixed mavenKey
 * @param mixed type
 * @param mavenKey
 * @param type
 * @returns [type]
 *
 * Created at: 19/12/2022, 22:50:37 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const resultat=function(mavenKey, type) {

  /** On bind les variables */
  const data = { mavenKey, type };
  const options = {
    url: `${serveur()}/api/projet/repartition/details`, type: 'GET',
    dataType: 'json', data, contentType };

  /** On va chercher les resutats. */
  return $.ajax(options).then(t => {

    if ( type==='BUG') {
      /** On en registre les résultats dans des dataset de la page */
      const t1 = document.getElementById('nombre-bug');
      const t2 = document.getElementById('bug-bloquant');
      const t3 = document.getElementById('bug-critique');
      const t4 = document.getElementById('bug-info');
      const t5 = document.getElementById('bug-majeur');
      const t6 = document.getElementById('bug-mineur');
      t1.dataset.nombreBug=t.total;
      t2.dataset.nombreBugBloquant=t.blocker;
      t3.dataset.nombreBugCritique=t.critical;
      t4.dataset.nombreBugInfo=t.info;
      t5.dataset.nombreBugMajeur=t.major;
      t6.dataset.nombreBugMineur=t.minor;
    }

    if ( type==='VULNERABILITY') {
      /** On en registre les résultats dans des dataset de la page */
      const t7 = document.getElementById('nombre-vulnerabilite');
      const t8 = document.getElementById('vulnerabilite-bloquant');
      const t9 = document.getElementById('vulnerabilite-critique');
      const t10 = document.getElementById('vulnerabilite-info');
      const t11 = document.getElementById('vulnerabilite-majeur');
      const t12 = document.getElementById('vulnerabilite-mineur');
      t7.dataset.nombreVulnerabilite=t.total;
      t8.dataset.nombreVulnerabiliteBloquant=t.blocker;
      t9.dataset.nombreVulnerabiliteCritique=t.critical;
      t10.dataset.nombreVulnerabiliteInfo=t.info;
      t11.dataset.nombreVulnerabiliteMajeur=t.major;
      t12.dataset.nombreVulnerabiliteMineur=t.minor;
    }

    if ( type==='CODE_SMELL') {
      /** On en registre les résultats dans des dataset de la page */
      const t13 = document.getElementById('nombre-mauvaise-pratique');
      const t14 = document.getElementById('mauvaise-pratique-bloquant');
      const t15 = document.getElementById('mauvaise-pratique-critique');
      const t16 = document.getElementById('mauvaise-pratique-info');
      const t17 = document.getElementById('mauvaise-pratique-majeur');
      const t18 = document.getElementById('mauvaise-pratique-mineur');
      t13.dataset.nombreMauvaisePratique=t.total;
      t14.dataset.nombreMauvaisePratiqueBloquant=t.blocker;
      t15.dataset.nombreMauvaisePratiqueCritique=t.critical;
      t16.dataset.nombreMauvaisePratiqueInfo=t.info;
      t17.dataset.nombreMauvaisePratiqueMajeur=t.major;
      t18.dataset.nombreMauvaisePratiqueMineur=t.minor;
    }

    /** On affiche les résultats pour les BUG */
    if ( type==='BUG') {
      // On affiche les résultats
      $('#nombre-bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.total));
      $('#bug-bloquant').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.blocker));
      $('#bug-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.critical));
      $('#bug-majeur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.major));
      $('#bug-mineur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.minor));
      $('#bug-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.info));
      }

    /** On affiche les résultats pour les vulnérabilités */
    if ( type==='VULNERABILITY') {
      $('#nombre-vulnerabilite').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.total));
      $('#vulnerabilite-bloquant').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.blocker));
      $('#vulnerabilite-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.critical));
      $('#vulnerabilite-majeur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.major));
      $('#vulnerabilite-mineur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.minor));
      $('#vulnerabilite-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.info));
    }

    /** On affiche les résultats pour les mauvaises pratiques */
    if ( type==='CODE_SMELL') {
      $('#nombre-mauvaise-pratique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.total));
      $('#mauvaise-pratique-bloquant').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.blocker));
      $('#mauvaise-pratique-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.critical));
      $('#mauvaise-pratique-majeur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.major));
      $('#mauvaise-pratique-mineur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.minor));
      $('#mauvaise-pratique-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.info));
    }
  });
};

/** On lance la collecte pour les BUG */
$('#collecte-bug').on('click', ()=>{
  // on récupère les résultats binder dans la page.
  const t1 = document.getElementById('nombre-bug');
  const t2 = document.getElementById('bug-bloquant');
  const t3 = document.getElementById('bug-critique');
  const t4 = document.getElementById('bug-info');
  const t5 = document.getElementById('bug-majeur');
  const t6 = document.getElementById('bug-mineur');

  const total=t1.dataset.nombreBug;
  const blocker=t2.dataset.nombreBugBloquant;
  const critical=t3.dataset.nombreBugCritique;
  const info=t4.dataset.nombreBugInfo;
  const major=t5.dataset.nombreBugMajeur;
  const minor=t6.dataset.nombreBugMineur;

  /** On initialise le timer pour les BUG */
  const timer = document.getElementById('js-bug-time');
  timer.dataset.timer=0;

  /**
   * On créé une fonction asynchrone avec un callback.
   * Pour chaque type de sévérité on appel la fonction de collecte
   *
   * start = valeur de départ de la barre de progression
   * stop = valeur de fin de la barre de progression
   * counter =  nombre de défaut
   * tempo : durée en seconde
   *
   */
  async function fnAsync() {
    let start=0, stop=0, counter, tempo=0;

  if (parseInt(blocker,10)!==0) {
      stop=Math.trunc((parseInt(blocker,10)/parseInt(total,10))*cent);
      if (stop === 0) {
          stop=stop+1;
        }
      counter=parseInt(blocker,10);
      const timer1 = document.getElementById('js-bug-time');
      tempo =parseInt(timer1.dataset.timer,10);
      await collecte(_mavenkey, 'BUG', 'BLOCKER', start, stop, counter, tempo);
    }

    if (parseInt(critical,10)!==0) {
      start=stop;
      stop=stop+Math.trunc((parseInt(critical,10)/parseInt(total,10))*cent);
      if (stop === 0) {
        stop=stop+1;
      }
      counter=parseInt(blocker,10)+parseInt(critical,10);
      const timer2 = document.getElementById('js-bug-time');
      tempo = parseInt(tempo,10) + parseInt(timer2.dataset.timer,10);
      await collecte(_mavenkey, 'BUG', 'CRITICAL', start, stop, counter, tempo);
    }

    if (parseInt(info,10)!==0) {
      start=stop;
      stop=stop+Math.trunc((parseInt(info,10)/parseInt(total,10))*cent);
      if (stop === 0) {
        stop=stop+1;
      }
      counter=parseInt(blocker,10)+parseInt(critical,10)+parseInt(info,10);
      const timer3 = document.getElementById('js-bug-time');
      tempo = parseInt(tempo,10) + parseInt(timer3.dataset.timer,10);
      await collecte(_mavenkey, 'BUG', 'INFO', start, stop, counter, tempo);
    }

    if (parseInt(major,10)!==0) {
      start=stop;
      stop=stop+Math.trunc((parseInt(major,10)/parseInt(total,10))*cent);
      if (stop === 0) {
        stop=stop+1;
      }
      counter=parseInt(blocker,10)+parseInt(critical,10)+parseInt(info,10)+parseInt(major,10);
      const timer4 = document.getElementById('js-bug-time');
      tempo = parseInt(tempo,10) + parseInt(timer4.dataset.timer,10);
      await collecte(_mavenkey, 'BUG', 'MAJOR', start, stop, counter, tempo);
    }

    if (parseInt(minor,10)!==0) {
      start=stop;
      stop=cent;
      counter=parseInt(blocker,10)+parseInt(critical,10)+parseInt(info,10)+parseInt(major,10)+parseInt(minor,10);
      const timer5 = document.getElementById('js-bug-time');
      tempo= parseInt(tempo,10) + parseInt(timer5.dataset.timer,10);
      await collecte(_mavenkey, 'BUG', 'MINOR', start, stop, counter, tempo);
    }

    $('#etape-1').css('color', '#c45d4e');
  }
  /** On appelle la fonction de récupèration des sévérités pour les BUG */
  fnAsync();
});

/** On lance la collecte ppour les VULNERABILITY */
$('#collecte-vulnerabilite').on('click', ()=>{

  /** on récupère les résultats binder dans la page. */
  const t1 = document.getElementById('nombre-vulnerabilite');
  const t2 = document.getElementById('vulnerabilite-bloquant');
  const t3 = document.getElementById('vulnerabilite-critique');
  const t4 = document.getElementById('vulnerabilite-info');
  const t5 = document.getElementById('vulnerabilite-majeur');
  const t6 = document.getElementById('vulnerabilite-mineur');

  const total=t1.dataset.nombreVulnerabilite;
  const blocker=t2.dataset.nombreVulnerabiliteBloquant;
  const critical=t3.dataset.nombreVulnerabiliteCritique;
  const info=t4.dataset.nombreVulnerabiliteInfo;
  const major=t5.dataset.nombreVulnerabiliteMajeur;
  const minor=t6.dataset.nombreVulnerabiliteMineur;

  /** On initialise le timer pour les vulnerabilités */
  const timer = document.getElementById('js-vulnerabilite-time');
  timer.dataset.timer=0;

  /**
   * On créé une fonction asynchrone avec un callback.
   * Pour chaque type de sévérité on appel la fonction de collecte
   *
   * start = valeur de départ de la barre de progression
   * stop = valeur de fin de la barre de progression
   * counter =  nombre de défaut
   * tempo : durée en seconde
   *
   */
  async function fnAsync() {
    let start=0, stop=0, counter, tempo=0;

    if (parseInt(blocker,10)!==0) {
      stop=Math.trunc((parseInt(blocker,10)/parseInt(total,10))*cent);
      if (stop === 0) {
        stop=stop+1;
      }
      counter=parseInt(blocker,10);
      const timer1 = document.getElementById('js-vulnerabilite-time');
      tempo=parseInt(timer1.dataset.timer,10);
      await collecte(_mavenkey, 'VULNERABILITY', 'BLOCKER', start, stop, counter, tempo);
    }

    if (parseInt(critical,10)!==0){
      start=stop;
      stop=stop+Math.trunc((parseInt(critical,10)/parseInt(total,10))*cent);
      if (stop === 0) {
        stop=stop+1;
      }
      counter=parseInt(blocker,10)+parseInt(critical,10);
      const timer2 = document.getElementById('js-vulnerabilite-time');
      tempo = parseInt(tempo,10) + parseInt(timer2.dataset.timer,10);
      await collecte(_mavenkey, 'VULNERABILITY', 'CRITICAL', start, stop, counter, tempo);
    }

    if (parseInt(info,10)!==0) {
      start=stop;
      stop=stop+Math.trunc((parseInt(info,10)/parseInt(total,10))*cent);
      if (stop === 0) {
        stop=stop+1;
      }
      counter=parseInt(blocker,10)+parseInt(critical,10)+parseInt(info,10);
      const timer3 = document.getElementById('js-vulnerabilite-time');
      tempo = parseInt(tempo,10) + parseInt(timer3.dataset.timer,10);
      await collecte(_mavenkey, 'VULNERABILITY', 'INFO', start, stop, counter, tempo);
    }

    if (parseInt(major,10)!==0) {
      start=stop;
      stop=stop+Math.trunc((parseInt(major,10)/parseInt(total,10))*cent);
      counter=parseInt(blocker,10)+parseInt(critical,10)+parseInt(info,10)+parseInt(major,10);
      const timer4 = document.getElementById('js-vulnerabilite-time');
      tempo = parseInt(tempo,10) + parseInt(timer4.dataset.timer,10);
      await collecte(_mavenkey, 'VULNERABILITY', 'MAJOR', start, stop, counter, tempo);
    }

    if (parseInt(minor,10)!==0) {
      start=stop;
      stop=cent;
      counter=parseInt(blocker,10)+parseInt(critical,10)+parseInt(info,10)+parseInt(major,10)+parseInt(minor,10);
      const timer5 = document.getElementById('js-vulnerabilite-time');
      tempo= parseInt(tempo,10) + parseInt(timer5.dataset.timer,10);
      await collecte(_mavenkey, 'VULNERABILITY', 'MINOR', start, stop, counter, tempo);
    }

    $('#etape-2').css('color', '#c45d4e');
  }

  /** On appelle la fonction de récupèration des sévérités pour les VULNERABILITY */
  fnAsync();
});

/** On lance la collecte pour les CODE_SMELL */
$('#collecte-mauvaise-pratique').on('click', ()=>{

  /** on récupère les résultats binder dans la page. */
  const t1 = document.getElementById('nombre-mauvaise-pratique');
  const t2 = document.getElementById('mauvaise-pratique-bloquant');
  const t3 = document.getElementById('mauvaise-pratique-critique');
  const t4 = document.getElementById('mauvaise-pratique-info');
  const t5 = document.getElementById('mauvaise-pratique-majeur');
  const t6 = document.getElementById('mauvaise-pratique-mineur');

  const total=t1.dataset.nombreMauvaisePratique;
  const blocker=t2.dataset.nombreMauvaisePratiqueBloquant;
  const critical=t3.dataset.nombreMauvaisePratiqueCritique;
  const info=t4.dataset.nombreMauvaisePratiqueInfo;
  const major=t5.dataset.nombreMauvaisePratiqueMajeur;
  const minor=t6.dataset.nombreMauvaisePratiqueMineur;

  /** On initialise le timer pour les vulnerabilités */
  const timer = document.getElementById('js-mauvaise-pratique-time');
  timer.dataset.timer=0;

  /**
   * On créé une fonction asynchrone avec un callback.
   * Pour chaque type de sévérité on appel la fonction de collecte
   *
   * start = valeur de départ de la barre de progression
   * stop = valeur de fin de la barre de progression
   * counter =  nombre de défaut
   * tempo : durée en seconde
   *
   */
  async function fnAsync() {
    let start=0, stop=0, counter, tempo=0;

    if (parseInt(blocker,10)!==0) {
      stop=Math.trunc((parseInt(blocker,10)/parseInt(total,10))*cent);
      if (stop === 0) {
        stop=stop+1;
      }
      counter=parseInt(blocker,10);
      const timer1 = document.getElementById('js-mauvaise-pratique-time');
      tempo=parseInt(timer1.dataset.timer,10);
      await collecte(_mavenkey, 'CODE_SMELL', 'BLOCKER', start, stop, counter, tempo);
    }

    if (parseInt(critical,10)!==0) {
      start=stop;
      stop=stop+Math.trunc((parseInt(critical,10)/parseInt(total,10))*cent);
      if (stop === 0) {
        stop=stop+1;
      }
      counter=parseInt(blocker,10)+parseInt(critical,10);
      const timer2 = document.getElementById('js-mauvaise-pratique-time');
      tempo = parseInt(tempo,10) + parseInt(timer2.dataset.timer,10);
      await collecte(_mavenkey, 'CODE_SMELL', 'CRITICAL', start, stop, counter, tempo);
    }

    if (parseInt(info,10)!==0) {
      start=stop;
      stop=stop+Math.trunc((parseInt(info,10)/parseInt(total,10))*cent);
      if (stop === 0) {
        stop=stop+1;
      }
      counter=parseInt(blocker,10)+parseInt(critical,10)+parseInt(info,10);
      const timer3 = document.getElementById('js-mauvaise-pratique-time');
      tempo = parseInt(tempo,10) + parseInt(timer3.dataset.timer,10);
      await collecte(_mavenkey, 'CODE_SMELL', 'INFO', start, stop, counter, tempo);
    }

    if (parseInt(major,10)!==0) {
      start=stop;
      stop=stop+Math.trunc((parseInt(major,10)/parseInt(total,10))*cent);
      counter=parseInt(blocker,10)+parseInt(critical,10)+parseInt(info,10)+parseInt(major,10);
      const timer4 = document.getElementById('js-mauvaise-pratique-time');
      tempo = parseInt(tempo,10) + parseInt(timer4.dataset.timer,10);
      await collecte(_mavenkey, 'CODE_SMELL', 'MAJOR', start, stop, counter, tempo);
    }

    if (parseInt(minor,10)!==0){
      start=stop;
      stop=cent;
      counter=parseInt(blocker,10)+parseInt(critical,10)+parseInt(info,10)+parseInt(major,10)+parseInt(minor,10);
      const timer5 = document.getElementById('js-mauvaise-pratique-time');
      tempo= parseInt(tempo,10) + parseInt(timer5.dataset.timer,10);
      await collecte(_mavenkey, 'CODE_SMELL', 'MINOR', start, stop, counter, tempo);
    } else {
      await collecte('NaN', 'NaN', 'NaN', 'NaN', cent, 'NaN', 'NaN');
    }

    $('#etape-3').css('color', '#c45d4e');
  }

  /** On appelle la fonction de récupèration des sévérités pour les VULNERABILITY */
  fnAsync();
});

/** On appelle le service de suppression des données du projet. */
$('.bouton-supprime-donnees').on('click', ()=>{
  clear(_mavenkey);
});

$('.bouton-repartition-traitement-donnees').on('click', ()=>{

  /** On lance la fonction asynchonne */
  async function fnAsync() {

    const tabTitre=`<tr>
    <th scope="col"></th>
    <th scope="col" class="text-center"><strong>Application Présentaton</strong></th>
    <th scope="col" class="text-center"><strong>Application Métier</strong></th>
    <th scope="col" class="text-center"><strong>Autres</strong></th>
    <th scope="col" class="text-center"><strong>IdC</strong></th></tr>`;

    /** BLOCKER */
    /** On affiche le tableau */
    $('#tableau-1').removeClass('hide');
    $('#mon-bo-tableau1').html(tabTitre);
    await analyse(_mavenkey, 'BUG', 'BLOCKER', 'texte-rouge');
    await analyse(_mavenkey, 'BUG', 'CRITICAL', 'texte-rouge');
    await analyse(_mavenkey, 'BUG', 'INFO', 'texte-bleu');
    await analyse(_mavenkey, 'BUG', 'MAJOR','texte-orange');
    await analyse(_mavenkey, 'BUG', 'MINOR', 'texte-vert');

    /** VULNERABILITY */
    $('#tableau-2').removeClass('hide');
    $('#mon-bo-tableau2').html(tabTitre);
    await analyse(_mavenkey, 'VULNERABILITY', 'BLOCKER', 'texte-rouge');
    await analyse(_mavenkey, 'VULNERABILITY', 'CRITICAL', 'texte-rouge');
    await analyse(_mavenkey, 'VULNERABILITY', 'INFO', 'texte-bleu');
    await analyse(_mavenkey, 'VULNERABILITY', 'MAJOR', 'texte-orange');
    await analyse(_mavenkey, 'VULNERABILITY', 'MINOR', 'texte-vert');

    /** CODE_SMELL */
    $('#tableau-3').removeClass('hide');
    $('#mon-bo-tableau3').html(tabTitre);
    await analyse(_mavenkey, 'CODE_SMELL', 'BLOCKER', 'texte-rouge');
    await analyse(_mavenkey, 'CODE_SMELL', 'CRITICAL', 'texte-rouge');
    await analyse(_mavenkey, 'CODE_SMELL', 'INFO', 'texte-bleu');
    await analyse(_mavenkey, 'CODE_SMELL', 'MAJOR', 'texte-orange');
    await analyse(_mavenkey, 'CODE_SMELL', 'MINOR', 'texte-vert');
  }

  /** On lance la fonction assynchrone */
  fnAsync();
});

/**
 *
 * Main
 *
 */

/** On va chercher les informations par type et par séverité */
resultat(_mavenkey, 'BUG' );
resultat(_mavenkey, 'VULNERABILITY' );
resultat(_mavenkey, 'CODE_SMELL' );
