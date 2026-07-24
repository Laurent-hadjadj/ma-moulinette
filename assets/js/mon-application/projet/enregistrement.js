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


/** Intégration de jquery */
import $ from 'jquery';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

/** On importe les constantes */
import {dateOptions, content_type, http_200, http_400, http_401, http_403, http_404, http_500, http_502, http_504} from '../../common/constante.js';

import { showMessage,  hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

/**
 * [Description for log]
 * Affiche la log.
 *
 * @param mixed txt
 *
 * @return void
 *
 * Created at: 13/12/2022, 12:58:45 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const log = function(txt) {
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
export const enregistrement = async function(maven_key) {
  /** On récupère les informations sur les versions */
  const project_name = $('#js-nom-projet').text().trim();

  /*
   * On enregistre des données brutes pour l'enregistrement.
   * On n'utilise jquery pour la gestion du data Attribute car ce n'est pas fiable.
   * On utilise à la place l'appel JS standard.
   */
  const t0 = document.getElementById('js-key-analyse');
  const t1 = document.getElementById('js-version-release');
  const t2a = document.getElementById('js-version-snapshot');
  const t2b = document.getElementById('js-version-autre');
  const analyseKey = t0.dataset.analyseKey;
  const versionRelease = t1.dataset.release;
  const versionSnapshot = t2a.dataset.snapshot;
  const versionAutre = t2b.dataset.autre;

  const version = $('#js-version').text().trim();
  const t3 = document.getElementById('js-date-version');
  const dateVersion = t3.dataset.dateVersion;

  /** On récupère les exclusions noSonar.
   *  MODIF 2026-05-15 : les anciens ids #suppress-warning et #no-sonar ont
   *  été retirés par la refonte modale du 2026-05-06. Les valeurs sont
   *  désormais stockées sur le dataset de #no-sonar-total (élément stable)
   *  par peinture.js. Lecture défensive ( ?? 0 ) au cas où le bloc n'est
   *  pas affiché sur cette page. */
  const t4 = document.getElementById('js-no-sonar-total');
  const suppressWarning = t4 ? (t4.dataset.s1309         ?? 0) : 0;
  const noSonar         = t4 ? (t4.dataset.nosonar       ?? 0) : 0;
  /* MODIF 2026-05-15 : 5 sous-catégories alimentées par peinture.js
   * sur le même élément #no-sonar-total. Sans ces lignes, les colonnes
   * historique.no_pmd, check_style, java/python/php_no_sonar restent null. */
  const noPmd         = t4 ? (t4.dataset.noPmd         ?? 0) : 0;
  const checkStyle    = t4 ? (t4.dataset.checkStyle    ?? 0) : 0;
  const javaNoSonar   = t4 ? (t4.dataset.javaNoSonar   ?? 0) : 0;
  const pythonNoSonar = t4 ? (t4.dataset.pythonNoSonar ?? 0) : 0;
  const phpNoSonar    = t4 ? (t4.dataset.phpNoSonar    ?? 0) : 0;

  /** On récupère le nombre des to..do */
  const t5a = document.getElementById('js-todo-liste');
  const todo = t5a ? t5a.dataset.todo : 0;

  /** On récupère le nombre de Logger
   *  MODIF 2026-05-15 : lecture défensive — si un des
   *  éléments DOM #js-logger-* n'existe pas (template modifié, page
   *  partielle, etc.), on retombe sur 0 plutôt que de crasher avec
   *  "Cannot read properties of null (reading 'dataset')". */
  const jsLoggerInfo  = document.getElementById('js-logger-info');
  const jsLoggerWarn  = document.getElementById('js-logger-warn');
  const jsLoggerError = document.getElementById('js-logger-error');
  const jsLoggerDebug = document.getElementById('js-logger-debug');
  const loggerInfo  = jsLoggerInfo  ? jsLoggerInfo.dataset.loggerInfo   : 0;
  const loggerWarn  = jsLoggerWarn  ? jsLoggerWarn.dataset.loggerWarn   : 0;
  const loggerError = jsLoggerError ? jsLoggerError.dataset.loggerError : 0;
  const loggerDebug = jsLoggerDebug ? jsLoggerDebug.dataset.loggerDebug : 0;

  /* MODIF 2026-05-15 : récupération du breakdown
    (level × framework) déposé sur le dataset de #js-affiche-logger-detail
    par peinture.js (via endpoint /api/secure/peinture/projet/logger-details).
    Format attendu :
      {info: {SLF4J: N, ...}, warn: {...}, error: {...}, debug: {...}}.
    Null si plugin track-logger-method désactivé / endpoint en échec
     -> envoie null au backend, persisté tel quel dans historique.logger_breakdown. */
  let loggerBreakdown = null;
  const jsAfficheLoggerDetail = document.getElementById('js-affiche-logger-detail');
  if (jsAfficheLoggerDetail && jsAfficheLoggerDetail.dataset.loggerBreakdown) {
    try {
      loggerBreakdown = JSON.parse(jsAfficheLoggerDetail.dataset.loggerBreakdown);
    } catch (e) {
      loggerBreakdown = null;
    }
  }

  /* MODIF 2026-07-23 : récupération du JSON Actuator déposé sur le dataset de
   * #js-actuator-pastille (par projetActuator() en collecte, ou par le bloc
   * Actuator de peinture.js en lecture historique) — [] si absent (pas de
   * point d'accès déclaré, ou peinture jamais lancée), persisté tel quel dans
   * historique.actuator_info. */
  let actuatorInfo = [];
  const jsActuatorPastille = document.getElementById('js-actuator-pastille');
  if (jsActuatorPastille && jsActuatorPastille.dataset.actuatorJson) {
    try {
      actuatorInfo = JSON.parse(jsActuatorPastille.dataset.actuatorJson);
    } catch (e) {
      actuatorInfo = [];
    }
  }

  /**
   * On récupère les informations du projet :
   * lignes, coverage fonctionnelle, duplication, tests unitaires et
   * le nombre de défaut.
    */
  const t6 = document.getElementById('js-nombre-ligne');
  const t7 = document.getElementById('js-nombre-ligne-de-code');
  const t7a = document.getElementById('js-nombre-fichier');
  const t7b = document.getElementById('js-nombre-classe');
  const t7c = document.getElementById('js-nombre-fonction');

  const t8 = document.getElementById('js-coverage');
  const t8a = document.getElementById('js-ratio-dette-technique');
  const t9 = document.getElementById('js-duplicated-lines-density');
  const t10 = document.getElementById('js-tests');
  const t11 = document.getElementById('js-violations');
  const nombreLigne = t6.dataset.nombreLigne;
  const nombreLigneDeCode = t7.dataset.nombreLigneDeCode;
  const nombreFichier = t7a.dataset.nombreFichier;
  const nombreClasse = t7b.dataset.nombreClasse;
  const nombreFonction = t7c.dataset.nombreFonction;
  const coverage = t8.dataset.coverage;
  const sqaleDebtRatio = t8a.dataset.sqaleDebtRatio;
  const duplicated_lines_density = t9.dataset.duplicatedLinesDensity;
  const tests = t10.dataset.tests;
  const violations = t11.dataset.violations;

  /** On récupère les informations sur la dette technique et les anomalies. */
  /* Dette technique */
  const t12 = document.getElementById('js-dette');
  const dette=t12.dataset.detteMinute;

  /* Nombre d'anomalie par type */
  const t13 = document.getElementById('js-nombre-bug-total');
  const t14 = document.getElementById('js-nombre-vulnerability');
  const t15 = document.getElementById('js-nombre-mauvaise-pratique');
  const nombreBug = t13.dataset.nombreBug;
  const nombreVulnerability = t14.dataset.nombreVulnerability;
  const nombreCodeSmell = t15.dataset.nombreCodeSmell;

  /* répartition des anomalies par module */
  const t16 = document.getElementById('js-nombre-frontend');
  const t17 = document.getElementById('js-nombre-backend');
  const t18 = document.getElementById('js-nombre-autre');
  const t18a = document.getElementById('js-nombre-inconnu');
  const frontend = t16.dataset.nombreFrontend;
  const backend = t17.dataset.nombreBackend;
  const autre = t18.dataset.nombreAutre;
  const inconnu = t18a.dataset.nombreInconnu;

  /* Répartition des anomalies par sévérité */
  const t19 = document.getElementById('js-nombre-violation-bloquant');
  const t20 = document.getElementById('js-nombre-violation-critique');
  const t21 = document.getElementById('js-nombre-violation-info');
  const t22 = document.getElementById('js-nombre-violation-majeur');
  const t23 = document.getElementById('js-nombre-violation-mineur');
  const nombreViolationsBloquant = t19.dataset.nombreViolationsBloquant;
  const nombreViolationsCritique = t20.dataset.nombreViolationsCritique;
  const nombreViolationsInfo = t21.dataset.nombreViolationsInfo;
  const nombreViolationsMajeur = t22.dataset.nombreViolationsMajeur;
  const nombreViolationsMineur = t23.dataset.nombreViolationsMineur;

  /** On récupère les notes SonarQube pour la version courante */
  const noteReliability=$('#js-note-reliability').text().trim();
  const noteSecurity=$('#js-note-security').text().trim();
  const noteSqale=$('#js-note-sqale').text().trim();

  /** On récupère les hotspots. */
  const noteHotspot = $('#js-note-menace-potentielle').text().trim();

  /** On récupère les hotspot par sévérité */
  const t24 = document.getElementById('js-menace-potentielle-to-review-high');
  const t25 = document.getElementById('js-menace-potentielle-to-review-medium');
  const t26 = document.getElementById('js-menace-potentielle-to-review-low');
  const t24b = document.getElementById('js-menace-potentielle-reviewed-high');
  const t25b = document.getElementById('js-menace-potentielle-reviewed-medium');
  const t26b = document.getElementById('js-menace-potentielle-reviewed-low');
  const t27 = document.getElementById('js-menace-potentielle-totale');
  const menacePotentielleToReviewHigh = t24.dataset.menacePotentielleToReviewHigh;
  const menacePotentielleToReviewMedium = t25.dataset.menacePotentielleToReviewMedium;
  const menacePotentielleToReviewLow = t26.dataset.menacePotentielleToReviewLow;
  const menacePotentielleReviewedHigh = t24b.dataset.menacePotentielleReviewedHigh;
  const menacePotentielleReviewedMedium = t25b.dataset.menacePotentielleReviewedMedium;
  const menacePotentielleReviewedLow = t26b.dataset.menacePotentielleReviewedLow;
  const menacePotentielleTotale = t27.dataset.menacePotentielleTotale;

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

  const bugBlocker = t28.dataset.bugBlocker;
  const bugCritical = t29.dataset.bugCritical;
  const bugMajor = t30.dataset.bugMajor;
  const bugMinor = t31.dataset.bugMinor;
  const bugInfo = t32.dataset.bugInfo;
  const vulnerabilityBlocker = t33.dataset.vulnerabilityBlocker;
  const vulnerabilityCritical=t34.dataset.vulnerabilityCritical;
  const vulnerabilityMajor = t35.dataset.vulnerabilityMajor;
  const vulnerabilityMinor = t36.dataset.vulnerabilityMinor;
  const vulnerabilityInfo  = t37.dataset.vulnerabilityInfo;

  const codeSmellBlocker = t38.dataset.codeSmellBlocker;
  const codeSmellCritical = t39.dataset.codeSmellCritical;
  const codeSmellMajor = t40.dataset.codeSmellMajor;
  const codeSmellMinor = t41.dataset.codeSmellMinor;
  const codeSmellInfo = t42.dataset.codeSmellInfo;

  /** Payload aligné sur les clés SonarQube (= colonnes DB historique).
   *  Les variables JS conservent leurs noms locaux mais sont assignée
   *  aux clés SonarQube dans le payload PUT. */
  const data =
  {
    ...(window.peintureData || {}),
    maven_key, 'project_name': project_name, 'analyse_key': analyseKey,
    'version_release': versionRelease, 'version_snapshot': versionSnapshot,
    'version_autre': versionAutre, version, 'date_version': dateVersion,
    'suppress_warning': suppressWarning, 'no_sonar': noSonar, todo,
    'no_pmd':           noPmd,
    'check_style':      checkStyle,
    'java_no_sonar':    javaNoSonar,
    'python_no_sonar':  pythonNoSonar,
    'php_no_sonar':     phpNoSonar,
    'logger_info': loggerInfo, 'logger_warn': loggerWarn, 'logger_error': loggerError,
    'logger_debug': loggerDebug,
    // MODIF 2026-05-15  : breakdown level × framework
    'logger_breakdown': loggerBreakdown,
    // MODIF 2026-07-23  : JSON Actuator (voir lecture dataset ci-dessus)
    'actuator_info': actuatorInfo,
    'ncloc': nombreLigneDeCode, 'lines': nombreLigne,
    'files': nombreFichier, 'classes': nombreClasse, 'functions': nombreFonction,
    coverage, duplicated_lines_density,
    'sqale_debt_ratio': sqaleDebtRatio, 'tests': tests,
    'violations': violations, 'sqale_index': dette,
    'bugs': nombreBug, 'vulnerabilities': nombreVulnerability,
    'code_smells': nombreCodeSmell,
    'repartition_frontend': frontend, 'repartition_backend': backend,
    'repartition_autre': autre, 'repartition_inconnu': inconnu,
    'blocker_violations': nombreViolationsBloquant,
    'critical_violations': nombreViolationsCritique,
    'info_violations': nombreViolationsInfo,
    'major_violations': nombreViolationsMajeur,
    'minor_violations': nombreViolationsMineur,
    'reliability_rating': noteReliability, 'security_rating':  noteSecurity,
    'sqale_rating': noteSqale, 'security_review_rating': noteHotspot,
    'menace_potentielle_to_review_high':  menacePotentielleToReviewHigh,
    'menace_potentielle_to_review_medium': menacePotentielleToReviewMedium,
    'menace_potentielle_to_review_low': menacePotentielleToReviewLow,
    'menace_potentielle_reviewed_high':  menacePotentielleReviewedHigh,
    'menace_potentielle_reviewed_medium': menacePotentielleReviewedMedium,
    'menace_potentielle_reviewed_low': menacePotentielleReviewedLow,
    'menace_potentielle_totale': menacePotentielleTotale,
    'bug_blocker': bugBlocker, 'bug_critical': bugCritical, 'bug_major': bugMajor,
    'bug_minor': bugMinor, 'bug_info': bugInfo,
    'vulnerability_blocker': vulnerabilityBlocker, 'vulnerability_critical':  vulnerabilityCritical,
    'vulnerability_major': vulnerabilityMajor, 'vulnerability_minor': vulnerabilityMinor,
    'vulnerability_info': vulnerabilityInfo,
    'code_smell_blocker': codeSmellBlocker, 'code_smell_critical': codeSmellCritical,
    'code_smell_major': codeSmellMajor,
    'code_smell_minor': codeSmellMinor, 'code_smell_info': codeSmellInfo,
    initial: 0
  };

  const options = {
    url: `${serveur()}/api/secure/enregistrement`,
    type: 'PUT',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  }

  try {
        const t = await $.ajax(options);
        // 📌 Vérification des erreurs
        const errorCodes = [http_400, http_401, http_403, http_404, http_500, http_502, http_504];
        if (errorCodes.includes(t.code)){
          const hasTrace = !!t.trace;
            const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
            showMessage(t.type, t.message, trace);
            return;
          }
        if (t.code === http_200) {
          const message='Enregistrement des informations effectué.';
          log(` - INFO : ${message}`);
          showMessage('success', `${message}`);
          setTimeout(() => { hideMessage(); }, 5000);
          return;
        }

      if (t.code === 23505) {
        const message=`Cette version existe déjà dans l'historique.`;
        showMessage('warning', `${message}`);
        }
  } catch (error) {
      console.error('[Enregistrement] Catch 502/5xx :', error.status, error.statusText, error.responseText?.substring(0, 500));
      const trace = prepareTechnicalDetails(error);
      const message = "Une erreur inattendue s'est produite lors de l'enregistrement des données (Erreur 500).";
      showMessage('critical', message, trace);
      sessionStorage.setItem('ma_moulinette_enregistrement', 'Erreur lors de la sauvegarde des données.');
      log(` - 🔴 Enregistrement des données en échec.`);
      return;
    }
}
