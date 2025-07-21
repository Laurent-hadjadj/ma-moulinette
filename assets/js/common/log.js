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

import { dateOptions } from './constante.js';

/**
 * [Description for log]
 * Affiche la log.
 *
 * @param string txt
 *
 * @return void
 *
 * Created at: 19/12/2022, 22:10:19 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 */
export const log = function(txt) {
  const textarea = document.getElementById('log');
  textarea.value += `${new Intl.DateTimeFormat('default',
  dateOptions).format(new Date())} ${txt}\n`;
};
