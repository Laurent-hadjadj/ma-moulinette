/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
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

/** Gestion des modales zurb foundation compatible WCAG  */
import { modalSafe } from '../../common/safeModal.js';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';

import { showMessage } from '../../common/messageHelper.js';
import { displayNumber, displayPercent, displayValue } from '../../common/displayHelper.js';

/** On importe les constantes */
import {dateOptions, content_type, couleur, note, espace, rien, http_400, http_406, listeOwasp2017, listeOwasp2021, listeOwasp2025,
  un, deux, trois, quatre, cinq, six, sept, huit, neuf, dix, onze, cent} from '../../common/constante.js';

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
/* MODIF 28/10/2026 : BUG CORRIGÉ — les bornes (`> 0.71 && < 0.81`, etc.)
 * laissaient des trous aux valeurs limites exactes (0.31, 0.51, 0.71, 0.81) :
 * un taux tombant pile dessus ne matchait aucune condition, laissant c/n
 * `undefined` → `class="badge undefined"`, aucune règle CSS ne correspond,
 * badge bleu par défaut de Foundation. */
const calculNoteHotspot = function(taux) {
  /** C = couleur, n = note */
  if (taux > 0.79) {
    return [couleur[un], note[un]];
  }
  if (taux > 0.71) {
    return [couleur[deux], note[deux]];
  }
  if (taux > 0.51) {
    return [couleur[trois], note[trois]];
  }
  if (taux > 0.31) {
    return [couleur[4], note[quatre]];
  }
  return [couleur[5], note[cinq]];
};

/* MODIF 27/10/2026 : seuils propres (if/elseif, pas de recouvrement), mêmes
 * bornes que calculNoteHotspot() ci-dessus / OwaspController::
 * computeHotspotNoteFromTaux() côté export PDF. Utilisée par les 2 tuiles
 * compteur (Vulnérabilités OWASP et Hotspot, badge .stat-note/.note-vertX) —
 * le tableau de synthèse A1-A10 garde calculNoteHotspot()/l'ancien badge,
 * inchangé. MODIF 28/10/2026 : renommée (générique) — sert désormais aussi à
 * la note Vulnérabilités OWASP, pas seulement Hotspot. */
const colorByRatingNote = {
  A: 'note-vert1',
  B: 'note-vert2',
  C: 'note-jaune',
  D: 'note-orange',
  E: 'note-rouge',
};

const noteHotspotFromTaux = function (taux) {
  if (taux > 0.79) { return 'A'; }
  if (taux > 0.71) { return 'B'; }
  if (taux > 0.51) { return 'C'; }
  if (taux > 0.31) { return 'D'; }
  return 'E';
};

/**
 * Injecte le badge de note A-E de la tuile compteur Hotspot (taux de
 * validation), même convention que renderAccessibilityRating()/colorByRating dans assets/js/mon-application/projet/peinture.js.
 *
 * @param {string} selector
 * @param {number|null} taux
 */
const renderOwaspHotspotRating = function (selector, taux) {
  if (taux === null || taux === undefined || Number.isNaN(taux)) {
    $(selector).html(`<span class="stat-note-kpi stat-note-width note-gris">${displayValue(null)}</span>`);
    return;
  }
  const rating = noteHotspotFromTaux(taux);
  const cssClass = colorByRatingNote[rating] ?? '';
  $(`${selector}-percent`).html(`${displayPercent(taux * cent)}`)
  $(`${selector}`).html(`<span class="stat-note-kpi stat-note-width ${cssClass}">${rating}</span>`);
};

/* MODIF 28/10/2026 : note A-E de la tuile compteur Vulnérabilités OWASP
 * (manquait jusqu'ici — seule la tuile Hotspot avait une note). Même ordre de
 * sévérité (pire sévérité présente → pire note) que l'ancien
 * injectionOwaspInfo() par catégorie, mais réécrite ici en fonction pure,
 * exhaustive sur des seuils >=1 (pas de recouvrement ni de cas "aucune
 * condition ne matche" — l'original, en cascade de if sans else, laissait un
 * badge non défini si le total valait exactement 1 avec une seule sévérité
 * concernée, cf. historique du fichier) : appliquée au total toutes
 * catégories confondues (r.bloquant/r.critique/r.majeur/r.mineur, déjà dans
 * la réponse de /api/secure/peinture/owasp/liste), pas par catégorie.
 *
 * @param {number} bloquant
 * @param {number} critique
 * @param {number} majeur
 * @param {number} mineur
 *
 * @return {string} 'A'..'E'
 */
const noteOwaspFromCounts = function (bloquant, critique, majeur, mineur) {
  const b = Number(bloquant) || 0;
  const c = Number(critique) || 0;
  const j = Number(majeur) || 0;
  const m = Number(mineur) || 0;
  if (b + c + j + m === 0) { return 'A'; }
  if (b > 0) { return 'E'; }
  if (c > 0) { return 'D'; }
  if (j > 0) { return 'C'; }
  return 'B';
};

/**
 * Injecte le badge de note A-E de la tuile compteur Vulnérabilités OWASP.
 *
 * @param {string} selector
 * @param {number} bloquant
 * @param {number} critique
 * @param {number} majeur
 * @param {number} mineur
 */
const renderOwaspVulnRating = function (selector, bloquant, critique, majeur, mineur) {
  const rating = noteOwaspFromCounts(bloquant, critique, majeur, mineur);
  const cssClass = rating ? (colorByRatingNote[rating] ?? '') : 'note-gris';
  $(selector).html(`<span class="stat-note-kpi stat-note-width ${cssClass}">${displayValue(rating)}</span>`);
};

/**
 * Injecte le compteur + la part (%) d'un module dans la répartition par
 * module des vulnérabilités OWASP (remplace injectionModule(), qui portait sur les hotspots — cf. historique du fichier).
 *
 * @param {string} selector
 * @param {number|null} count
 * @param {number} total
 */
const injectionModuleOwasp = function (selector, count, total) {
  const pct = total > 0 ? (Number(count) || 0) / total : 0;
  $(selector).html(`${displayNumber(count)}`);
  $(`${selector}-percent`).html(`${displayPercent(pct * cent)}`);
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
  const i =`<span class="stat-note stat-note-value">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(menace)}</span>
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
  /** réinitialise les valeurs.
   *  MODIF 2026-07-18 : '' -> '--' (une cellule vide se confondait avec un
   *  vrai zéro), + ajout de la boucle #a1..#a10 (tableau de synthèse) qui
   *  manquait ici : sans données pour le référentiel sélectionné (406), les
   *  compteurs de failles du référentiel précédent restaient affichés. */
  const placeholder = '--';

  /** version et la date du projet dans SonarQube */
  $('#js-application-version').html(placeholder);
  $('#js-owasp-version').html(placeholder);

  /** les vulnérabilités */
  $('#js-owasp-total').html(placeholder);
  $('#js-nombre-faille-bloquant').html(placeholder);
  $('#js-nombre-faille-critique').html(placeholder);
  $('#js-nombre-faille-majeur').html(placeholder);
  $('#js-nombre-faille-mineur').html(placeholder);
  $('#js-owasp-rating').html(placeholder);

  /** nombre de hotspot au status REVIEWED */
  $('#js-hotspot-reviewed').html(placeholder);
  /** nombre de hotspot au status TO_REVIEW */
  $('#js-hotspot-to-review').html(placeholder);

/**  hotspot OWASP  */
  $('#js-hotspot-total').html(placeholder);
  $('#js-nombre-hotspot-high').html(placeholder);
  $('#js-nombre-hotspot-medium').html(placeholder);
  $('#js-nombre-hotspot-low').html(placeholder);
  $('#js-hotspot-rating').html(placeholder);

  /* Tableau de synthèse (failles) + Hotspot */
  for (let id=1; id<11; id++) {
    $(`#a${id}`).html(placeholder);
    $(`#h${id}`).html(placeholder);
  }

  /** tableau de détail des vulnérabilités OWASP */
  $('#js-tbody-owasp-details').html('');
  $('#js-owasp-details-show').text(placeholder);
  $('#js-owasp-details-total').text(placeholder);

  /** répartition front/back */
  $('#js-frontend').html(placeholder);
  $('#js-backend').html(placeholder);
  $('#js-autre').html(placeholder);

  /** on supprime le référentiel par défaut */
  sessionStorage.setItem('ma_moulinette_referential_owasp', '');
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
/* MODIF 26/10/2026 : cache de la répartition par sévérité des violations par catégorie (a1..a10), reconstruit à chaque remplissageOwaspInfo() (donc à chaque changement de référentiel) — voir son utilisation dans remplissageDetailsHotspotOwasp() plus bas. */
const owaspSeverityByCategory = {};

const remplissageOwaspInfo = async function(maven_key, referential_owasp) {

  /** si la clé maven n'est pas défini alors on sort */
  if (maven_key === undefined || referential_owasp === undefined) {
    return;
  }

  const data = { maven_key, referential_owasp };

  const options = {
    url: `${serveur()}/api/secure/peinture/owasp/liste`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const r = await $.ajax(options);
  if (r.code === http_400) {
    showMessage('error', `La requête n'est pas conforme (Erreur 400).`);
    videLeTableau();
    return;
  }
  if (r.code===http_406) {
    showMessage('warning', `Le projet n'a pas été trouvé !`);
    videLeTableau();
    return;
  }

  /* MODIF 26/10/2026 : met en cache la répartition par sévérité des
   * VIOLATIONS de chaque catégorie (a1Blocker..a10Minor, déjà présents * dans cette même réponse, jusqu'ici jamais lus) — réutilisé par
   * remplissageDetailsHotspotOwasp() pour les afficher dans la modale
   * "Détail", qui ne montrait jusqu'ici que la sévérité des hotspots. */
  for (let i = 1; i <= 10; i++) {
    owaspSeverityByCategory[`a${i}`] = {
      bloquant: r[`a${i}Blocker`],
      critique: r[`a${i}Critical`],
      majeur: r[`a${i}Major`],
      mineur: r[`a${i}Minor`],
    };
  }

  /** On affiche la version et la date du projet dans SonarQube */
  const date_version = new Intl.DateTimeFormat('default', dateOptions).format(new Date(r.date_version));
  $('#js-application-version').html(`V${r.version}, (${date_version})`);

  /** On affiche la version du référentiel.
   *  MODIF 27/10/2026 : vocabulaire aligné sur la classification locale
   *  (OwaspRuleClassifierService) — 'core' (règle SonarQube elle-même),
   *  'findsecbugs' (règle du plugin tiers FindSecBugs, jamais indexée par la facette officielle) ou 'mixed' (les deux contribuent) — remplace l'ancien vocabulaire 'facet'/'tag' (colonne owasp.source, supprimée). */
  let sourceLabel = 'OWASP (SonarQube)';
  if (r.source === 'findsecbugs') { sourceLabel = 'OWASP (FindSecBugs)'; }
  if (r.source === 'mixed') { sourceLabel = 'OWASP (mixte SonarQube + FindSecBugs)'; }
  $('#owasp-version').html(`Référentiel OWASP Actuel : <span class="lead color-noir">${r.referential_owasp}</span> — <span class="lead color-noir">${sourceLabel}</span>`);

  /* MODIF 27/10/2026 : met à jour le callout « nombre de règles » pour le référentiel actif (donnée statique posée en data-* par Twig, cf. OwaspController::index() -> owasp_rule_count_2017/2021). */
  const ruleCountData = $('#js-owasp-rule-count-callout').data(`ruleCount${r.referential_owasp}`);
  if (ruleCountData !== undefined) {
    $('#js-owasp-rule-count').text(ruleCountData);
  }

  /* On génère l'histogramme avec les menaces OWASP */
  let dataBar=[];
  for (let i=1; i<11; i++){
    dataBar.push(r['a'+i]);
  }
  dessineMoiUneBarre(r.referential_owasp, dataBar);

  /** On ajoute les valeurs pour les vulnérabilités */
  $('#js-owasp-total').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.total));
  $('#js-nombre-faille-bloquant').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.bloquant));
  $('#js-nombre-faille-critique').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.critique));
  $('#js-nombre-faille-majeur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.majeur));
  $('#js-nombre-faille-mineur').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.mineur));

  /* MODIF 28/10/2026 : note A-E de la tuile compteur Vulnérabilités OWASP. */
  renderOwaspVulnRating('#js-owasp-rating', r.bloquant, r.critique, r.majeur, r.mineur);

  let c=[], n=[];

  /* Détails A1 */
  if (Number(r.a1Blocker + r.a1Critical + r.a1Major + r.a1Minor) === 0){
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a1Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a1Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a1Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a1Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(un, r.a1, c, n);

  /** Détails A2 */
  if (Number(r.a2Blocker + r.a2Critical + r.a2Major + r.a2Minor) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a2Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a2Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a2Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a2Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(deux, r.a2, c, n);

  /* Détails A3 */
  if (Number(r.a3Blocker + r.a3Critical + r.a3Major + r.a3Minor) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a3Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a3Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a3Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a3Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(trois, r.a3, c, n);

  /* Détails A4 */
  if (Number(r.a4Blocker + r.a1Critical + r.a1Major + r.a1Minor) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a4Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a4Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a4Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a4Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(quatre, r.a4, c, n);

  /* Détails A5 */
  if (Number(r.a5Blocker + r.a5Critical + r.a5Major + r.a5Minor) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a5Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a5Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a5Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a5Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  injectionOwaspInfo(cinq, r.a5, c, n);

  /* Détails A6 */
  if (Number(r.a6Blocker + r.a6Critical + r.a6Major + r.a6Minor) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a6Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a6Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a6Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a6Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(six, r.a6, c, n);

  /* Détails A7 */
  if (Number(r.a7Blocker + r.a7Critical + r.a7Major + r.a7Minor) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a7Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a7Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a7Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a7Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(sept, r.a7, c, n);

  /* Détails A8 */
  if (Number(r.a8Blocker + r.a8Critical + r.a8Major + r.a8Minor) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a8Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a8Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a8Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a8Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(huit, r.a8, c, n);

  /* Détails A9 */
  if (Number(r.a9Blocker + r.a9Critical + r.a9Major + r.a9Minor) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a9Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a9Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a9Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a9Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(neuf, r.a9, c, n);

  /* Détails A10 */
  if (Number(r.a10Blocker + r.a10Critical + r.a10Major + r.a10Minor) === 0) {
    c = couleur[un];
    n = note[un];
  }
  if (Number(r.a10Minor) > 1) {
    c = couleur[deux];
    n = note[deux];
  }
  if (Number(r.a10Major) > 1) {
    c = couleur[trois];
    n = note[trois];
  }
  if (Number(r.a10Critical) > 1) {
    c = couleur[quatre];
    n = note[quatre];
  }
  if (Number(r.a10Blocker) > 1) {
    c = couleur[cinq];
    n = note[cinq];
  }
  /** on injecte : ID, Menace, Badge Note */
  injectionOwaspInfo(dix, r.a10, c, n);

  /* MODIF 27/10/2026 : répartition par module des VULNÉRABILITÉS OWASP
   * (remplace l'ancienne répartition basée sur les hotspots, cf.
   * remplissageHotspotDetails() et templates/owasp/index.html.twig). */
  const module_total = (Number(r.module_frontend) || 0) + (Number(r.module_backend) || 0) + (Number(r.module_autre) || 0);
  injectionModuleOwasp('#js-frontend', r.module_frontend, module_total);
  injectionModuleOwasp('#js-backend', r.module_backend, module_total);
  injectionModuleOwasp('#js-autre', r.module_autre, module_total);
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
    url: `${serveur()}/api/secure/peinture/owasp/hotspot/info`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
      headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const r = await $.ajax(options);
  if (r.code===http_400) {
    const message = `La requête n'est pas conforme (Erreur 400).`;
    showMessage('error', message);
    videLeTableau();
    return;
  }

  /** On compte le nombre de hotspot au status REVIEWED */
  $('#js-hotspot-reviewed').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.reviewed));
  /** On compte le nombre de hotspot au status TO_REVIEW */
  $('#js-hotspot-to-review').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.toReview));
  const hotspotToReview = r.toReview;

  /** On affiche le nombre de hotspot OWASP et par la répartition */
  $('#js-hotspot-total').html(r.total);
  const nombreHotspot=r.total;
  $('#js-nombre-hotspot-high').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.high));
  $('#js-nombre-hotspot-medium').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.medium));
  $('#js-nombre-hotspot-low').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.low));

  /* MODIF 27/10/2026 : badge recoloré — .stat-note/.note-vertX (même
   * convention que les tuiles Accessibilité/RCI/Empreinte Écologique),
   * remplace l'ancien .badge/note-a..e (incohérence visuelle signalée).
   * Le tableau de synthèse A1-A10 (remplissageHotspotListe() plus bas)
   * garde calculNoteHotspot()/l'ancien badge, inchangé. */
  let leTaux = nombreHotspot !== 0 ? 1 - (Number(hotspotToReview) / nombreHotspot) : 1;
  renderOwaspHotspotRating('#js-hotspot-rating', leTaux);
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
const injectionHotspotListe = function(id, formatage, menace, leTaux, badge, laNote) {
  const i = `<span class="stat-note stat-note-value">${new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(menace)}</span>
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
      url: `${serveur()}/api/secure/peinture/owasp/hotspot/liste`,
      type: 'POST',
      dataType: 'json',
      data: JSON.stringify(data),
      contentType: content_type,
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app'
      },
    };

    const r = await $.ajax(options);
    if (r.code===http_400) {
      const message = `La requête n'est pas conforme (Erreur 400).`;
      showMessage('error', message);
      videLeTableau();
      return;
    }

    /* MODIF 28/10/2026 : BUG CORRIGÉ — repli par défaut à 'a' (littéral), alors que la constante couleur (constante.js) utilise le préfixe 'note-' ('note-a'..'note-e'). class="badge a" ne correspond à aucune règle CSS : badge bleu par défaut de Foundation, visible dès qu'un projet n'a aucun hotspot OWASP (nombreHotspot === 0 ci-dessous, branche else). */
    let leTaux=1, laNote=['note-a','A'], formatage;
    const nombreHotspot = Number(r.menaceA1+r.menaceA2+r.menaceA3+r.menaceA4+
                            r.menaceA5+r.menaceA6+r.menaceA7+r.menaceA8+
                            r.menaceA9+r.menaceA10);
    formatage = espace;

    if ( nombreHotspot !== 0 ){
      /* calcul A1 */
      leTaux = 1 - (Number(r.menaceA1) / nombreHotspot);
      laNote = calculNoteHotspot(leTaux);
      if ( (leTaux*cent)>dix && (leTaux*cent) < cent) {
        formatage = espace+espace+espace;
      } else {
        formatage = rien;
        }
      injectionHotspotListe(un, formatage, r.menaceA1, leTaux, laNote[0], laNote[1]);

      /* calcul A2*/
      leTaux = 1 - (Number(r.menaceA2) / nombreHotspot);
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
                  <td><a class="color-bleu" "href="${url}/coding_rules?open=${rule}&q=${rule}">${rule}</a></td>
                  <td class="${color}">${severity}</td>
                  <td class="component">${file}</td>
                  <td>${line}</td>
                  <td>${message}</td>
                  <td>${status}</td>
                </tr>`;
  $('#js-tbody-hotspot-details').append(ligne);
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

  const data = { maven_key, referential_owasp };
  const options = {
    url: `${serveur()}/api/secure/peinture/owasp/hotspot/details`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const r =await  $.ajax(options)
  if (r.code === http_400) {
    const message = `La requête n'est pas conforme (Erreur 400).`;
    showMessage('error', message);
    videLeTableau();
    return;
  }

    /* MODIF 27/10/2026 : la répartition par module (#frontend/#backend/#autre) ne porte plus sur les hotspots — elle est désormais alimentée par remplissageOwaspInfo() à partir des vulnérabilités OWASP (cf. injectionModuleOwasp(), templates/owasp/index.html.twig). Cette fonction se limite au tableau détaillé des hotspots. */
    let number = 0, monNumber, ligne, c;
    const serveurURL = $('#js-serveur').data('serveur');

    if (r.details.menaces === undefined || r['details']['menaces'].length == 0) {
        /** On ajoute une ligne dans le tableau */
        ligne = `<tr class="text-center">
                  <td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>Pas de faille.</td>
                </tr>`;
        $('#js-tbody-hotspot-details').html(ligne);
      } else {
      /** On efface le tableau et on ajoute les lignes */
      $('#js-tbody-hotspot-details').html('');
      for (const detail of r.details.menaces){
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

        /** Les colonnes de la table hotspot_details : rule_key (clé technique
         *  pour le deep-link SonarQube), rule_name (libellé), file_path,
         *  line. Avant : detail.rule / detail.file → undefined. */
        injectionHotspotDetails(monNumber, serveurURL, c, detail.rule_key, detail.severity, detail.file_path, detail.line, detail.message, detail.status);
      }
  }
};

/**
 * [Description for injectionOwaspDetailsRow]
 * Injecte une ligne du tableau de détail des vulnérabilités OWASP.
 *
 * @param {string|number} number
 * @param {string} url
 * @param {string} rule
 * @param {string} severity
 * @param {string} categories
 * @param {string} component
 * @param {number|null} line
 * @param {string} message
 *
 * Created at: 28/10/2026 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const injectionOwaspDetailsRow = function (number, url, rule, severity, categories, component, line, message) {
  const ligne = `<tr>
                  <td class="stat-note">${number}</td>
                  <td><a href="${url}/coding_rules?open=${rule}&q=${rule}">${rule}</a></td>
                  <td>${severity}</td>
                  <td>${categories}</td>
                  <td class="component">${component}</td>
                  <td>${line ?? '—'}</td>
                  <td>${message}</td>
                </tr>`;
  $('#js-tbody-owasp-details').append(ligne);
};

/**
 * [Description for remplissageOwaspDetails]
 * Affiche le tableau de détail des vulnérabilités OWASP (10 premières,
 * triées par criticité, cf. OwaspDetailsRepository::selectOwaspDetailsTop10BySeverity())
 * + le nombre total réel sous le tableau.
 *
 * MODIF 28/10/2026 : nouveau — le seul tableau de détail de la page n'avait jusqu'ici jamais couvert que les Security Hotspots (remplissageHotspotDetails() ci-dessus), jamais les vulnérabilités OWASP elles-mêmes.
 *
 * @param string maven_key
 * @param integer referential_owasp
 *
 * Created at: 28/10/2026 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
const remplissageOwaspDetails = async function (maven_key, referential_owasp) {
  if (maven_key === undefined || referential_owasp === undefined) {
    return;
  }

  const data = { maven_key, referential_owasp };
  const options = {
    url: `${serveur()}/api/secure/peinture/owasp/details`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const r = await $.ajax(options);
  if (r.code === http_400) {
    const message = `La requête n'est pas conforme (Erreur 400).`;
    showMessage('error', message);
    videLeTableau();
    return;
  }

  const serveurURL = $('#js-serveur').data('serveur');

  if (r.details === undefined || r.details.length === 0) {
    const ligne = `<tr class="text-center">
                    <td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>N.C</td><td>Pas de vulnérabilité.</td>
                  </tr>`;
    $('#js-tbody-owasp-details').html(ligne);
  } else {
    $('#js-tbody-owasp-details').html('');
    let number = 0;
    for (const detail of r.details) {
      number++;
      const monNumber = number < dix ? '0' + number : number;
      injectionOwaspDetailsRow(monNumber, serveurURL, detail.rule_key, detail.severity, detail.categories, detail.file_path, detail.line, detail.message);
    }
  }

  const nombreFormat = new Intl.NumberFormat('fr-FR', { style: 'decimal' });
  $('#owasp-details-show').text(nombreFormat.format(r.details ? r.details.length : 0));
  $('#owasp-details-total').text(nombreFormat.format(r.total ?? 0));
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

  /* MODIF 26/10/2026 : BUG CORRIGÉ — referential_owasp n'était jamais envoyé
   * au backend : countHotspotOwaspMenaceByStatus()
   * sommait silencieusement 2017 ET 2021, donnant le même résultat quel que
   * soit l'onglet sélectionné. */
  const referentialOwasp = sessionStorage.getItem('ma_moulinette_referential_owasp');
  const data = { maven_key, menace, referential_owasp: referentialOwasp };
  const options = {
    url: `${serveur()}/api/secure/peinture/owasp/hotspot/severity`,
    type: 'POST',
    dataType: 'json',
    data: JSON.stringify(data),
    contentType: content_type,
    headers: {
      'X-API-Custom-403': 'true',
      'X-Internal-Front': 'front-app'
    },
  };

  const r = await $.ajax(options);
  if (r.code===http_400) {
    const message=`La requête n'est pas conforme (Erreur 400) !`;
    showMessage('error', message);
    /* MODIF 28/10/2026 : sans ça, le spinner posé par showDetailsModalSpinner() restait affiché indéfiniment dans la modale déjà ouverte en cas d'erreur. */
    $('.details-titre').text('Erreur lors du chargement du détail.');
    return;
  }

    /** on affiche le titre en fonction du référentiel */
    const x = referentialOwasp;

    if (x === undefined || x == null || x == '') { return; }

    if (x === "2017") {
      $('.details-titre').html(listeOwasp2017[titre]);
    }
    if (x === "2021") {
      $('.details-titre').html(listeOwasp2021[titre]);
    }
    if (x === "2025") {
      $('.details-titre').html(listeOwasp2025[titre]);
    }

    $('#detail-haut').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.high));
    $('#detail-moyen').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.medium));
    $('#detail-faible').html(new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(r.low));

    /* MODIF 26/10/2026 : répartition par sévérité des VIOLATIONS de cette
     * catégorie, déjà en cache depuis le dernier remplissageOwaspInfo(). */
    const violationSeverity = owaspSeverityByCategory[menace] ?? { bloquant: 0, critique: 0, majeur: 0, mineur: 0 };
    const fmt = (n) => new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(n ?? 0);
    $('#detail-violation-bloquant').html(fmt(violationSeverity.bloquant));
    $('#detail-violation-critique').html(fmt(violationSeverity.critique));
    $('#detail-violation-majeur').html(fmt(violationSeverity.majeur));
    $('#detail-violation-mineur').html(fmt(violationSeverity.mineur));
};

/**
 * [Description for updateCategoryLabels]
 * Met à jour les libellés de catégorie (colonne "show-for-medium") du tableau
 * de synthèse selon le référentiel sélectionné.
 *
 * MODIF 2026-07-18 : ces libellés étaient câblés en dur dans le HTML sur la
 * nomenclature 2017, quel que soit le référentiel réellement choisi (ex.
 * "A1 - Attaques d'injection" restait affiché même en 2021, où A1 signifie
 * "Contrôle d'accès défaillant"). Les listes `listeOwasp2017/2021/2025`
 * existent déjà (utilisées pour le titre de la modale de détail) — on les
 * réutilise ici pour le tableau de synthèse.
 *
 * @param string referential_owasp
 *
 * Created at: 18/07/2026 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const updateCategoryLabels = function (referential_owasp) {
  const listes = { '2017': listeOwasp2017, '2021': listeOwasp2021, '2025': listeOwasp2025 };
  const liste = listes[referential_owasp];
  if (!liste) {
    return;
  }
  for (let i = 1; i <= 10; i++) {
    $(`#label-a${i}`).text(liste[i]);
  }
};

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
const selectAnalyseVersion = function (key, referential_owasp) {
  /** Libellés de catégorie du tableau de synthèse pour ce référentiel */
  updateCategoryLabels(referential_owasp);
  /** Affiche les vulnérabilités par criticité */
  remplissageOwaspInfo(key, referential_owasp);
  /** Affiche les hotspot par criticité */
  remplissageHotspotInfo(key, referential_owasp);
  /** On rempli le tableau avec les données des menaces owasp */
  remplissageHotspotListe(key, referential_owasp);
  /** On rempli le tableau de détail des vulnérabilités OWASP (10 premières, triées par criticité) */
  remplissageOwaspDetails(key, referential_owasp);
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
  const $version_2017 = $('#owasp-2017');
  const $version_2021 = $('#owasp-2021');
  const $version_2025 = $('#owasp-2025');

  /* MODIF 2026-07-18 : les branches 2017/2025 avaient une double négation
   * (`!X.hasClass('display-off') === false`, équivalente à `X.hasClass(...)`
   * seul) qui inversait la logique voulue — elle ne masquait un bloc que
   * s'il était déjà masqué (no-op), jamais quand il était visible. Résultat :
   * sélectionner 2017 ou 2025 ne masquait jamais les deux autres blocs, qui
   * restaient affichés en même temps. Seule la branche 2021 était correcte
   * (sans le `!` en trop) — les 3 branches suivent maintenant ce même
   * schéma : masquer si PAS déjà masqué. */
  if (version === "2017") {
    if ($version_2021.hasClass('display-off') === false) { $version_2021.addClass('display-off')}
    if ($version_2025.hasClass('display-off') === false) { $version_2025.addClass('display-off')}
    $version_2017.removeClass('display-off');
  }
  if (version === "2021") {
    if ($version_2017.hasClass('display-off') === false) { $version_2017.addClass('display-off')}
    if ($version_2025.hasClass('display-off') === false) { $version_2025.addClass('display-off')}
    $version_2021.removeClass('display-off');
  }
  if (version === "2025") {
    if ($version_2017.hasClass('display-off') === false) { $version_2017.addClass('display-off')}
    if ($version_2021.hasClass('display-off') === false) { $version_2021.addClass('display-off')}
    $version_2025.removeClass('display-off');
  }
}

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
/* MODIF 2026-07-18 : conserve l'instance du graphique pour la détruire avant
* d'en recréer une — sans ça, chaque clic sur un référentiel (2017/2021/2025)
* tentait un new Chart() sur le même canvas déjà utilisé, provoquant
* "Uncaught (in promise) Error: Canvas is already in use." */
let owaspBarChartInstance = null;

const dessineMoiUneBarre = function (referential, data){

  // Données statiques pour les pourcentages de chaque type de vulnérabilité
  const labels = ['A1','A2','A3','A4','A5','A6','A7','A8','A9','A10'];

  if (owaspBarChartInstance) {
    owaspBarChartInstance.destroy();
  }

  const ctx = document.getElementById('owasp-bar-chart').getContext('2d');
  owaspBarChartInstance = new Chart(ctx, {
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
                  // MODIF 26/10/2026 : context.parsed est l'objet {x, y} du point
                  // (format Chart.js pour un bar/line chart), pas directement la
                  // valeur — concaténer l'objet entier affichait "[object Object]%"
                  // au lieu du nombre.
                  label: function(context) {
                      let label = context.label || '';
                      if (label) {
                          label += ': ';
                      }
                      if (context.parsed.y !== null) {
                          label += context.parsed.y + '%';
                      }
                      return label;
                  }
              }
          }
      }
    }
  });

}

/*************** Main du programme **************/

/** On récupère la clé du projet.
 *  MODIF 2026-07-18 : `maven_key` peut être `null` (sessionStorage vidé,
 *  page rouverte directement, nouvel onglet...) — un `.split(':')` sur
 *  `null` plantait ici, en tête de script, AVANT l'attachement de tous les
 *  gestionnaires de clic plus bas (référentiels, export PDF, modales) : la
 *  page semblait chargée mais restait totalement inerte, sans message
 *  visible pour l'utilisateur (seule une erreur JS non catchable dans le
 *  flux applicatif apparaissait en console). Le repli `['', '']` reste
 *  nécessaire pour éviter ce crash.
 *  MODIF 2026-07-22 : le `showMessage('warning', ...)` qui accompagnait ce
 *  repli a été retiré — il faisait doublon avec le message flash serveur
 *  (`OwaspController::index()`, cas token absent), affiché sur la même
 *  page pour la même raison. */
const maven_key = sessionStorage.getItem('ma_moulinette_projet');
const projet = maven_key ? maven_key.split(':') : ['', ''];

/** On met à jour la page */
$('#js-application').html(projet[1] || '');

/** On appel les fonctions de remplissage pour la version OWASP.
 *  Fix 2026-05-03 : ligne `setItem(..., 2021)` parasite supprimée — elle
 *  écrasait la valeur 2017 juste après l'avoir posée → l'export PDF lisait
 *  systématiquement 2021 dans sessionStorage quel que soit le bouton cliqué. */
const $owasp_2017 = $('#bouton-choisir-owasp-2017');
const $owasp_2021 = $('#bouton-choisir-owasp-2021');
const $owasp_2025 = $('#bouton-choisir-owasp-2025');

$owasp_2017.on('click', ()=>{
  sessionStorage.setItem('ma_moulinette_referential_owasp', 2017);
  $owasp_2017.addClass('active');
  $owasp_2021.removeClass('active');
  $owasp_2025.removeClass('active');
  if ($owasp_2017.hasClass('disable') === false){
    selectAnalyseVersion(maven_key, '2017');
  }
});

$owasp_2021.on('click', ()=>{
  sessionStorage.setItem('ma_moulinette_referential_owasp', 2021);
  if ($owasp_2021.hasClass('disable') === false){
    $owasp_2017.removeClass('active');
    $owasp_2025.removeClass('active');
    $owasp_2021.addClass('active');
    selectAnalyseVersion(maven_key, '2021');
  }
});

$owasp_2025.on('click', ()=>{
  sessionStorage.setItem('ma_moulinette_referential_owasp', 2025);
  if ($owasp_2025.hasClass('disable') === false){
    $owasp_2017.removeClass('active');
    $owasp_2021.removeClass('active');
    $owasp_2025.addClass('active');
    selectAnalyseVersion(maven_key, '2025');
  }
});

$('select[name="owasp"]').on('change', function () {
  const version = $('select[name="owasp"]').val();
  selectReferentialOwasp(version);
});

/* MODIF 27/10/2026 : chargement automatique du référentiel par défaut, déjà sélectionné côté Twig (referentiel_affiche_defaut -> <select> + bouton marqué "active", cf. templates/owasp/index.html.twig) — avant, aucune donnée ne s'affichait tant qu'on n'avait pas cliqué sur un bouton de référentiel. Les boutons restent utilisables pour en changer ensuite. */
const referentielParDefaut = $('select[name="owasp"]').val();
if (referentielParDefaut) {
  sessionStorage.setItem('ma_moulinette_referential_owasp', referentielParDefaut);
  selectAnalyseVersion(maven_key, referentielParDefaut);
}

/** Export PDF : ouvre /owasp/rapport/pdf?maven_key=...&referential_owasp=...
 *  dans un nouvel onglet (rendu inline). Le serveur agrège les données via
 *  les repos Owasp / HotspotOwasp / HotspotDetails. */
$('#bouton-ouvrir-rapport-owasp').on('click', () => {
  const mavenKey = sessionStorage.getItem('ma_moulinette_projet');
  const referential = sessionStorage.getItem('ma_moulinette_referential_owasp') || '2025' ;
  if (!mavenKey) {
    showMessage('warning', "Aucun projet sélectionné — impossible d'exporter le rapport.", null);
    return;
  }
  const params = new URLSearchParams({ maven_key: mavenKey, referential_owasp: referential });
  window.open(`${serveur()}/owasp/rapport/pdf?${params.toString()}`, '_blank');
});

/* MODIF 28/10/2026 : BUG CORRIGÉ — plusieurs clics rapprochés sur une icône 🔎 (le temps que l'appel AJAX réponde) empilaient autant de gestionnaires async en parallèle ; chacun appelait modalSafe.open('#details') à la fin de son propre await, ce qui rouvrait/refermait la modale en rafale au fur et à mesure que les promesses se résolvaient. Garde anti-double-clic (detailsModalLoading) : les clics reçus pendant un chargement en cours sont ignorés. La modale s'ouvre désormais immédiatement avec un spinner ;
 * remplissageDetailsHotspotOwasp() écrase ensuite le spinner par le vrai titre une fois la réponse arrivée. */
let detailsModalLoading = false;

const showDetailsModalSpinner = function () {
  $('.details-titre').html('<span class="avatar-loading-spinner" role="status" aria-label="Chargement du détail de la catégorie"></span> Chargement…');
  const placeholder = '--';
  $('#detail-violation-bloquant, #detail-violation-critique, #detail-violation-majeur, #detail-violation-mineur').html(placeholder);
  $('#detail-haut, #detail-moyen, #detail-faible').html(placeholder);
};

$('.js-details').on('click', async function () {
  if (detailsModalLoading) {
    return;
  }
  detailsModalLoading = true;

  const id = $(this).attr('id').split('-');
  const k_key=sessionStorage.getItem('ma_moulinette_projet');

  showDetailsModalSpinner();
  modalSafe.open('#details');

  try {
    if (id[1] === 'a1') {
      await remplissageDetailsHotspotOwasp(k_key, 'a1', un);
    }
    if (id[1] === 'a2') {
      await remplissageDetailsHotspotOwasp(k_key, 'a2', deux);
    }
    if (id[1] === 'a3') {
      await remplissageDetailsHotspotOwasp(k_key, 'a3', trois);
    }
    if (id[1] === 'a4') {
      await remplissageDetailsHotspotOwasp(k_key, 'a4', quatre);
    }
    if (id[1] === 'a5') {
      await remplissageDetailsHotspotOwasp(k_key, 'a5', cinq);
    }
    if (id[1] === 'a6') {
      await remplissageDetailsHotspotOwasp(k_key, 'a6', six);
    }
    if (id[1] === 'a7') {
      await remplissageDetailsHotspotOwasp(k_key, 'a7', sept);
    }
    if (id[1] === 'a8') {
      await remplissageDetailsHotspotOwasp(k_key, 'a8',huit);
    }
    if (id[1] === 'a9') {
      await remplissageDetailsHotspotOwasp(k_key, 'a9', neuf);
    }
    if (id[1] === 'a10') {
      await remplissageDetailsHotspotOwasp(k_key, 'a10', dix);
    }
  } finally {
    detailsModalLoading = false;
  }
});

/** On ferme la modale "Owasp et points chauds" */
$('#bouton-fermer-details-owasp').on('click', function () {
  modalSafe.close('#details');
});

/** On ouvre / ferme la modale de comparaison des référentiels OWASP */
$('#bouton-ouvrir-comparaison-owasp').on('click', function () {
  modalSafe.open('#compare-owasp-modal');
});

$('#bouton-fermer-comparaison-owasp').on('click', function () {
  modalSafe.close('#compare-owasp-modal');
});
