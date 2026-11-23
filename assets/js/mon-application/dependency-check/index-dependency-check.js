/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *
 *  MODIF 2026-05-08 : entry JS pour les pages
 *  /dependency-check/* (index, projet, dashboard).
 *  Charge uniquement les styles + Foundation + jQuery — aucune logique
 *  metier spécifique pour l'instant (pages 100% server-side).
 *
 *  ---
 *  Vous pouvez obtenir une copie de la licence a l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/dependency-check.css';

/** Integration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
