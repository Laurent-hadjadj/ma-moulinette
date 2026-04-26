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

import { cent } from './constante.js';

/**
 * Marqueur d'absence de donnée affiché à la place de null/undefined.
 * Convention ma-moulinette : une métrique SonarQube absente s'affiche '-'
 * pour distinguer "pas de mesure" de "mesure à 0".
 */
const NO_DATA = '-';

const isNullish = (v) => v === null || v === undefined || (typeof v === 'number' && Number.isNaN(v));

/**
 * Affiche un nombre en décimal fr-FR (séparateur de milliers espace insécable).
 * Retourne '-' si la valeur est null/undefined/NaN.
 *
 * @param {number|string|null|undefined} v
 * @returns {string}
 */
export const displayNumber = (v) => {
  if (isNullish(v)) return NO_DATA;
  const n = typeof v === 'number' ? v : parseFloat(v);
  if (Number.isNaN(n)) return NO_DATA;
  return new Intl.NumberFormat('fr-FR', { style: 'decimal' }).format(n);
};

/**
 * Affiche une valeur 0-100 en pourcentage fr-FR (ex: 75.5 -> "75,5 %").
 * Retourne '-' si la valeur est null/undefined/NaN.
 *
 * @param {number|string|null|undefined} v
 * @returns {string}
 */
export const displayPercent = (v) => {
  if (isNullish(v)) return NO_DATA;
  const n = typeof v === 'number' ? v : parseFloat(v);
  if (Number.isNaN(n)) return NO_DATA;
  return new Intl.NumberFormat('fr-FR', { style: 'percent' }).format(n / cent);
};

/**
 * Affiche une valeur brute (string/number) ou '-' si null/undefined.
 * Utile pour les ratings 'A'-'E', les statuts, etc.
 *
 * @param {*} v
 * @returns {string}
 */
export const displayValue = (v) => isNullish(v) ? NO_DATA : String(v);
