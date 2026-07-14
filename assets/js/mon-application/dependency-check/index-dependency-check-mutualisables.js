/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-05-16 : entry JS pour la page Mutualisables.
 *  Remplace le tri server-side + pagination Knp par DataTables client-side.
 *  Tri initial : Gain mutu. DESC (colonne 6). Colonnes Type et Utilisée par
 *  non-triables. Langue française.
 *
 *  ---
 *  Vous pouvez obtenir une copie de la licence a l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import 'datatables.net-zf/css/dataTables.foundation.min.css';

import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/dependency-check.css';

import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';

/** Gestion des modales zurb foundation compatible WCAG  */
import { modalSafe } from '../../common/safeModal.js';

import DataTable from 'datatables.net-zf'; //NOSONAR

const DT_LANG_FR = {
    emptyTable: "Aucune donnée disponible",
    processing:     'Traitement...',
    search:         'Rechercher :',
    lengthMenu:     'Afficher _MENU_ lignes',
    info:           'Lignes _START_ à _END_ sur _TOTAL_',
    infoEmpty:      'Aucune ligne disponible',
    infoFiltered:   '(filtré depuis _MAX_ lignes)',
    zeroRecords:    'Aucun résultat',
    paginate: {
        first:    '«',
        previous: '‹',
        next:     '›',
        last:     '»',
    },
};

document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('table-mutualisables');
    if (!table) { return; }

    new DataTable(table, {
        paging: true,
        info: true,
        searching: true,
        ordering: true,
        pageLength: 10,
        lengthMenu: [10, 15, 20, 25],
        /* Tri initial : Gain mutu. (index 6) DESC */
        order: [[6, 'desc']],
        columnDefs: [
            /* Type (2) et Utilisée par (7) : pas de tri */
            { orderable: false, targets: [2, 7] },
            /* Colonnes numériques : forcer type num pour tri correct */
            { type: 'num', targets: [3, 4, 5, 6] },
        ],
        language: DT_LANG_FR,
    });
});

/* Ouverture de la modale "Méthode de calcul du JH" */
$('.js-ouvrir-methodology-jh').on('click', () => {
  modalSafe.open('#dc-jh-methodology-modal');
});

/* Fermeture de la modale "Méthode de calcul du JH" */
$('#bouton-fermer-methodology-jh').on('click', () => {
  modalSafe.close('#dc-jh-methodology-modal');
});
