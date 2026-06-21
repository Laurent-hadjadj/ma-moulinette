/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-06-10 : point d'accès  DataTables pour la page Statistiques — Projets.
 *  Recherche globale + tri par colonne + pagination.
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
import '../../../styles/mon-application/statistique-projet.css';

import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import '../../auth/details.js';

import DataTable from 'datatables.net-zf'; //NOSONAR

const DT_LANG_FR = {
    emptyTable:   'Aucune donnée disponible',
    processing:   'Traitement...',
    search:       'Rechercher :',
    lengthMenu:   'Afficher _MENU_ lignes',
    info:         'Lignes _START_ à _END_ sur _TOTAL_',
    infoEmpty:    'Aucune ligne disponible',
    infoFiltered: '(filtré depuis _MAX_ lignes)',
    zeroRecords:  'Aucun résultat',
    paginate: {
        first:    '«',
        previous: '‹',
        next:     '›',
        last:     '»',
    },
};

document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('table-projets');
    if (!table) { return; }

    new DataTable(table, {
        paging:    true,
        info:      true,
        searching: true,
        ordering:  true,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        /* Tri initial : nom projet ASC (colonne 0) */
        order: [[0, 'asc']],
        columnDefs: [
            /* Colonnes numériques : tri numérique correct */
            { type: 'num', targets: [12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22] },
        ],
        language: DT_LANG_FR,
    });
});
