/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *
 *  MODIF 2026-05-16 : entry JS pour la page Comparer.
 *  DataTables sur la table CVE communes (tri sévérité DESC par défaut).
 *  Le tableau de synthèse side-by-side reste statique (peu de lignes).
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

import DataTable from 'datatables.net-zf'; //NOSONAR

const DT_LANG_FR = {
    search:       'Rechercher :',
    lengthMenu:   'Afficher _MENU_ lignes',
    info:         'Lignes _START_ à _END_ sur _TOTAL_',
    infoEmpty:    'Aucune ligne disponible',
    infoFiltered: '(filtré depuis _MAX_ lignes)',
    zeroRecords:  'Aucun résultat',
    emptyTable:   'Aucune CVE commune',
    paginate: {
        first: '«', previous: '‹', next: '›', last: '»',
    },
};

document.addEventListener('DOMContentLoaded', () => {
    const cveTable = document.getElementById('table-dc-comparer-cve');
    if (!cveTable) { return; }

    new DataTable(cveTable, {
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        /* Tri initial : Sévérité (index 1) DESC puis CVSS (index 2) DESC */
        order: [[1, 'desc'], [2, 'desc']],
        columnDefs: [
            { type: 'num', targets: [2, 3] },
        ],
        language: DT_LANG_FR,
    });
});
