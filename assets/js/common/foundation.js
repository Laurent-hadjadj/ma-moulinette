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

/************ Démarrage de Zurb Foundation ************************************/
import $ from 'jquery';

/** Démarrage de Zurb foundation */
$(document).foundation();

/** Initialisation après chargement complet du DOM (pour les éléments ajoutés dynamiquement) */
$(function () {
  $(document).foundation();
});
