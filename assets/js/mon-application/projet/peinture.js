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

// Intégration de jquery
import $ from 'jquery';

// On importe les paramètres serveur
import {serveur} from '../../common/properties.js';

import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe le fonction de journalisation */
import { log } from '../../common/log.js';

/** On importe les constantes */
import {contentType, dateOptions, http_400, http_401, http_403, http_404, http_406, http_500,
        http_503, http_504, un, deux, trois, quatre, cinq, dix, cent, dixMille} from '../../common/constante.js';


/**
 * [Description for enableButtonAnalyse]
 *
 * @return void
 *
 * Created at: 21/07/2025 12:05:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const enableButtonAnalyse = function(){
  const $buttonAfficheResult = $('.js-affiche-result');

  // ✅ Réactivation propre du bouton
  $buttonAfficheResult.removeClass('clicked-true disabled-bouton');
  $buttonAfficheResult.attr('aria-label', "Afficher les résultats.");
  $buttonAfficheResult.removeAttr('aria-disabled tabindex');
}

/**
 * [Description for disableButtonAffiche]
 *
 * @return void
 *
 * Created at: 21/07/2025 12:06:39 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const disableButtonAffiche = function(){
  const $buttonAfficheResult = $('.js-affiche-result');

  /** Réinitialisation préalable de toutes les classes liées à l'état */
  $buttonAfficheResult.removeClass('clicked-false clicked-true');

  $buttonAfficheResult.addClass('disabled-bouton clicked-true');
  $buttonAfficheResult.attr('aria-disabled', 'true');
  $buttonAfficheResult.attr('aria-label', "Affiche des informations en cours.");
  $buttonAfficheResult.attr('tabindex', '-1');
}

/**
 * [Description for ErrorButtonAffiche]
 *
 * @return [type]
 *
 * Created at: 21/07/2025 12:15:16 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const ErrorButtonAffiche = function(){
  const $buttonAfficheResult = $('.js-affiche-result');

  $buttonAfficheResult.removeClass('clicked-true').addClass('clicked-false');
}

/**
 * [Description for remplissage]
 * Fonction de remplissage des tableaux.
 *
 * @param mixed mavenKey
 *
 * @return [type]
 *
 * Created at: 13/12/2022, 10:08:02 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
export const remplissage = async function(maven_key) {
  const data = { maven_key };

  /** On bloque le bouton */
  disableButtonAffiche();

  /**
   * On récupère les informations sur les versions, et le dernier audit.
   */
  const optionsInfo = {
    url: `${serveur()}/api/peinture/projet/version`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  try {
        const t = await $.ajax(optionsInfo);
        const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
        if (errorCodes.includes(t.code)){
            const hasTrace = !!t.trace;
            const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
            showMessage(t.type, t.message, trace);
            sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations de la version.');
            log(' - ❌ [Peinture] Affichage des informations sur les versions en échec.');
            return;
          }

        /** On affiche les informations du projet */
        const nom = maven_key.split(':');
        $('#nom-projet').html(nom[1]);
        $('#key-analyse').html(t.analyse_key);
        $('#clef-projet').html(maven_key);
        $('#version-release').html(t.release);
        $('#version-snapshot').html(t.snapshot);
        $('#version-autre').html(t.autre);

        const version = document.getElementById('version-autre');
        version.dataset.label = JSON.stringify(t.label);
        version.dataset.dataset = JSON.stringify(t.dataset);
        $('#version').html(t.projet);
        $('#date-version').html(new Intl.DateTimeFormat('default', dateOptions).format(new Date(t.date)));

        /** Historique */
        const t0 = document.getElementById('key-analyse');
        const t1 = document.getElementById('version-release');
        const t2 = document.getElementById('version-snapshot');
        const t3 = document.getElementById('version-autre');
        const t4 = document.getElementById('date-version');
        t0.dataset.analyseKey=(t.analyse_key);
        t1.dataset.release=(t.release);
        t2.dataset.snapshot=(t.snapshot);
        t3.dataset.autre=(t.autre);
        t4.dataset.dateVersion=(t.date);
        log(' - 🎨 [Peinture] Affichage des informations sur les versions.');
      } catch(error) {
            ErrorButtonAffiche();
            const trace = prepareTechnicalDetails(error);
            const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors l'affichage des informations sur les versions.";
              showMessage('alert', message, trace);
              sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Informations.');
              log(' - ❌ [Peinture] Affichage des informations sur les versions en échec.');
              return;
      }

  /***
   * On récupère les exclusions noSonar
   */
  const optionsNoSonar = {
    url: `${serveur()}/api/peinture/projet/nosonar`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

    try {
      const t = await $.ajax(optionsNoSonar);
      const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
      if (errorCodes.includes(t.code)){
          const hasTrace = !!t.trace;
          const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
          showMessage(t.type, t.message, trace);
          sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations noSonar.');
          log(' - ❌ [Peinture] Affichage des informations NoSonar en échec.');
          return;
        }

      $('#suppress-warning').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.s1309));
      $('#no-sonar').html( new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.nosonar));
      const t5 = document.getElementById('suppress-warning');
      const t6 = document.getElementById('no-sonar');
      t5.dataset.s1309=(t.s1309);
      t6.dataset.nosonar=(t.nosonar);
      log(' - 🎨 [Peinture] Affichage des informations NoSOnar.');
    } catch(error) {
          ErrorButtonAffiche();
          const trace = prepareTechnicalDetails(error);
          const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors l'affichage des informations noSonar.";
            showMessage('alert', message, trace);
            sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc NoSonar.');
            log(' - ❌ [Peinture] Affichage des informations NoSonar en échec.');
            return;
    }

  /** On récupère les to.do tags */
  const optionsTodo = {
    url: `${serveur()}/api/peinture/projet/todo`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(optionsTodo);
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations todo.');
        log(' - ❌ [Peinture] Affichage des informations ToDO en échec.');
        return;
      }

    /** On injecte dans la fenêtre modale les résultats */
    $('#todo-liste').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.todo));
    $('#js-java').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.java));
    $('#js-javascript').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.javascript));
    $('#js-typescript').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.typescript));
    $('#js-html').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.html));
    $('#js-xml').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.xml));

    /* On ajoute la liste détaillée des fichiers */
    let l, cutRule, cutComponent;
    /** On efface le tableau */
    $('#tableau-liste-detail').html('');
    t.details.liste.forEach(element => {
      cutRule=element.rule.split(':');
      cutComponent=element.component.split(':');
      l=`<tr><td><strong>${cutRule[0]}</strong></td><td>${cutComponent[2]}</td><td>${element.line}</td></tr>`;
      $('#tableau-liste-detail').append(l);
    });

    const t50 = document.getElementById('todo-liste');
    const t51 = document.getElementById('js-java');
    const t52 = document.getElementById('js-javascript');
    const t53 = document.getElementById('js-typescript');
    const t54 = document.getElementById('js-html');
    const t55 = document.getElementById('js-xml');
    const t56 = document.getElementById('tableau-liste-detail');
    t50.dataset.todo=(t.todo);
    t51.dataset.java=(t.java);
    t52.dataset.javascript=(t.javascript);
    t53.dataset.typescript=(t.typescript);
    t54.dataset.html=(t.html);
    t55.dataset.xml=(t.xml);
    t56.dataset.listeFichier=(t.details);
    log(' - 🎨 [Peinture] Affichage des informations sur les todo.');
  } catch(error) {
      ErrorButtonAffiche();
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors l'affichage des informations sur les todo.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc todo.');
      log(' - ❌ [Peinture] Affichage des informations sur les todo en échec.');
      return;
  }

  /***
 * On récupère les logger
 */
  const optionsLogger = {
    url: `${serveur()}/api/peinture/projet/logger`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(optionsLogger);
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations sur les loggers JAVA.');
        log(' - ❌ [Peinture] Affichage des informations sur les loggers JAVA en échec.');
        return;
      }

    //Historique
    const logger1 = document.getElementById('js-logger-info');
    const logger2 = document.getElementById('js-logger-warn');
    const logger3 = document.getElementById('js-logger-error');
    const logger4 = document.getElementById('js-logger-debug');

      /** Il n'y a pas de logger pour ce projet, i.e ce n'est peut être pas un projet java */
    if (t.total === -1 || t.total === 0) {
      $('#logger-liste').html('N.C');
      logger1.dataset.loggerInfo=(t.logger_info);
      logger2.dataset.loggerWarn=(t.logger_warn);
      logger3.dataset.loggerError=(t.logger_error);
      logger4.dataset.loggerDebug=(t.logger_debug);
      return;
    }

    $('#logger-liste').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.total));
    $('#js-logger-total').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.total));
    $('#js-logger-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.logger_info));
    $('#js-logger-warn').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.logger_warn));
    $('#js-logger-error').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.logger_error));
    $('#js-logger-debug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.logger_debug));

    logger1.dataset.loggerInfo=(t.logger_info);
    logger2.dataset.loggerWarn=(t.logger_warn);
    logger3.dataset.loggerError=(t.logger_error);
    logger4.dataset.loggerDebug=(t.logger_debug);
    log(' - 🎨 [Peinture] Affichage des informations sur les loggers JAVA.');
  } catch(error) {
      ErrorButtonAffiche();
      const trace = prepareTechnicalDetails(error);
      const message = "<strong>[Projet]</strong> Une erreur inattendue s'est produite lors l'affichage des informations sur les loggers JAVA.";
      showMessage('alert', message, trace);
      sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Loggers.');
      log(' - ❌ [Peinture] Affichage des informations sur les loggers JAVA en échec.');
      return;
  }

  /** Débloque le bouton afficher */
  enableButtonAnalyse()
  return;

  /**
   * On récupère les mesures :
   * lignes, coverage fonctionnelle, ration de dette technique, duplication, tests unitaires et le nombre de défaut.
   */
  const optionsMesures = {
    url: `${serveur()}/api/peinture/projet/mesures`, type: 'POST',
    dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    console.log('mesures', t);
    const t = await $.ajax(optionsMesures);
    const errorCodes = [http_400, http_401, http_403, http_406, http_500];
    if (errorCodes.includes(t.code)){
        showMessage(t.type, typeMessage(t.message));
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des mesures.');
        return;
      }

    $('#nombre-ligne').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.lines));
    $('#nombre-ligne-de-code').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.ncloc));

    /** On affiche les langages supportés */
    const jsonObject=t.languages;
    let i=0;
    let total=0;

    /** Calcule la somme des valeurs */
    for (const key in jsonObject) {
      if (jsonObject.hasOwnProperty(key)) {
          total += parseFloat(jsonObject[key]);
      }
    }

    /** On nettoie la liste des langages */
    $('#distribution-langage').html('');

    /** On ajoute les langages */
    for (const key in jsonObject) {
      if (jsonObject.hasOwnProperty(key)) {
          i++;
          const value = parseFloat(jsonObject[key]);
          const percentage = (value / total) * 100;
          const ligne = new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(jsonObject[key])
          const item=`<span tabindex="${i}" class="nasa label-langage tool-tip-langage">
                      ${key}&nbsp;
                      <span class="tool-tip-text-langage">${ligne} (${percentage.toFixed(2)}%)</span>
                      </span>`;
          $('#distribution-langage').append(item);
      }
    }

    $('#coverage').html(new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(parseFloat(t.coverage)/cent));
    $('#ratio-dette-technique').html(new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(parseFloat(t.sqale_debt_ratio)/cent));

    /** On colorise le résultat */
    if (t.sqale_debt_ratio <= 30){
      /** La dette technique est soutenable */
      $('#ratio-dette-technique').addClass('couleur-vert');
    }
    if (t.sqale_debt_ratio > 30 && t.sqale_debt_ratio <= 60){
      /** La dette technique est importante, il faut absolument commencer à la réduire */
      $('#ratio-dette-technique').addClass('couleur-orange');
    }
    if (t.sqale_debt_ratio > 60 && t.sqale_debt_ratio <= 100){
      /** La dette technique n'est plus soutenable, il faut envisager de réécrire l'application ! */
      $('#ratio-dette-technique').addClass('couleur-rouge');
    }
    if (t.sqale_debt_ratio >60 && t.sqale_debt_ratio > 100){
      /** l'application est mauvaise, le ration est > à 100% */
      $('#ratio-dette-technique').addClass('couleur-bordeaux');
    }

    $('#duplicated-lines-density').html(new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(parseFloat(t.duplicated_lines_density,10)/cent));
    $('#tests').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.tests));
    $('#violations').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.issues));

    //Historique
    const t7 = document.getElementById('nombre-ligne');
    const t8 = document.getElementById('nombre-ligne-de-code');
    const t9 = document.getElementById('coverage');
    const t9a = document.getElementById('ratio-dette-technique');
    const t10 = document.getElementById('duplicated-lines-density');
    const t11 = document.getElementById('tests');
    const t12 = document.getElementById('violations');

    t7.dataset.nombreLigne=(t.lines);
    t8.dataset.nombreLigneDeCode=(t.ncloc);
    t9.dataset.coverage=(t.coverage);
    t9a.dataset.sqaleDebtRatio=(t.sqale_debt_ratio);
    t10.dataset.duplicatedLinesDensity=(t.duplicated_lines_density);
    t11.dataset.tests=(t.tests);
    t12.dataset.violations=t.issues;
  } catch(error) {
    showMessage('alert', `<strong>[Peinture]</strong> Une erreur inattendue s'est produite lors la récupération des Mesures.<br>${error.message}`);
  }


  /**
   * On récupère les informations sur la dette technique et les anomalies.
   */
  const optionsAnomalie = {
    url: `${serveur()}/api/peinture/projet/anomalie`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

    try {
      const t = await $.ajax(optionsAnomalie);
      const errorCodes = [http_400, http_401, http_403, http_406, http_500];
      if (errorCodes.includes(t.code)){
          showMessage(t.type, typeMessage(t.message));
          sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des anomalies.');
          return;
        }

      /* Dette technique */
      $('#dette').html(t.dette);
      $('#js-dette-reliability').html(t.detteReliability);
      $('#js-dette-vulnerability').html(t.detteVulnerability);
      $('#js-dette-code-smell').html(t.detteCodeSmell);

      /** Historique */
      const t13 = document.getElementById('js-dette');
      t13.dataset.detteMinute=t.detteMinute;

      /* Nombre d'anomalie */
      $('#nombre-bug').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bug));
      $('#nombre-vulnerability').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerability));
      $('#nombre-mauvaise-pratique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmell));
      /** 10000 = le nombre max des retours possibles */
      if (t.codeSmell===dixMille) {
        $('#nombre-mauvaise-pratique').addClass('couleur-rouge');
      }

      /** Historique */
      const t14 = document.getElementById('nombre-bug');
      const t15 = document.getElementById('nombre-vulnerability');
      const t16 = document.getElementById('nombre-mauvaise-pratique');
      t14.dataset.nombreBug=(t.bug);
      t15.dataset.nombreVulnerability=(t.vulnerability);
      t16.dataset.nombreCodeSmell=(t.codeSmell);

      /* Répartition modules*/
      let i1, i2, i3, i4, p1, p2, p3, p4, e1='', e2='', e3='', e4='';
      const html01='<span style="color:#fff;">0</span>';
      const html02='<span style="color:#fff;">00</span>';

      const totalModule = parseInt(t.frontend + t.backend + t.autre + t.inconnue, 10);

      if (totalModule !== 0) {
        if (t.frontend !== 0) {
          p1 = t.frontend/totalModule;
          if (p1 * cent > dix && p1 * cent < cent) {
            e1 = html01;
          }
          if (p1 * cent < dix) {
            e1 = html02;
          }
          i1 = `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.frontend)}</span> ${e1}
              <span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(t.frontend/totalModule)}</span>`;
        } else {
          i1 = `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>`;
        }
        $('#nombre-frontend').html(i1);

      if (t.backend !== 0) {
        p2 = t.backend / totalModule;
        if ( p2 * cent > dix && p2 * cent < cent) {
          e2 = html01;
        }
        if (p2 * cent < dix) {
          e2 = html02;
        }
        i2= `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.backend)}</span> ${e2}
          <span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(t.backend/totalModule)}</span>`;
      } else {
        i2 = `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>`;
      }
      $('#nombre-backend').html(i2);

      if (t.autre !== 0) {
        p3 = t.autre / totalModule;
        if (p3 *cent > dix && p3 * cent < cent) {
          e3 = html01;
        }
        if (p3 * cent < dix) {
          e3 = html02;
        }
        i3 = `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.autre)}</span> ${e3}
            <span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(t.autre/totalModule)}</span>`;
      } else {
        i3 = `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>`;
      }
      $('#nombre-autre').html(i3);

      if (t.inconnue !== 0) {
        p4 = t.inconnue / totalModule;
        if (p4 *cent > dix && p4 * cent < cent) {
          e4 = html01;
        }
        if (p3 * cent < dix) {
          e4 = html02;
        }
        i4 = `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.inconnue)}</span> ${e4}
            <span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(t.inconnue/totalModule)}</span>`;
      } else {
        i4 = `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>`;
      }
      $('#nombre-inconnue').html(i4);

      /** Historique */
      const t17 = document.getElementById('nombre-frontend');
      const t18 = document.getElementById('nombre-backend');
      const t19 = document.getElementById('nombre-autre');
      const t19a = document.getElementById('nombre-inconnue');
      t17.dataset.nombreFrontend = t.frontend;
      t18.dataset.nombreBackend = t.backend;
      t19.dataset.nombreAutre = t.autre;
      t19a.dataset.nombreInconnue = t.inconnue;
      } else {
          $('#nombre-frontend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          $('#nombre-backend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          $('#nombre-autre').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          $('#nombre-inconnue').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          const t20 = document.getElementById('nombre-frontend');
          const t21 = document.getElementById('nombre-backend');
          const t22 = document.getElementById('nombre-autre');
          const t22a = document.getElementById('nombre-inconnue');
          t20.dataset.nombreFrontend = 0;
          t21.dataset.nombreBackend = 0;
          t22.dataset.nombreAutre = 0;
          t22a.dataset.nombreInconnue = 0;
          }

      /* Répartition des anomalies par sévérité */
      $('#nombre-anomalie-bloquant').html(t.blocker);
      $('#nombre-anomalie-critique').html(t.critical);
      $('#nombre-anomalie-info').html(t.info);
      $('#nombre-anomalie-majeur').html(t.major);
      $('#nombre-anomalie-mineur').html(t.minor);

      const t23 = document.getElementById('nombre-anomalie-bloquant');
      const t24 = document.getElementById('nombre-anomalie-critique');
      const t25 = document.getElementById('nombre-anomalie-info');
      const t26 = document.getElementById('nombre-anomalie-majeur');
      const t27 = document.getElementById('nombre-anomalie-mineur');
      t23.dataset.nombreAnomalieBloquant=t.blocker;
      t24.dataset.nombreAnomalieCritique=t.critical;
      t25.dataset.nombreAnomalieInfo=t.info;
      t26.dataset.nombreAnomalieMajeur=t.major;
      t27.dataset.nombreAnomalieMineur=t.minor;

      /** On récupère les notes SonarQube pour la version courante */
      let couleur1, couleur2, couleur3 = '';
      const tNotes = ['', 'A', 'B', 'C', 'D', 'E'];

      const noteVert1 = 'note-vert1';
      const noteVert2 = 'note-vert2';
      const noteJaune = 'note-jaune';
      const noteOrange = 'note-orange';
      const noteRouge = 'note-rouge';

      if (t.noteReliability === un ) {
        couleur1 = noteVert1;
      }
      if (t.noteSecurity === un) {
        couleur2 = noteVert1;
      }
      if (t.noteSqale === un) {
        couleur3 = noteVert1;
      }

      if (t.noteReliability === deux) {
        couleur1 = noteVert2;
      }
      if (t.noteSecurity === deux) {
        couleur2 = noteVert2;
      }
      if (t.noteSqale === deux) {
        couleur3 = noteVert2;
      }

      if (t.noteReliability === trois) {
        couleur1 = noteJaune;
      }
      if (t.noteSecurity === trois) {
        couleur2 = noteJaune;
      }
      if (t.noteSqale === trois) {
        couleur3 = noteJaune;
      }

      if (t.noteReliability === quatre) {
        couleur1 = noteOrange;
      }
      if (t.noteSecurity === quatre) {
        couleur2 = noteOrange;
      }
      if (t.noteSqale === quatre) {
        couleur3 = noteOrange;
      }

      if (t.noteReliability === cinq) {
        couleur1 = noteRouge;
      }
      if (t.noteSecurity === cinq) {
        couleur2 = noteRouge;
      }
      if (t.noteSqale === cinq) {
        couleur3 = noteRouge;
      }

      const noteReliability = tNotes[parseInt(t.noteReliability,10)];
      const noteSecurity = tNotes[parseInt(t.noteSecurity,10)];
      const noteSqale = tNotes[parseInt(t.noteSqale,10)];

      $('#note-reliability').html(`<span class="${couleur1}">${noteReliability}</span>`);
      $('#note-security').html(`<span class="${couleur2}">${noteSecurity}</span>`);
      $('#note-sqale').html(`<span class="${couleur3}">${noteSqale}</span>`);
    } catch(error) {
      showMessage('alert', `<strong>[Peinture]</strong> Une erreur inattendue s'est produite lors la récupération des Anomalies.<br>${error.message}`);
    }


  /**
   * On récupère les hotspot.
   */
  const optionsHotspots = {
    url: `${serveur()}/api/peinture/projet/hotspots`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(optionsHotspots);
    const errorCodes = [http_400, http_401, http_403, http_406, http_500];
    if (errorCodes.includes(t.code)){
        showMessage(t.type, typeMessage(t.message));
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - Erreur - récupération des hotspots.');
        return;
      }

    let couleur='';

    if (t.note === 'E') {
        couleur = 'note-rouge';
    }
    if (t.note === 'D') {
        couleur = 'note-orange';
    }
    if (t.note === 'C') {
        couleur = 'note-jaune';
    }
    if (t.note === 'B') {
        couleur = 'note-vert2';
      }
    if (t.note === 'A') {
        couleur = 'note-vert1';
    }

    $('#note-hotspot').html(`<span class="${couleur}">${t.note}</span>`);
  } catch(error) {
    showMessage('alert', `<strong>[Peinture]</strong> Une erreur inattendue s'est produite lors la récupération des Hotspots.<br>${error.message}`);
  }

  /**
   * On récupère la sévérité par type.
   */
  const optionsAnomaliesDetails = {
    url: `${serveur()}/api/peinture/projet/anomalie/details`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
    const t = await $.ajax(optionsAnomaliesDetails);
    const errorCodes = [http_400, http_401, http_403, http_406, http_500];
    if (errorCodes.includes(t.code)){
        showMessage(t.type, typeMessage(t.message));
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération du détail des anomalies.');
        return;
      }

    $('#js-bug-blocker').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugBlocker));
    $('#js-bug-critical').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugCritical));
    $('#js-bug-major').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugMajor));
    $('#js-bug-minor').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugMinor));
    $('#js-bug-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.bugInfo));

    const t28 = document.getElementById('js-bug-blocker');
    const t29 = document.getElementById('js-bug-critical');
    const t30 = document.getElementById('js-bug-major');
    const t31 = document.getElementById('js-bug-minor');
    const t32 = document.getElementById('js-bug-info');
    t28.dataset.bugBlocker = t.bugBlocker;
    t29.dataset.bugCritical = t.bugCritical;
    t30.dataset.bugMajor = t.bugMajor;
    t31.dataset.bugMinor = t.bugMinor;
    t32.dataset.bugInfo = t.bugInfo;

    $('#js-vulnerability-blocker').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityBlocker));
    $('#js-vulnerability-critical').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityCritical));
    $('#js-vulnerability-major').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityMajor));
    $('#js-vulnerability-minor').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityMinor));
    $('#js-vulnerability-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.vulnerabilityInfo));

    const t33 = document.getElementById('js-vulnerability-blocker');
    const t34 = document.getElementById('js-vulnerability-critical');
    const t35 = document.getElementById('js-vulnerability-major');
    const t36 = document.getElementById('js-vulnerability-minor');
    const t37 = document.getElementById('js-vulnerability-info');
    t33.dataset.vulnerabilityBlocker = t.vulnerabilityBlocker;
    t34.dataset.vulnerabilityCritical = t.vulnerabilityCritical;
    t35.dataset.vulnerabilityMajor = t.vulnerabilityMajor;
    t36.dataset.vulnerabilityMinor = t.vulnerabilityMinor;
    t37.dataset.vulnerabilityInfo = t.vulnerabilityInfo;

    $('#js-code-smell-blocker').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellBlocker));
    $('#js-code-smell-critical').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellCritical));
    $('#js-code-smell-major').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellMajor));
    $('#js-code-smell-minor').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellMinor));
    $('#js-code-smell-info').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.codeSmellInfo));

    const t38 = document.getElementById('js-code-smell-blocker');
    const t39 = document.getElementById('js-code-smell-critical');
    const t40 = document.getElementById('js-code-smell-major');
    const t41 = document.getElementById('js-code-smell-minor');
    const t42 = document.getElementById('js-code-smell-info');
    t38.dataset.vulnerabilityBlocker = t.codeSmellBlocker;
    t39.dataset.vulnerabilityCritical = t.codeSmellCritical;
    t40.dataset.vulnerabilityMajor = t.codeSmellMajor;
    t41.dataset.vulnerabilityMinor = t.codeSmellMinor;
    t42.dataset.vulnerabilityInfo = t.codeSmellInfo;
  } catch(error) {
    showMessage('alert', `<strong>[Peinture]</strong> Une erreur inattendue s'est produite lors la récupération du détails des Anomalies.<br>${error.message}`);
  }
};

/**
 * [Description for afficheHotspotDetails]
 * On récupère la répartition des hotspot par sévérité pour le type to_review et reviewed
 * http://{url}/api/peinture/projet/hotspot/details{meven_key}
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:25:28 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
export const afficheHotspotDetails = async function (mavenKey){
  /* On récupère la répartition des hotspot. */
  const data = { maven_key: mavenKey };
  const options = {
    url: `${serveur()}/api/peinture/projet/hotspots/details`, type: 'POST',
          dataType: 'json', data: JSON.stringify(data), contentType };

  try {
        const t = await $.ajax(options);
        // 📌 Vérification des erreurs
        const errorCodes = [http_400, http_401, http_403, http_406, http_500];
        if (errorCodes.includes(t.code)){
            showMessage(t.type, typeMessage(t.message));
            sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération du détail des hotspots.');
            return;
          }

        /* On efface les données.*/
        $('#tableau-liste-hotspot').html('');
        const str =`
          <tr colspan="3" id="titre-hotspot-to-review" class="open-sans text-center">Risques de vulnérabilité à vérifier.</tr>
          <tr id="to-review">
            <td id="hotspot-to-review-high" class="text-center stat" data-hotspot-to-review-high="${t.to_review_high}">
              ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.to_review_high)}</td>
            <td id="hotspot-to-review-medium" class="text-center stat" data-hotspot-to-review-medium="${t.to_review_medium}">
              ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.to_review_medium)}</td>
            <td id="hotspot-to-review-low" class="text-center stat" data-hotspot-to-review-low="${t.to_review_low}">
              ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.to_review_low)}</td>
          </tr>
          <tr colspan="3" id="titre-hotspot-to-review" class="open-sans text-center">Risques de vulnérabilité déjà vérifiés.</tr>
          <tr id="reviewed">
            <td id="hotspot-to-review-high" class="text-center stat" data-hotspot-to-review-high="${t.reviewed_high}">
              ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.reviewed_high)}</td>
            <td id="hotspot-to-review-medium" class="text-center stat" data-hotspot-to-review-medium="${t.reviewed_medium}">
              ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.reviewed_medium)}</td>
            <td id="hotspot-to-review-low" class="text-center stat" data-hotspot-to-review-low="${t.to_review_low}">
              ${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.reviewed_low)}</td>
          </tr>
          `;

        $('#tableau-liste-hotspot').append(str);
        $('#hotspot-total').html(`<span class="stat">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.to_review_total)}</span>`);
        if (t.to_review_total > 0) {
          $('#s').html('s');
        }

        const t1 = document.getElementById('hotspot-total');
        t1.dataset.nombreHotspot=(t.to_review_high_total);
      } catch(error) {
        showMessage('alert', `<strong>[Peinture]</strong> Une erreur inattendue s'est produite lors la récupération du détails des Hotspots.<br>${error.message}`);
      }
};
