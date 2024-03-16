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


/** Intégration de jquery */
import $ from 'jquery';

/** On importe les paramètres serveur */
import {serveur} from './properties.js';

/** On importe les constantes */
import {dateOptions, contentType} from './constante.js';

/**
 * [Description for log]
 * Affiche la log.
 *
 * @param mixed txt
 *
 * @return [type]
 *
 * Created at: 13/12/2022, 12:58:45 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const log=function(txt) {
  const textarea = document.getElementById('log');
  textarea.scrollTop = textarea.scrollHeight;
  textarea.value += `${new Intl.DateTimeFormat('default',
  dateOptions).format(new Date())} ${txt}\n`;
};

/**
* [Description for enregistrement]
* Fonction de remplissage des tableaux.
*
* @param mixed mavenKey
*
* @return [type]
*
* Created at: 13/12/2022, 12:59:18 (Europe/Paris)
* @author     Laurent HADJADJ <laurent_h@me.com>
*/
export const enregistrement=function(mavenKey) {
  /** On récupère les informations sur les versions */
  const nomProjet=$('#nom-projet').text().trim();
  /*
   * On enregistre des données brutes pour l'enregistrement.
   * On n'utilise jquery pour la gestion du data Attribute car ce n'est pas fiable.
   * On utilise à la place l'appel JS standard.
   */
  const t1 = document.getElementById('version-release');
  const t2a = document.getElementById('version-snapshot');
  const t2b = document.getElementById('version-autre');
  const versionRelease=t1.dataset.release;
  const versionSnapshot=t2a.dataset.snapshot;
  const versionAutre=t2b.dataset.autre;

  const version=$('#version').text().trim();
  const t3 = document.getElementById('date-version');
  const dateVersion=t3.dataset.dateVersion;

  /** On récupère les exclusions noSonar */
  const t4 = document.getElementById('suppress-warning');
  const t5 = document.getElementById('no-sonar');
  const suppressWarning=t4.dataset.s1309;
  const noSonar=t5.dataset.nosonar;

  /** On récupère le nombre des todo */
  const t4a = document.getElementById('todo-liste');
  const todo=t4a.dataset.todo;

  /**
   * On récupère les informations du projet :
   * lignes, couverture fonctionnelle, duplication, tests unitaires et
   * le nombre de défaut.
    */
  const t6 = document.getElementById('nombre-ligne');
  const t7 = document.getElementById('nombre-ligne-de-code');
  const t8 = document.getElementById('couverture');
  const t8a = document.getElementById('sqale-debt-ratio');
  const t9 = document.getElementById('duplication');
  const t10 = document.getElementById('tests-unitaires');
  const t11 = document.getElementById('nombre-defaut');
  const nombreLigne=t6.dataset.nombreLigne;
  const nombreLigneDeCode=t7.dataset.nombreLigneDeCode;
  const couverture=t8.dataset.coverage;
  const sqalesebtRatio=t8a.dataset.sqaleDebtRatio;
  const duplication=t9.dataset.duplication;
  const testsUnitaires=t10.dataset.testsUnitaires;
  const nombreDefaut=t11.dataset.nombreDefaut;

  /** On récupère les informations sur la dette technique et les anomalies. */
  /* Dette technique */
  const t12 = document.getElementById('js-dette');
  const dette=t12.dataset.detteMinute;

  /* Nombre d'anomalie par type */
  const t13 = document.getElementById('nombre-bug');
  const t14 = document.getElementById('nombre-vulnerabilite');
  const t15 = document.getElementById('nombre-mauvaise-pratique');
  const nombreBug=t13.dataset.nombreBug;
  const nombreVulnerability=t14.dataset.nombreVulnerability;
  const nombreCodeSmell=t15.dataset.nombreCodeSmell;

  /* répartition des anomalies par module */
  const t16 = document.getElementById('nombre-frontend');
  const t17 = document.getElementById('nombre-backend');
  const t18 = document.getElementById('nombre-autre');
  const frontend=t16.dataset.nombreFrontend;
  const backend=t17.dataset.nombreBackend;
  const autre=t18.dataset.nombreAutre;

  /* Répartition des anomalies par sévérité */
  const t19 = document.getElementById('nombre-anomalie-bloquant');
  const t20 = document.getElementById('nombre-anomalie-critique');
  const t21 = document.getElementById('nombre-anomalie-info');
  const t22 = document.getElementById('nombre-anomalie-majeur');
  const t23 = document.getElementById('nombre-anomalie-mineur');
  const nombreAnomalieBloquant=t19.dataset.nombreAnomalieBloquant;
  const nombreAnomalieCritique=t20.dataset.nombreAnomalieCritique;
  const nombreAnomalieInfo=t21.dataset.nombreAnomalieInfo;
  const nombreAnomalieMajeur=t22.dataset.nombreAnomalieMajeur;
  const nombreAnomalieMineur=t23.dataset.nombreAnomalieMineur;

  /** On récupère les notes sonarqube pour la version courante */
  const noteReliability=$('#note-reliability').text().trim();
  const noteSecurity=$('#note-security').text().trim();
  const noteSqale=$('#note-sqale').text().trim();

  /** On récupère les hotspots. */
  const noteHotspot=$('#note-hotspot').text().trim();

  /** On récupère les hotspost par sévérité */
  const t24 = document.getElementById('hotspot-high');
  const t25 = document.getElementById('hotspot-medium');
  const t26 = document.getElementById('hotspot-low');
  const t27 = document.getElementById('hotspot-total');
  const hotspotHigh=t24.dataset.hotspotHigh;
  const hotspotMedium=t25.dataset.hotspotMedium;
  const hotspotLow=t26.dataset.hotspotLow;
  const hotspotTotal=t27.dataset.hotspotTotal;

  const t28 = document.getElementById('js-bug-blocker');
  const t29 = document.getElementById('js-bug-critical');
  const t30 = document.getElementById('js-bug-major');
  const t31 = document.getElementById('js-bug-minor');
  const t32 = document.getElementById('js-bug-info');
  const t33 = document.getElementById('js-vulnerability-blocker');
  const t34 = document.getElementById('js-vulnerability-critical');
  const t35 = document.getElementById('js-vulnerability-major');
  const t36 = document.getElementById('js-vulnerability-minor');
  const t37 = document.getElementById('js-vulnerability-info');
  const t38 = document.getElementById('js-code-smell-blocker');
  const t39 = document.getElementById('js-code-smell-critical');
  const t40 = document.getElementById('js-code-smell-major');
  const t41 = document.getElementById('js-code-smell-minor');
  const t42 = document.getElementById('js-code-smell-info');

  const bugBlocker=t28.dataset.bugBlocker;
  const bugCritical=t29.dataset.bugCritical;
  const bugMajor=t30.dataset.bugMajor;
  const bugMinor=t31.dataset.bugMinor;
  const bugInfo=t32.dataset.bugInfo;
  const vulnerabilityBlocker=t33.dataset.vulnerabilityBlocker;
  const vulnerabilityCritical=t34.dataset.vulnerabilityCritical;
  const vulnerabilityMajor=t35.dataset.vulnerabilityMajor;
  const vulnerabilityMinor=t36.dataset.vulnerabilityMinor;
  const vulnerabilityInfo=t37.dataset.vulnerabilityInfo;
  const codeSmellBlocker=t38.dataset.vulnerabilityBlocker;
  const codeSmellCritical=t39.dataset.vulnerabilityCritical;
  const codeSmellMajor=t40.dataset.vulnerabilityMajor;
  const codeSmellMinor=t41.dataset.vulnerabilityMinor;
  const codeSmellInfo=t42.dataset.vulnerabilityInfo;

  const data =
  {
    mavenKey, nomProjet,
    versionRelease, versionSnapshot, versionAutre, version,
    dateVersion, suppressWarning, noSonar, todo,
    nombreLigneDeCode, nombreLigne, couverture, duplication,
    sqalesebtRatio, testsUnitaires, nombreDefaut,
    dette,
    nombreBug, nombreVulnerability, nombreCodeSmell,
    frontend,backend, autre,
    nombreAnomalieBloquant, nombreAnomalieCritique,
    nombreAnomalieInfo, nombreAnomalieMajeur,
    nombreAnomalieMineur,
    noteReliability, noteSecurity,
    noteSqale, noteHotspot, hotspotHigh,
    hotspotMedium, hotspotLow, hotspotTotal,
    bugBlocker, bugCritical, bugMajor, bugMinor, bugInfo,
    vulnerabilityBlocker, vulnerabilityCritical,
    vulnerabilityMajor, vulnerabilityMinor, vulnerabilityInfo,
    codeSmellBlocker, codeSmellCritical, codeSmellMajor,
    codeSmellMinor, codeSmellInfo,
    initial:0, mode:'null' };

const options = {
    url: `${serveur()}/api/enregistrement`, type: 'PUT',
    dataType: 'json', data: JSON.stringify(data), contentType };
    $.ajax(options).then(t=> {
      if (t.code === 'OK') {
        const message='Enregistrement des informations effectué.';
        log(` - INFO : ${message}`);
        const callbox=`<div class="callout success text-justify" data-closable="slide-out-right">
                        <p style="color:#187e3d;" class="open-sans" cell">Bravo ! ${message}</p>
                        <button class="close-button" aria-label="Fermer la fenêtre" type="button" data-close>
                        <span aria-hidden="true">&times;</span></button></div>`;
        $('#message').html(callbox);
      } else {
        const message='Cette version existe déjà dans l\'historique.';
        log(` - ERROR (${t.code}) : ${message}`);
        const callbox=`<div class="callout warning text-justify" data-closable="slide-out-right">
                      <p style="color:#00445b;" class="open-sans" cell">Ooups ! ${message}</p>
                      <button class="close-button" aria-label="Fermer la fenêtre" type="button" data-close>
                      <span aria-hidden="true">&times;</span></button></div>`;
        $('#message').html(callbox);
      }
    });
};
