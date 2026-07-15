/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-07-15 : intégration DataTables (extension Zurb Foundation) —
 *  recherche globale + tri par colonne + pagination (15 lignes par défaut).
 *
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import 'datatables.net-zf/css/dataTables.foundation.min.css';

import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/mes-projets.css';

/** Intégration de jquery */
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
    const table = document.getElementById('table-mes-projets');
    if (!table) { return; }

    new DataTable(table, {
        paging:     true,
        info:       true,
        searching:  true,
        ordering:   true,
        pageLength: 15,
        lengthMenu: [5, 10, 15, 20, 25],
        /* Tri initial : nom de l'application ASC (colonne 0) */
        order: [[0, 'asc']],
        columnDefs: [
            /* Colonnes numériques (Lignes, Violations, Composants, Qualité du code,
               Loggers, Tests) : tri numérique correct plutôt qu'alphabétique. */
            { type: 'num', targets: [2, 3, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22] },
        ],
        language: DT_LANG_FR,
    });
});
