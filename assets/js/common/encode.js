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

/**
 * [Description for encode]
 * Encodage ROT13
 *
 * @param string str
 *
 * @return string
 *
 * Created at: 23/02/2024 14:47:06 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
export const encode = function(str) {
  return str.replace(/[A-Za-z]/g, (char) => {
    const code = char.codePointAt(0);
    const base = char >= 'a' ? 'a'.codePointAt(0) : 'A'.codePointAt(0);
    return String.fromCodePoint(base + ((code - base + 13) % 26));
  });
}
