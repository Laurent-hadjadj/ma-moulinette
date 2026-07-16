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

// Intégration de jquery
import $ from 'jquery';

// On importe les paramètres serveur
import {serveur} from '../../common/properties.js';

import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/** On importe le fonction de journalisation */
import { log } from '../../common/log.js';

/** On importe les constantes */
import {content_type, dateOptions, http_400, http_401, http_403, http_404, http_500,
http_503, http_504, dix, cent, dixMille} from '../../common/constante.js';

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
 * @param string maven_key
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
 * @param string maven_key
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

        $('#js-nom-projet').html(nom);
        $('#js-key-analyse').html(t.analyse_key);
        $('#js-clef-projet').html(maven_key);
        $('#js-version-release').html(t.release);
        $('#js-version-snapshot').html(t.snapshot);
        $('#js-version-autre').html(t.autre);

        const version = document.getElementById('js-version-autre');
        version.dataset.label = JSON.stringify(t.label);
        version.dataset.dataset = JSON.stringify(t.dataset);
        $('#js-version').html(t.version);
        $('#js-date-version').html(new Intl.DateTimeFormat('default', dateOptions).format(new Date(t.date)));

        /** Historique */
        const t0 = document.getElementById('js-key-analyse');
        const t1 = document.getElementById('js-version-release');
        const t2 = document.getElementById('js-version-snapshot');
        const t3 = document.getElementById('js-version-autre');
        const t4 = document.getElementById('js-date-version');
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

      /* MODIF 2026-05-06 :
       * Le bloc info n'affiche plus que le Total agrégé. La repartition par
       * sous-category à étè déplacée dans la modale (#js-*-modale).
       * Anciens selectors #suppress-warning, #no-pmd, #check-style, #no-sonar,
       * #no-sonar-python, #no-sonar-php retires. */
      $('#js-no-sonar-total').html(displayNumber(t.total));

      /* MODIF 2026-05-15 : on stocke
       * sur le dataset de #no-sonar-total (élément stable) les valeurs que
       * enregistrement.js lisait auparavant sur #suppress-warning et #no-sonar
       * (ids retirés par MODIF 2026-05-06). Pattern data-attribute → lecture
       * propre côté enregistrement.js, pas de parsing fragile sur innerHTML. */
      const noSonarTotalEl = document.getElementById('js-no-sonar-total');
      if (noSonarTotalEl) {
        noSonarTotalEl.dataset.s1309         = String(t.suppress_warning ?? 0);
        noSonarTotalEl.dataset.nosonar       = String(
          (parseInt(t.java_no_sonar   ?? 0, 10) || 0)
        + (parseInt(t.python_no_sonar ?? 0, 10) || 0)
        + (parseInt(t.php_no_sonar    ?? 0, 10) || 0)
        );
        /* MODIF 2026-05-15 : 5 sous-catégories supplémentaires nécessaires
         * pour l'enregistrement (colonnes historique.no_pmd, check_style,
         * java_no_sonar, python_no_sonar, php_no_sonar). Sans ces datasets,
         * le payload JS ne les envoie pas et le controller persiste null. */
        noSonarTotalEl.dataset.noPmd         = String(t.no_pmd         ?? 0);
        noSonarTotalEl.dataset.checkStyle    = String(t.check_style    ?? 0);
        noSonarTotalEl.dataset.javaNoSonar   = String(t.java_no_sonar   ?? 0);
        noSonarTotalEl.dataset.pythonNoSonar = String(t.python_no_sonar ?? 0);
        noSonarTotalEl.dataset.phpNoSonar    = String(t.php_no_sonar    ?? 0);
      }

      /* MODIF 2026-05-06 : remplissage modale Detail NoSonar
       * (6 sous-categories + 10 premieres occurrences fichier/ligne). */
      $('#js-suppress-warning-modale').html(displayNumber(t.suppress_warning));
      $('#js-no-pmd-modale').html(displayNumber(t.no_pmd));
      $('#js-check-style-modale').html(displayNumber(t.check_style));
      $('#js-no-sonar-java-modale').html(displayNumber(t.java_no_sonar));
      $('#js-no-sonar-python-modale').html(displayNumber(t.python_no_sonar));
      $('#js-no-sonar-php-modale').html(displayNumber(t.php_no_sonar));

      $('#tableau-liste-nosonar-detail').html('');
      const nsListe = (t.details && t.details.liste) ? t.details.liste : [];
      const nsMaxOccurrences = 10;
      const nsMaxLength = 80;
      let nsLimit = 0;
      for (let i = 0; i < nsListe.length && nsLimit < nsMaxOccurrences; i++) {
        const el = nsListe[i];
        const cutRule = (el.rule || '').split(':')[0] || el.rule || '';
        const cutComponentArray = (el.component || '').split(':');
        const cutComponent = (cutComponentArray.length === 1)
          ? cutComponentArray[0]
          : (cutComponentArray[2] || cutComponentArray[1] || cutComponentArray[0]);
        const truncatedLine = cutComponent.length > nsMaxLength
          ? '...' + cutComponent.substring(cutComponent.length - nsMaxLength)
          : cutComponent;
        const ligne = `<tr><td><strong>${el.rule || cutRule}</strong></td><td>${truncatedLine}</td><td class="text-center">${el.line ?? '–'}</td></tr>`;
        $('#tableau-liste-nosonar-detail').append(ligne);
        nsLimit++;
      }

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
    /* MODIF 2026-05-06 : ajout php / python / ruby. */
    $('#js-todo-liste').html(displayNumber(t.todo));
    $('#js-java').html(displayNumber(t.java));
    $('#js-javascript').html(displayNumber(t.javascript));
    $('#js-typescript').html(displayNumber(t.typescript));
    $('#js-php').html(displayNumber(t.php));
    $('#js-python').html(displayNumber(t.python));
    $('#js-ruby').html(displayNumber(t.ruby));
    $('#js-html').html(displayNumber(t.html));
    $('#js-xml').html(displayNumber(t.xml));

    /* On ajoute la liste détaillée des fichiers */
    let cutComponent;
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

    const t50 = document.getElementById('js-todo-liste');
    const t51 = document.getElementById('js-java');
    const t52 = document.getElementById('js-javascript');
    const t53 = document.getElementById('js-typescript');
    const t54 = document.getElementById('js-html');
    const t55 = document.getElementById('js-xml');
    const t56 = document.getElementById('tableau-liste-detail');
    /* MODIF 2026-05-06 : datasets supplémentaires. */
    const t57 = document.getElementById('js-php');
    const t58 = document.getElementById('js-python');
    const t59 = document.getElementById('js-ruby');
    t50.dataset.todo=(t.todo);
    t51.dataset.java=(t.java);
    t52.dataset.javascript=(t.javascript);
    t53.dataset.typescript=(t.typescript);
    t54.dataset.html=(t.html);
    t55.dataset.xml=(t.xml);
    t56.dataset.listeFichier=(t.details);
    if (t57) t57.dataset.php=(t.php);
    if (t58) t58.dataset.python=(t.python);
    if (t59) t59.dataset.ruby=(t.ruby);
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

    /* MODIF 2026-05-04 : le backend renvoie maintenant null
      * (au lieu de -1) quand la table logger est vide (plugin SonarQube inactif).
      * Pour le dataset HTML, on coalesce vers 0 (l'historique enregistre une valeur numérique).
      * Pour l'affichage (plus bas via displayNumber), null → '-' (distingue "pas de plugin" de "0 logger"). */
    logger1.dataset.loggerInfo = String(t.logger_info ?? 0);
    logger2.dataset.loggerWarn = String(t.logger_warn ?? 0);
    logger3.dataset.loggerError = String(t.logger_error ?? 0);
    logger4.dataset.loggerDebug = String(t.logger_debug ?? 0);

    $('#js-logger-liste').html(displayNumber(t.total));
    $('#js-logger-total').html(displayNumber(t.total));
    $('#js-logger-info').html(displayNumber(t.logger_info));
    $('#js-logger-warn').html(displayNumber(t.logger_warn));
    $('#js-logger-error').html(displayNumber(t.logger_error));
    $('#js-logger-debug').html(displayNumber(t.logger_debug));

    /** Injecte les pourcentages */
    $('#js-logger-info-pct').html(displayPercent(t.logger_info_pct));
    $('#js-logger-warn-pct').html(displayPercent(t.logger_warn_pct));
    $('#js-logger-error-pct').html(displayPercent(t.logger_error_pct));
    $('#js-logger-debug-pct').html(displayPercent(t.logger_debug_pct));

    /** Injecte les barres de pourcentage */
    $('#js-logger-info-bar').css('--percent', t.logger_info_pct ?? 0);
    $('#js-logger-warn-bar').css('--percent', t.logger_warn_pct ?? 0);
    $('#js-logger-error-bar').css('--percent', t.logger_error_pct ?? 0);
    $('#js-logger-debug-bar').css('--percent', t.logger_debug_pct ?? 0);

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

    /** On récupère les détails des loggers pour alimenter le tableau et les données pour la table historique */
    const optionsLoggerDetails =  {
      url: `${serveur()}/api/secure/peinture/projet/logger-details`,
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
      const t = await $.ajax(optionsLoggerDetails);
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
            log(' - ❌ [Peinture] Affichage du détail des informations sur les loggers JAVA en échec.');
          }
          return;
        }

      // Reconstruit le format { info: {SLF4J: N, ...}, warn: {...}, ... }
      // à partir de breakdown_matrix : [{level, framework, nb}, ...]
      const breakdown = { info: {}, warn: {}, error: {}, debug: {} };
      for (const row of (t.breakdown_matrix || [])) {
          const lvl = row.level;
          const fw  = row.framework === null ? '—' : row.framework;
          if (!breakdown[lvl]) { breakdown[lvl] = {}; }
          breakdown[lvl][fw] = parseInt(row.nb, 10);
        }
        // MODIF 2026-05-15 : on récupère l'élément dans CE scope (le bloc try
        // précédent qui définissait `logger1` est désormais fermé).
        const loggerInfoEl = document.getElementById('js-affiche-logger-detail');
        if (loggerInfoEl) {
          loggerInfoEl.dataset.loggerBreakdown = JSON.stringify(breakdown);
          loggerInfoEl.dataset.loggerDetails   = JSON.stringify(t.details || []);
        }

        log(` - 🎨 [Peinture] Breakdown loggers (level × framework) récupéré : ${t.breakdown_matrix?.length ?? 0} combinaisons.`);
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

    /* MODIF 2026-05-06 : on expose la totalité du payload
     * (~80 champs renvoyés par ApiPeintureController::peintureProjetMesures) dans un
     * objet global pour que enregistrement.js puisse l'envoyer en INSERT sans avoir
     * a poser un dataset DOM par valeur. */
    window.peintureData = t;

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
    $('#js-distribution-langage').html('');

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
          $('#js-distribution-langage').append(item);
      }
    }

    $('#js-nombre-ligne').html(displayNumber(t.lines));
    $('#js-nombre-ligne-de-code').html(displayNumber(t.ncloc));

    $('#js-nombre-fichier').html(displayNumber(t.files));
    $('#js-nombre-classe').html(displayNumber(t.classes));
    $('#js-nombre-fonction').html(displayNumber(t.functions));
    $('#js-nombre-statement').html(displayNumber(t.statements));

    $('#js-complexity-ratio').html(displayNumber(t.complexity_ratio));
    $('#js-cognitive-complexity-ratio').html(displayNumber(t.cognitive_complexity_ratio));

    $('#js-coverage').html(displayPercent(t.coverage));
    $('#js-ratio-dette-technique').html(displayPercent(t.sqale_debt_ratio));

    /** On colorise le résultat */
    if (t.sqale_debt_ratio <= 30){
      /** La dette technique est soutenable */
      $('#js-ratio-dette-technique').addClass('couleur-vert');
    }
    if (t.sqale_debt_ratio > 30 && t.sqale_debt_ratio <= 60){
      /** La dette technique est importante, il faut absolument commencer à la réduire */
      $('#js-ratio-dette-technique').addClass('couleur-orange');
    }
    if (t.sqale_debt_ratio > 60 && t.sqale_debt_ratio <= 100){
      /** La dette technique n'est plus soutenable, il faut envisager de réécrire l'application ! */
      $('#js-ratio-dette-technique').addClass('couleur-rouge');
    }
    if (t.sqale_debt_ratio >60 && t.sqale_debt_ratio > 100){
      /** l'application est mauvaise, le ration est > à 100% */
      $('#js-ratio-dette-technique').addClass('couleur-bordeaux');
    }

    $('#js-duplicated-lines-density').html(displayPercent(t.duplicated_lines_density));
    $('#js-tests').html(displayNumber(t.tests));
    $('#js-violations').html(displayNumber(t.violations));

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
      $(selector).html(`<span class="stat-note stat-note-width ${cssClass}">${displayValue(rating)}</span>`);
    };
    renderRating('#js-note-reliability', t.reliability_rating);
    renderRating('#js-note-security', t.security_rating);
    renderRating('#js-note-sqale', t.sqale_rating);
    renderRating('#js-note-menace-potentielle', t.security_review_rating);
    renderRating('#js-note-complexity', t.complexity_rating);
    renderRating('#js-note-cognitive-complexity', t.cognitive_complexity_rating);
    renderRating('#js-note-coverage', t.coverage_rating);
    renderRating('#js-note-duplication', t.duplicated_lines_rating);
    renderRating('#js-note-comment-lines', t.comment_lines_rating);

    /** Quality Gate : OK / WARN / ERROR */
    const colorByQualityGate = {
      'OK': 'note-vert1',
      'WARN': 'note-jaune',
      'ERROR': 'note-rouge'
    };
    const qgClass = colorByQualityGate[t.alert_status] ?? '';
    $('#js-alert-status').html(`<span class="stat-note stat-note-qg ${qgClass}">${displayValue(t.alert_status)}</span>`);

    /** Documentation */
    $('#js-comment-lines').html(displayNumber(t.comment_lines));
    $('#js-comment-lines-density').html(displayPercent(t.comment_lines_density));

    /** Détail Tests */
    $('#js-test-errors').html(displayNumber(t.test_errors));
    $('#js-test-failures').html(displayNumber(t.test_failures));
    $('#js-skipped-tests').html(displayNumber(t.skipped_tests));
    $('#js-test-success-density').html(displayPercent(t.test_success_density));

    /** Statut anomalies (workflow de tri) */
    $('#js-accepted-issues').html(displayNumber(t.accepted_issues));
    $('#js-false-positive-issues').html(displayNumber(t.false_positive_issues));

    //Historique
    const t7 = document.getElementById('js-nombre-ligne');
    const t8 = document.getElementById('js-nombre-ligne-de-code');
    const t8a = document.getElementById('js-nombre-fichier');
    const t8b = document.getElementById('js-nombre-classe');
    const t8c = document.getElementById('js-nombre-fonction');

    const t9 = document.getElementById('js-coverage');
    const t9a = document.getElementById('js-ratio-dette-technique');
    const t10 = document.getElementById('js-duplicated-lines-density');
    const t11 = document.getElementById('js-tests');
    const t12 = document.getElementById('js-violations');

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
      $('#js-dette').html(t.dette);
      $('#js-dette-reliability').html(t.detteReliability);
      $('#js-dette-vulnerability').html(t.detteVulnerability);
      $('#js-dette-code-smell').html(t.detteCodeSmell);

      /** Historique */
      const t13 = document.getElementById('js-dette');
      t13.dataset.detteMinute=t.detteMinute;

      /* Nombre d'anomalie */
      $('#js-nombre-bug-total').html(displayNumber(t.bug_total));
      $('#js-separator-bug').html('|');

      $('#js-nombre-vulnerability').html(displayNumber(t.vulnerability));
      $('#js-nombre-mauvaise-pratique').html(displayNumber(t.code_smell_total));
      /** 10000 = le nombre max des retours possibles */
      if (Number(t.code_smell_total) === dixMille) {
        $('#js-nombre-mauvaise-pratique').addClass('couleur-rouge');
      }

      /** Historique */
      const t14 = document.getElementById('js-nombre-bug-total');
      const t15 = document.getElementById('js-nombre-vulnerability');
      const t16 = document.getElementById('js-nombre-mauvaise-pratique');
      t14.dataset.nombreBug = (t.bug_total);
      t15.dataset.nombreVulnerability = (t.vulnerability);
      t16.dataset.nombreCodeSmell = (t.code_smell_total);

      /* Répartition modules */
      const totalModule = Number(t.frontend + t.backend + t.autre + t.inconnu);

      const categories = [
        { key: 'frontend', elCount: '#js-nombre-frontend', elPercent: '#js-nombre-frontend-percent', dataAttr: 'nombreFrontend' },
        { key: 'backend',  elCount: '#js-nombre-backend', elPercent: '#js-nombre-backend-percent',  dataAttr: 'nombreBackend'  },
        { key: 'autre',    elCount: '#js-nombre-autre', elPercent: '#js-nombre-autre-percent',    dataAttr: 'nombreAutre'    },
        { key: 'inconnu',  elCount: '#js-nombre-inconnu', elPercent: '#js-nombre-inconnu-percent',  dataAttr: 'nombreInconnu'  },
      ];

      const fmtDecimal  = new Intl.NumberFormat('fr-FR', { style: 'decimal' });
      const fmtPercent  = new Intl.NumberFormat('fr-FR', { style: 'percent' });
      const htmlPad1    = '<span style="color:#fff;">0</span>';
      const htmlPad2    = '<span style="color:#fff;">00</span>';

      /**
       * Retourne le padding invisible pour aligner les pourcentages
       * (<10% → "00", 10-99% → "0", 100% → "")
       */
      const percentPadding = function(ratio) {
        const pct = ratio * 100;
        if (pct < 10)  return htmlPad2;
        if (pct < 100) return htmlPad1;
        return '';
      }

      categories.forEach(({ key, elCount, elPercent, dataAttr }) => {
        const value  = Number(t[key]);
        const ratio  = totalModule > 0 ? value / totalModule : 0;
        const pad    = totalModule > 0 ? percentPadding(ratio) : '';

        $(elCount).html(fmtDecimal.format(value));
        $(elPercent).html(pad + fmtPercent.format(ratio));

        // Historique via data-attribute
        $(elCount)[0].dataset[dataAttr] = value;
      });

      /** Historique */
      const t17 = document.getElementById('js-nombre-frontend');
      const t18 = document.getElementById('js-nombre-backend');
      const t19 = document.getElementById('js-nombre-autre');
      const t19a = document.getElementById('js-nombre-inconnu');
      t17.dataset.nombreFrontend = t.frontend;
      t18.dataset.nombreBackend = t.backend;
      t19.dataset.nombreAutre = t.autre;
      t19a.dataset.nombreInconnu = t.inconnu;

      /* Répartition des violations par sévérité */
      $('#js-nombre-violation-bloquant').html(displayNumber(t.blocker));
      $('#js-nombre-violation-critique').html(displayNumber(t.critical));
      $('#js-nombre-violation-info').html(displayNumber(t.info));
      $('#js-nombre-violation-majeur').html(displayNumber(t.major));
      $('#js-nombre-violation-mineur').html(displayNumber(t.minor));
      const totalCodeSmellReal = Number(t.blocker + t.critical + t.major + t.minor);

      // On ajoute un séparateur entre les deux résultats
      $('#js-separator-code-smell').html('|');
      $('#js-nombre-mauvaise-pratique-real').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(totalCodeSmellReal));

      const t23 = document.getElementById('js-nombre-violation-bloquant');
      const t24 = document.getElementById('js-nombre-violation-critique');
      const t25 = document.getElementById('js-nombre-violation-info');
      const t26 = document.getElementById('js-nombre-violation-majeur');
      const t27 = document.getElementById('js-nombre-violation-mineur');
      t23.dataset.nombreViolationsBloquant = t.blocker;
      t24.dataset.nombreViolationsCritique = t.critical;
      t25.dataset.nombreViolationsInfo = t.info;
      t26.dataset.nombreViolationsMajeur = t.major;
      t27.dataset.nombreViolationsMineur = t.minor;

      log(' - 🎨 [Peinture] Affichage des informations sur les violations.');
    } catch(error) {
          ErrorButtonAffiche();
          const trace = prepareTechnicalDetails(error);
          const message = "Une erreur inattendue s'est produite lors l'affichage des informations sur les violations (Erreur 500).";
          showMessage('critical', message, trace);
          sessionStorage.setItem('ma_moulinette_peinture', 'Erreur Bloc Violation.');
          log(' - 🔴 [Peinture] Affichage des informations sur les Violations en échec.');
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

    if (t.bug_real > 0) {
        //bug_real = bug_total - bug_info
        $('#js-nombre-bug-real').html(`( ${displayNumber(t.bug_real)})`);
      }
      else {
        $('#js-nombre-bug-real').html('-');
      }

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

    if (t.CodeSmellRealTotal > 0) {
        //codeSmell_real = codeSmell_total - codeSmell_info
        $('#js-nombre-mauvaise-pratique-real').html(`${displayNumber(t.CodeSmellRealTotal)}`);
      }
      else {
        $('#js-nombre-mauvaise-pratique-real').html('--');
      }
    const t38 = document.getElementById('js-code-smell-blocker');
    const t39 = document.getElementById('js-code-smell-critical');
    const t40 = document.getElementById('js-code-smell-major');
    const t41 = document.getElementById('js-code-smell-minor');
    const t42 = document.getElementById('js-code-smell-info');
    t38.dataset.codeSmellBlocker = t.codeSmellBlocker;
    t39.dataset.codeSmellCritical = t.codeSmellCritical;
    t40.dataset.codeSmellMajor = t.codeSmellMajor;
    t41.dataset.codeSmellMinor = t.codeSmellMinor;
    t42.dataset.codeSmellInfo = t.codeSmellInfo;

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
 * @param string maven_key
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
    $('#js-tableau-menace-potentielle').html('');
    const str =`
      <tr id="to-review">
        <td class="open-sans text-left">Review</td>
        <td id="js-menace-potentielle-to-review-high" class="text-center stat" data-menace-potentielle-to-review-high="${t.to_review_high}">
          ${displayNumber(t.to_review_high)}</td>
        <td id="js-menace-potentielle-to-review-medium" class="text-center stat" data-menace-potentielle-to-review-medium="${t.to_review_medium}">
          ${displayNumber(t.to_review_medium)}</td>
        <td id="js-menace-potentielle-to-review-low" class="text-center stat" data-menace-potentielle-to-review-low="${t.to_review_low}">
          ${displayNumber(t.to_review_low)}</td>
      </tr>
      <tr id="reviewed">
        <td class="open-sans text-left">Reviewed</td>
        <td id="js-menace-potentielle-reviewed-high" class="text-center stat" data-menace-potentielle-reviewed-high="${t.reviewed_high}">
          ${displayNumber(t.reviewed_high)}</td>
        <td id="js-menace-potentielle-reviewed-medium" class="text-center stat" data-menace-potentielle-reviewed-medium="${t.reviewed_medium}">
          ${displayNumber(t.reviewed_medium)}</td>
        <td id="js-menace-potentielle-reviewed-low" class="text-center stat" data-menace-potentielle-reviewed-low="${t.to_review_low}">
          ${displayNumber(t.reviewed_low)}</td>
      </tr>`;

    $('#js-tableau-menace-potentielle').append(str);
    //calcul du nombre de menace à examiner
    const reviewed_total = Number(t.reviewed_high +t.reviewed_medium + t.reviewed_low);
    const total = Number(t.to_review_total - t.reviewed_total);
    let calcul = 0;
    if (total > 0) {
      calcul = 1 - (reviewed_total / Number(t.to_review_total));
    }
    const repartition = new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(calcul);

    $('#js-menace-potentielle-totale').html(`<span class="stat">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(total)}</span> <span>(${repartition})`);
    if (parseInt(t.to_review_total, 10) > 0) {
      $('.menace-potentielle-s').html('s');
    }

    const t1 = document.getElementById('js-menace-potentielle-totale');
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
