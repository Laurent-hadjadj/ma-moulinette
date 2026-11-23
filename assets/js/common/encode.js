/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

/**
 * [Description for encode]
 * Encodage ROT13
 *
 * @param string str
 *
 * @return string
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
export const encode = function(str) {
  return str.replace(/[A-Za-z]/g, (char) => {
    const code = char.codePointAt(0);
    const base = char >= 'a' ? 'a'.codePointAt(0) : 'A'.codePointAt(0);
    return String.fromCodePoint(base + ((code - base + 13) % 26));
  });
}
