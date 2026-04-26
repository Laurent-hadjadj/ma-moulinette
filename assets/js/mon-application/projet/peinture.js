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
import {content_type, dateOptions, http_400, http_401, http_403, http_404, http_406, http_500,
http_503, http_504, un, deux, trois, quatre, cinq, dix, cent, dixMille} from '../../common/constante.js';

/** Helpers d'affichage null-safe (null/undefined/NaN -> "-") */
import { displayNumber, displayPercent, displayValue } from '../../common/displayHelper.js';

let token;
import("../../common/secrets.local.js").then((module) => {
  token = module.token;
}).catch((error) => {
      const trace = prepareTechnicalDetails(error);
      const message = "Le module n'a pas été chargé correctement (Erreur 500).";
      showMessage('critical', message, trace);
      sessionStorage.setItem('ma_moulinette_error', 'Error loading the module');
    return;
  });

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
 * @return void
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
 * [Description for extraireNomProjet]
 *
 * @param mixed cle
 *
 * @return string
 *
 * Created at: 03/08/2025 18:59:20 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const extraireNomProjet = function(maven_key) {
  if (maven_key.includes(':')) {
    const parts = maven_key.split(':');
    return parts[1] || parts[0]; // fallback si rien après :
  }

  if (maven_key.includes('.')) {
    const parts = maven_key.split('.');
    return parts[parts.length - 1];
  }

  return maven_key;
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
    url: `${serveur()}/api/secure/peinture/projet/version`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
        const t = await $.ajax(optionsInfo);
        const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
        if (errorCodes.includes(t.code)){
            const hasTrace = !!t.trace;
            const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
            showMessage(t.type, t.message, trace);
            sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations de la version.');
            if (t.code === http_404){
              log(` - 💡 [Peinture] Le projet n'existe pas. Lance une collecte avant.`);
              enableButtonAnalyse();
            } else {
              log(' - ❌ [Peinture] Affichage des informations sur les versions en échec.');
            }
            return;
          }

        /** On affiche les informations du projet */
        const nom = extraireNomProjet(maven_key);

        $('#nom-projet').html(nom);
        $('#key-analyse').html(t.analyse_key);
        $('#clef-projet').html(maven_key);
        $('#version-release').html(t.release);
        $('#version-snapshot').html(t.snapshot);
        $('#version-autre').html(t.autre);

        const version = document.getElementById('version-autre');
        version.dataset.label = JSON.stringify(t.label);
        version.dataset.dataset = JSON.stringify(t.dataset);
        $('#version').html(t.version);
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
            const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur les versions (Erreur 500).";
              showMessage('critical', message, trace);
              sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Informations.');
              log(' - 🔴 [Peinture] Affichage des informations sur les versions en échec.');
              return;
      }

    /***
     * On récupère les exclusions noSonar
     */
    const optionsNoSonar = {
      url: `${serveur()}/api/secure/peinture/projet/nosonar`,
      type: 'POST',
      dataType: 'json',
      data: JSON.stringify(data),
      contentType: content_type,
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app'
      },
    };

    try {
      const t = await $.ajax(optionsNoSonar);
      const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
      if (errorCodes.includes(t.code)){
          const hasTrace = !!t.trace;
          const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
          showMessage(t.type, t.message, trace);
          sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations noSonar.');
          if (t.code === http_404){
              log(` - 💡 [Peinture] Le projet n'existe pas. Lance une collecte avant.`);
              enableButtonAnalyse();
          } else {
              log(' - ❌ [Peinture] Affichage des informations NoSonar en échec.');
          }
          return;
        }

      $('#suppress-warning').html(displayNumber(t.suppress_warning));
      $('#no-pmd').html(displayNumber(t.no_pmd));
      $('#check-style').html(displayNumber(t.check_style));
      $('#no-sonar').html(displayNumber(t.java_no_sonar));
      $('#no-sonar-python').html(displayNumber(t.python_no_sonar));
      $('#no-sonar-php').html(displayNumber(t.php_no_sonar));
      const t5 = document.getElementById('suppress-warning');
      const t6 = document.getElementById('no-sonar');
      t5.dataset.s1309=(t.suppress_warning);
      t6.dataset.nosonar=(t.java_no_sonar);
      log(' - 🎨 [Peinture] Affichage des informations NoSonar.');
    } catch(error) {
          ErrorButtonAffiche();
          const trace = prepareTechnicalDetails(error);
          const message = "Une erreur inattendue s'est produite lors l'affichage des informations noSonar (Erreur 500).";
            showMessage('critical', message, trace);
            sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc NoSonar.');
            log(' - 🔴 [Peinture] Affichage des informations NoSonar en échec.');
            return;
    }

  /** On récupère les to.do tags */
  const optionsTodo = {
    url: `${serveur()}/api/secure/peinture/projet/todo`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
    const t = await $.ajax(optionsTodo);
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations todo.');
        if (t.code === http_404){
            log(` - 💡 [Peinture] Le projet n'existe pas. Lance une collecte avant.`);
            enableButtonAnalyse();
          } else {
            log(' - ❌ [Peinture] Affichage des informations ToDO en échec.');
          }
        return;
      }

    /** On injecte dans la fenêtre modale les résultats */
    $('#todo-liste').html(displayNumber(t.todo));
    $('#js-java').html(displayNumber(t.java));
    $('#js-javascript').html(displayNumber(t.javascript));
    $('#js-typescript').html(displayNumber(t.typescript));
    $('#js-html').html(displayNumber(t.html));
    $('#js-xml').html(displayNumber(t.xml));

    /* On ajoute la liste détaillée des fichiers */
    let l, cutRule, cutComponent;
    /** On efface le tableau */
    $('#tableau-liste-detail').html('');
    /** On affiche que les 10 premiers todo, fo pas déconner !!! */
    let limit = 0;
    let maxOccurrences = 10;
    let maxLength = 80;

    for (let i = 0; i < t.details.liste.length && limit < maxOccurrences; i++) {
      let element = t.details.liste[i];
      let cutRule = element.rule.split(':');
      let cutComponentArray = element.component.split(':');

       /** On raccourci la ligne à l’élément qui nous intéresse. */
      cutComponent = (cutComponentArray.length == 1) ? cutComponentArray[0] : (cutComponentArray[2] || cutComponentArray[1]);

      // Tronquer le début pour conserver les 30 derniers caractères
      let truncatedLine = cutComponent.length > maxLength ? '...' + cutComponent.substring(cutComponent.length - maxLength) : cutComponent;

      let l = `<tr><td><strong>${cutRule[0]}</strong></td><td>${truncatedLine}</td><td class="text-center">${element.line}</td></tr>`;
      $('#tableau-liste-detail').append(l);
      limit++;
    }

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
      const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur les todo (Erreur 500).";
      showMessage('critical', message, trace);
      sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc todo.');
      log(' - 🔴 [Peinture] Affichage des informations sur les todo en échec.');
      return;
  }

  /***
 * On récupère les logger
 */
  const optionsLogger = {
    url: `${serveur()}/api/secure/peinture/projet/logger`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
    const t = await $.ajax(optionsLogger);
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations sur les loggers JAVA.');
        if (t.code === http_404){
            log(` - 💡 [Peinture] Le projet n'existe pas. Lance une collecte avant.`);
            enableButtonAnalyse();
        } else {
          log(' - ❌ [Peinture] Affichage des informations sur les loggers JAVA en échec.');
        }
        return;
      }

    //Historique
    const logger1 = document.getElementById('js-logger-info');
    const logger2 = document.getElementById('js-logger-warn');
    const logger3 = document.getElementById('js-logger-error');
    const logger4 = document.getElementById('js-logger-debug');

      /** Il n'y a pas de logger pour ce projet, i.e ce n'est peut être pas un projet java */
    logger1.dataset.loggerInfo = (t.logger_info == -1) ? '0' : t.logger_info;
    logger2.dataset.loggerWarn = (t.logger_warn == -1) ? '0' : t.logger_warn;
    logger3.dataset.loggerError = (t.logger_error == -1) ? '0' : t.logger_error;
    logger4.dataset.loggerDebug = (t.logger_debug == -1) ? '0' : t.logger_debug;

    $('#logger-liste').html(displayNumber(t.total));
    $('#js-logger-total').html(displayNumber(t.total));
    $('#js-logger-info').html(displayNumber(t.logger_info));
    $('#js-logger-warn').html(displayNumber(t.logger_warn));
    $('#js-logger-error').html(displayNumber(t.logger_error));
    $('#js-logger-debug').html(displayNumber(t.logger_debug));

    log(' - 🎨 [Peinture] Affichage des informations sur les loggers JAVA.');
  } catch(error) {
      ErrorButtonAffiche();
      const trace = prepareTechnicalDetails(error);
      const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur les loggers JAVA (Erreur 500).";
      showMessage('critical', message, trace);
      sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Loggers.');
      log(' - 🔴 [Peinture] Affichage des informations sur les loggers JAVA en échec.');
      return;
  }

  /**
   * On récupère les mesures :
   * lignes, coverage fonctionnelle, ration de dette technique, duplication, tests unitaires et le nombre de défaut.
   */
  const optionsMesures = {
    url: `${serveur()}/api/secure/peinture/projet/mesures`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
    const t = await $.ajax(optionsMesures);
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations sur les mesures.');
        if (t.code === http_404){
            log(` - 💡 [Peinture] Le projet n'existe pas. Lance une collecte avant.`);
            enableButtonAnalyse();
        } else {
            log(' - ❌ [Peinture] Affichage des informations sur les mesures en échec.');
        }
        return;
      }

        /** On affiche les langages supportés */
    const jsonObject=t.ncloc_language_distribution;
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

    $('#nombre-ligne').html(displayNumber(t.lines));
    $('#nombre-ligne-de-code').html(displayNumber(t.ncloc));

    $('#nombre-fichier').html(displayNumber(t.files));
    $('#nombre-classe').html(displayNumber(t.classes));
    $('#nombre-fonction').html(displayNumber(t.functions));
    $('#nombre-statement').html(displayNumber(t.statements));

    $('#complexity-ratio').html(displayNumber(t.complexity_ratio));
    $('#cognitive-complexity-ratio').html(displayNumber(t.cognitive_complexity_ratio));

    $('#coverage').html(displayPercent(t.coverage));
    $('#ratio-dette-technique').html(displayPercent(t.sqale_debt_ratio));

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

    $('#duplicated-lines-density').html(displayPercent(t.duplicated_lines_density));
    $('#tests').html(displayNumber(t.tests));
    $('#violations').html(displayNumber(t.violations));

    /**
     * Notes SonarQube pour la version courante.
     * Les ratings (reliability/security/sqale) viennent de la réponse mesures
     * sous forme de lettres 'A'..'E' (déjà converties par BuildMapHistoryService).
     * Si null/absent : affichage "-" sans classe couleur.
     */
    const colorByRating = {
      'A': 'note-vert1',
      'B': 'note-vert2',
      'C': 'note-jaune',
      'D': 'note-orange',
      'E': 'note-rouge'
    };
    const renderRating = (selector, rating) => {
      const cssClass = colorByRating[rating] ?? '';
      $(selector).html(`<span class="${cssClass}">${displayValue(rating)}</span>`);
    };
    renderRating('#note-reliability', t.reliability_rating);
    renderRating('#note-security', t.security_rating);
    renderRating('#note-sqale', t.sqale_rating);
    renderRating('#note-menace-potentielle', t.security_review_rating);
    renderRating('#note-complexity', t.complexity_rating);
    renderRating('#note-cognitive-complexity', t.cognitive_complexity_rating);
    renderRating('#note-coverage', t.coverage_rating);
    renderRating('#note-duplication', t.duplicated_lines_rating);
    renderRating('#note-comment-lines', t.comment_lines_rating);

    /** Quality Gate : OK / WARN / ERROR */
    const colorByQualityGate = {
      'OK': 'note-vert1',
      'WARN': 'note-jaune',
      'ERROR': 'note-rouge'
    };
    const qgClass = colorByQualityGate[t.alert_status] ?? '';
    $('#alert-status').html(`<span class="${qgClass}">${displayValue(t.alert_status)}</span>`);

    /** Documentation */
    $('#comment-lines').html(displayNumber(t.comment_lines));
    $('#comment-lines-density').html(displayPercent(t.comment_lines_density));

    /** Détail Tests */
    $('#test-errors').html(displayNumber(t.test_errors));
    $('#test-failures').html(displayNumber(t.test_failures));
    $('#skipped-tests').html(displayNumber(t.skipped_tests));
    $('#test-success-density').html(displayPercent(t.test_success_density));

    /** Statut anomalies (workflow de tri) */
    $('#accepted-issues').html(displayNumber(t.accepted_issues));
    $('#false-positive-issues').html(displayNumber(t.false_positive_issues));

    //Historique
    const t7 = document.getElementById('nombre-ligne');
    const t8 = document.getElementById('nombre-ligne-de-code');
    const t8a = document.getElementById('nombre-fichier');
    const t8b = document.getElementById('nombre-classe');
    const t8c = document.getElementById('nombre-fonction');

    const t9 = document.getElementById('coverage');
    const t9a = document.getElementById('ratio-dette-technique');
    const t10 = document.getElementById('duplicated-lines-density');
    const t11 = document.getElementById('tests');
    const t12 = document.getElementById('violations');

    t7.dataset.nombreLigne = t.lines;
    t8.dataset.nombreLigneDeCode = t.ncloc;
    t8a.dataset.nombreFichier= t.files;
    t8b.dataset.nombreClasse = t.classes;
    t8c.dataset.nombreFonction = t.functions;
    t9.dataset.coverage = t.coverage;
    t9a.dataset.sqaleDebtRatio = t.sqale_debt_ratio;
    t10.dataset.duplicatedLinesDensity = t.duplicated_lines_density;
    t11.dataset.tests = t.tests;
    t12.dataset.violations = t.violations;
    log(' - 🎨 [Peinture] Affichage des informations sur les mesures.');
  } catch(error) {
      ErrorButtonAffiche();
      const trace = prepareTechnicalDetails(error);
      const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur les mesures (Erreur 500).";
      showMessage('critical', message, trace);
      sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Mesures.');
      log(' - 🔴 [Peinture] Affichage des informations sur les Mesures en échec.');
      return;
  }

  /**
   * On récupère les informations sur la dette technique et les anomalies.
   */
  const optionsAnomalie = {
    url: `${serveur()}/api/secure/peinture/projet/anomalie`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

    try {
      const t = await $.ajax(optionsAnomalie);
      const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
      if (errorCodes.includes(t.code)){
          const hasTrace = !!t.trace;
          const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
          showMessage(t.type, t.message, trace);
          sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations sur les anomalies.');
          if (t.code === http_404){
              log(` - 💡 [Peinture] Le projet n'existe pas. Lance une collecte avant.`);
              enableButtonAnalyse();
          } else {
              log(' - ❌ [Peinture] Affichage des informations sur les anomalies en échec.');
          }
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
      $('#nombre-bug').html(displayNumber(t.bug));
      $('#nombre-vulnerability').html(displayNumber(t.vulnerability));
      $('#nombre-mauvaise-pratique').html(displayNumber(t.codeSmell));
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

      const totalModule = parseInt(t.frontend + t.backend + t.autre + t.inconnu, 10);

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

      if (t.inconnu !== 0) {
        p4 = t.inconnu / totalModule;
        if (p4 *cent > dix && p4 * cent < cent) {
          e4 = html01;
        }
        if (p4 * cent < dix) {
          e4 = html02;
        }
        i4 = `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(t.inconnu)}</span> ${e4}
            <span>${new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(t.inconnu/totalModule)}</span>`;
      } else {
        i4 = `<span>${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0)}</span>`;
      }
      $('#nombre-inconnu').html(i4);

      /** Historique */
      const t17 = document.getElementById('nombre-frontend');
      const t18 = document.getElementById('nombre-backend');
      const t19 = document.getElementById('nombre-autre');
      const t19a = document.getElementById('nombre-inconnu');
      t17.dataset.nombreFrontend = t.frontend;
      t18.dataset.nombreBackend = t.backend;
      t19.dataset.nombreAutre = t.autre;
      t19a.dataset.nombreInconnu = t.inconnu;
      } else {
          $('#nombre-frontend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          $('#nombre-backend').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          $('#nombre-autre').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          $('#nombre-inconnu').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(0));
          const t20 = document.getElementById('nombre-frontend');
          const t21 = document.getElementById('nombre-backend');
          const t22 = document.getElementById('nombre-autre');
          const t22a = document.getElementById('nombre-inconnu');
          t20.dataset.nombreFrontend = 0;
          t21.dataset.nombreBackend = 0;
          t22.dataset.nombreAutre = 0;
          t22a.dataset.nombreInconnu = 0;
          }

      /* Répartition des anomalies par sévérité */
      $('#nombre-anomalie-bloquant').html(t.blocker);
      $('#nombre-anomalie-critique').html(t.critical);
      $('#nombre-anomalie-info').html(t.info);
      $('#nombre-anomalie-majeur').html(t.major);
      $('#nombre-anomalie-mineur').html(t.minor);
      const totalCodeSmellReal = parseInt(t.blocker + t.critical + t.major + t.minor, 10);
      $('#nombre-mauvaise-pratique-real').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(totalCodeSmellReal));

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

      log(' - 🎨 [Peinture] Affichage des informations sur les anomalies.');
    } catch(error) {
          ErrorButtonAffiche();
          const trace = prepareTechnicalDetails(error);
          const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur les anomalies (Erreur 500).";
          showMessage('critical', message, trace);
          sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Anomalie.');
          log(' - 🔴 [Peinture] Affichage des informations sur les Anomalies en échec.');
          return;
    }


    /**
     * On récupère les hotspot.
     */
    const optionsHotspots = {
      url: `${serveur()}/api/secure/peinture/projet/hotspots`,
      type: 'POST',
      dataType: 'json',
      data: JSON.stringify(data),
      contentType: content_type,
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app'
      },
    };

    try {
      const t = await $.ajax(optionsHotspots);
      const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
      if (errorCodes.includes(t.code)){
          const hasTrace = !!t.trace;
          const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
          showMessage(t.type, t.message, trace);
          sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations sur les menaces potentielles.');
          if (t.code === http_404){
              log(` - 💡 [Peinture] Le projet n'existe pas. Lance une collecte avant.`);
              enableButtonAnalyse();
          } else {
            log(' - ❌ [Peinture] Affichage des informations sur les menaces potentielles en échec.');
          }
          return;
      }

      log(' - 🎨 [Peinture] Affichage des informations sur les menaces potentielles.');
    } catch(error) {
          ErrorButtonAffiche();
          const trace = prepareTechnicalDetails(error);
          const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur les menaces potentielles (Erreur 500).";
          showMessage('critical', message, trace);
          sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Anomalie.');
          log(' - 🔴 [Peinture] Affichage des informations sur les menaces potentielles en échec.');
          return;
    }

  /**
   * On récupère la sévérité par type.
   */
  const optionsAnomaliesDetails = {
    url: `${serveur()}/api/secure/peinture/projet/anomalie/details`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
    const t = await $.ajax(optionsAnomaliesDetails);
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations sur le détail des anomalies.');
        if (t.code === http_404){
              log(` - 💡 [Peinture] Le projet n'existe pas. Lance une collecte avant.`);
              enableButtonAnalyse();
          } else {
            log(' - ❌ [Peinture] Affichage des informations sur le détail des anomalies en échec.');
          }
        return;
      }


    $('#js-bug-blocker').html(displayNumber(t.bugBlocker));
    $('#js-bug-critical').html(displayNumber(t.bugCritical));
    $('#js-bug-major').html(displayNumber(t.bugMajor));
    $('#js-bug-minor').html(displayNumber(t.bugMinor));
    $('#js-bug-info').html(displayNumber(t.bugInfo));

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

    $('#js-vulnerability-blocker').html(displayNumber(t.vulnerabilityBlocker));
    $('#js-vulnerability-critical').html(displayNumber(t.vulnerabilityCritical));
    $('#js-vulnerability-major').html(displayNumber(t.vulnerabilityMajor));
    $('#js-vulnerability-minor').html(displayNumber(t.vulnerabilityMinor));
    $('#js-vulnerability-info').html(displayNumber(t.vulnerabilityInfo));

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

    $('#js-code-smell-blocker').html(displayNumber(t.codeSmellBlocker));
    $('#js-code-smell-critical').html(displayNumber(t.codeSmellCritical));
    $('#js-code-smell-major').html(displayNumber(t.codeSmellMajor));
    $('#js-code-smell-minor').html(displayNumber(t.codeSmellMinor));
    $('#js-code-smell-info').html(displayNumber(t.codeSmellInfo));

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
    log(' - 🎨 [Peinture] Affichage des informations sur le détail des anomalies.');
  } catch(error) {
      ErrorButtonAffiche();
      const trace = prepareTechnicalDetails(error);
      const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur le détail des anomalies (Erreur 500).";
      showMessage('critical', message, trace);
      sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Anomalie.');
      log(' - 🔴 [Peinture] Affichage des informations sur le détail des anomalies en échec.');
      return;
  }

  log(' - ✅ [Peinture] Affichage des informations terminé.');
  return 0;
};

/**
 * [Description for afficheHotspotDetails]
 * On récupère la répartition des hotspot par sévérité pour le type to_review et reviewed
 * http://{url}/api/secure/peinture/projet/hotspot/details{meven_key}
 *
 * @param string mavenKey
 *
 * @return response
 *
 * Created at: 19/12/2022, 22:25:28 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
export const afficheHotspotDetails = async function (maven_key){
  /* On récupère la répartition des hotspot. */
  const data = { maven_key };
  const options = {
    url: `${serveur()}/api/secure/peinture/projet/hotspots/details`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  try {
    const t = await $.ajax(options);
    const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_503, http_504];
    if (errorCodes.includes(t.code)){
        const hasTrace = !!t.trace;
        const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
        showMessage(t.type, t.message, trace);
        sessionStorage.setItem('ma_moulinette_peinture', 'Erreur - récupération des informations sur les menaces potentielles.');
        if (t.code === http_404){
            log(` - 💡 [Peinture] Le projet n'existe pas. Lance une collecte avant.`);
            enableButtonAnalyse();
        } else {
          log(' - ❌ [Peinture] Affichage des informations sur le détail des menaces potentielles en échec.');
        }
        return;
    }

    /* On efface les données.*/
    $('#tableau-menace-potentielle').html('');
    const str =`
      <tr id="to-review">
        <td class="open-sans text-left">Review</td>
        <td id="menace-potentielle-to-review-high" class="text-center stat" data-menace-potentielle-to-review-high="${t.to_review_high}">
          ${displayNumber(t.to_review_high)}</td>
        <td id="menace-potentielle-to-review-medium" class="text-center stat" data-menace-potentielle-to-review-medium="${t.to_review_medium}">
          ${displayNumber(t.to_review_medium)}</td>
        <td id="menace-potentielle-to-review-low" class="text-center stat" data-menace-potentielle-to-review-low="${t.to_review_low}">
          ${displayNumber(t.to_review_low)}</td>
      </tr>
      <tr id="reviewed">
        <td class="open-sans text-left">Reviewed</td>
        <td id="menace-potentielle-reviewed-high" class="text-center stat" data-menace-potentielle-reviewed-high="${t.reviewed_high}">
          ${displayNumber(t.reviewed_high)}</td>
        <td id="menace-potentielle-reviewed-medium" class="text-center stat" data-menace-potentielle-reviewed-medium="${t.reviewed_medium}">
          ${displayNumber(t.reviewed_medium)}</td>
        <td id="menace-potentielle-reviewed-low" class="text-center stat" data-menace-potentielle-reviewed-low="${t.to_review_low}">
          ${displayNumber(t.reviewed_low)}</td>
      </tr>`;

    $('#tableau-menace-potentielle').append(str);
    //calcul du nombre de menace à examiner
    const reviewed_total = Number(t.reviewed_high +t.reviewed_medium + t.reviewed_low);
    const total = Number(t.to_review_total - t.reviewed_total);
    let calcul = 0;
    if (total > 0) {
      calcul = 1 - (reviewed_total / Number(t.to_review_total));
    }
    const repartition = new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(calcul);

    $('#menace-potentielle-totale').html(`<span class="stat">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(total)}</span> <span>(${repartition})`);
    if (parseInt(t.to_review_total, 10) > 0) {
      $('.menace-potentielle-s').html('s');
    }

    const t1 = document.getElementById('menace-potentielle-totale');
    t1.dataset.menacePotentielleTotale = (total);
    log(' - 🎨 [Peinture] Mise à jour du tableau du détail des menaces potentielles.');
  } catch(error) {
    ErrorButtonAffiche();
    const trace = prepareTechnicalDetails(error);
    const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur le détail des menaces potentielles (Erreur 500).";
    showMessage('critical', message, trace);
    sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Menaces potentielles.');
    log(' - ❌ [Peinture] Affichage des informations sur le détail des menaces potentielles en échec.');
    return;
  }

  log(' - ✅ [Peinture] Mise à jour du tableau terminée.');
  enableButtonAnalyse();
  return 0;
};
