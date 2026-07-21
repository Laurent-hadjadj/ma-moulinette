/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
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
import '../../../styles/mon-application/admin-log.css';
import '../../../styles/mon-application/user-role-log.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';
import '../../auth/details.js';

import DataTable from 'datatables.net-zf'; //NOSONAR

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';
import { http_200, content_type } from '../../common/constante.js';

/** On importe le gestionnaire de message */
import { showMessage, hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

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

let dataTable = null;
const csrfToken = document.body.getAttribute('data-csrf-delete');

// Appliquer les filtres sélectionnés
$('#bouton-apply-filters').on('click', function () {
    loadJournal(getFilters());
});

// Archiver / Rapport PDF / Supprimer la sélection
$('#bouton-archiver').on('click', function () { downloadSelection('archive', 'journal_roles.csv'); });
$('#bouton-rapport-pdf').on('click', function () { downloadSelection('pdf', 'journal_roles.pdf'); });
$('#bouton-supprimer').on('click', function () { deleteSelection(); });

/**
 * On sélectionne toutes les lignes.
 * MODIF 2026-07-21 : deux problèmes cumulés empêchaient ce bouton de
 * fonctionner avec DataTables :
 *  1. Le "select all" étant dans le <thead>, DataTables (rendu 'foundation')
 *     reconstruit l'en-tête à partir du HTML capturé à l'initialisation —
 *     la case #select-all d'origine (sur laquelle le .on('change', ...)
 *     avait été posé au chargement du script) peut donc être remplacée par
 *     un nouveau nœud sans écouteur. Fix : liaison déléguée depuis `document`
 *     (réévaluée à chaque événement, insensible au remplacement du nœud).
 *  2. DataTables (pagination client-side) ne garde dans le DOM que les
 *     lignes de la page affichée — un simple $('.role-log-select') ne
 *     cochait donc que la page courante. Fix : on passe par l'API DataTables
 *     (rows().nodes()) qui couvre toutes les pages.
 */
$(document).on('change', '#select-all', function () {
    const checked = this.checked;
    const rows = dataTable ? $(dataTable.rows().nodes()) : $('#role-log-table tbody tr');
    rows.find('.role-log-select').prop('checked', checked);
});

// On gère les cases à cocher (clic n'importe où sur la ligne)
$('#role-log-table').on('click', 'tbody tr', function (e) {
    if (e.target.type !== 'checkbox') {
        const cb = $(this).find('.role-log-select');
        cb.prop('checked', !cb.prop('checked'));
    }
});

/**
 * [Description for getFilters]
 *
 * @return json
 *
 * Created at: 20/07/2026 00:00:00 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const getFilters = function() {
    const params = {};

    const courriel = $('#filter-courriel').val();
    if (courriel) { params.courriel = courriel; }

    const start = $('#filter-start').val();
    if (start) { params.start = start; }

    const end = $('#filter-end').val();
    if (end) { params.end = end; }

    return params;
}

/**
 * [Description for selectedIds]
 *
 * @return array
 *
 * Created at: 20/07/2026 00:00:00 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const selectedIds = function() {
    const ids = [];
    /* MODIF 2026-07-21 : idem select-all, on lit toutes les pages via l'API
     * DataTables plutôt que le seul DOM (limité à la page affichée). */
    const rows = dataTable ? $(dataTable.rows().nodes()) : $('#role-log-table tbody tr');
    rows.find('.role-log-select:checked').each(function () {
        ids.push($(this).val());
    });
    return ids;
}

/**
 * Bloque/débloque les 3 boutons d'action pendant un appel réseau, avec spinner.
 * MODIF 2026-07-21 : archiver/rapport PDF/supprimer n'avaient aucun retour
 * visuel avant la réponse serveur (délai de quelques secondes selon le volume).
 *
 * @param mixed busy
 *
 * @return void
 *
 * Created at: 21/07/2026 00:00:00 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const setActionsBusy = function(busy) {
    $('#bouton-archiver, #bouton-rapport-pdf, #bouton-supprimer').toggleClass('disabled-bouton', busy);
    $('#role-log-spinner').toggleClass('sp-volume', busy);
}

/**
 * [Description for loadJournal]
 *
 * @param mixed params
 *
 * @return json
 *
 * Created at: 20/07/2026 00:00:00 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const loadJournal = async function(params = {}) {
    const options = {
        url: serveur() + `/api/secure/admin/journal-roles/list`,
        method: 'GET',
        dataType: 'json',
        data: params,
        contentType: content_type,
        headers: {
            'X-API-Custom-403': 'true',
            'X-Internal-Front': 'front-app'
        }
    };

    $('#role-log-loading').removeClass('hide');

    if (dataTable) {
        dataTable.destroy();
        dataTable = null;
        $('#role-log-table tbody').empty();
    }

    try {
        const t = await $.ajax(options);

        if (Number(t.code) !== http_200) {
            const hasTrace = !!t.trace;
            const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
            const message = t.message || t.error || "Une erreur inconnue est survenue (Erreur 500).";
            showMessage(t.type || 'error', message, trace);
            $('#role-log-loading').addClass('hide');
            return;
        }

        const tbody = $('#role-log-table tbody');
        tbody.empty();

        $('#role-log-loading').addClass('hide');

        if (!t.count) {
            tbody.append(`
                <tr>
                    <td colspan="8" class="text-center">
                        Aucune ligne trouvée
                    </td>
                </tr>
            `);
            return;
        }

        t.lignes.forEach(function (ligne) {
            const alertes = (ligne.alerts || []).map(a => `<span class="role-log-alert-badge">${a}</span>`).join(' ');

            tbody.append(`
                <tr>
                    <td class="role-log-date">${ligne.date}</td>
                    <td>${ligne.userEmail}</td>
                    <td>${ligne.editorEmail}</td>
                    <td>${(ligne.oldRoles || []).join(', ')}</td>
                    <td>${(ligne.newRoles || []).join(', ')}</td>
                    <td class="text-center">
                        <span class="${ligne.oldActive ? 'role-log-active-on' : 'role-log-active-off'}" title="${ligne.oldActive ? 'Oui' : 'Non'}">${ligne.oldActive ? 'O' : 'N'}</span>
                        →
                        <span class="${ligne.newActive ? 'role-log-active-on' : 'role-log-active-off'}" title="${ligne.newActive ? 'Oui' : 'Non'}">${ligne.newActive ? 'O' : 'N'}</span>
                    </td>
                    <td>${alertes}</td>
                    <td class="text-center">
                        <input type="checkbox" class="role-log-select" value="${ligne.id}">
                    </td>
                </tr>
            `);
        });

        dataTable = new DataTable('#role-log-table', {
            paging: true,
            info: true,
            searching: true,
            ordering: true,
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: [7] },
            ],
            language: DT_LANG_FR,
        });
    } catch (error) {
        $('#role-log-loading').addClass('hide');
        const trace = prepareTechnicalDetails(error);
        const message = `Une erreur inattendue a interrompu l'affichage du journal (Erreur 500).`;
        showMessage('critical', message, trace);
    }
}

/**
 * Télécharge la sélection au format CSV (archive) ou PDF (rapport).
 *
 * @param mixed action
 * @param mixed filename
 *
 * @return void
 *
 * Created at: 20/07/2026 00:00:00 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const downloadSelection = function(action, filename) {
    const ids = selectedIds();

    if (!ids.length) {
        showMessage('warning', 'Veuillez sélectionner au moins une ligne (Erreur 404).');
        return;
    }

    const formData = new FormData();
    ids.forEach(id => formData.append('ids[]', id));

    setActionsBusy(true);

    $.ajax({
        url: serveur() + `/api/secure/admin/journal-roles/${action}`,
        method: 'POST',
        headers: {
            'X-API-Custom-403': 'true',
            'X-Internal-Front': 'front-app'
        },
        data: formData,
        processData: false,
        contentType: false,
        xhrFields: { responseType: 'blob' },
        complete: function() { setActionsBusy(false); },
        success: function(data, textStatus, jqXHR) {
            const contentType = jqXHR.getResponseHeader('Content-Type') || '';

            // Le serveur répond toujours en HTTP 200 (code métier dans le corps JSON),
            // y compris en cas d'erreur — sans ce test, une erreur 500 survenue après
            // l'appel (ex. base de données) serait téléchargée comme un faux fichier
            // CSV/PDF ne contenant que le JSON d'erreur, sans que l'utilisateur soit prévenu.
            if (contentType.includes('application/json')) {
                const reader = new FileReader();
                reader.onload = function() {
                    let t = null;
                    try { t = JSON.parse(reader.result); } catch (e) { t = null; }
                    const message = t?.message || "Une erreur inattendue est survenue lors du téléchargement (Erreur 500).";
                    showMessage(t?.type || 'error', message);
                };
                reader.readAsText(data);
                return;
            }

            const blob = new Blob([data]);
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
        },
        error: function(xhr, status, error) {
            if (xhr.status === 401 || xhr.status === 403) {
                showMessage('error', 'Accès refusé. Veuillez vous reconnecter.');
            } else {
                showMessage('error', `Erreur: ${error}`);
            }
        }
    });
}

/**
 * Supprime les lignes sélectionnées après confirmation.
 *
 * @return void
 *
 * Created at: 20/07/2026 00:00:00 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const deleteSelection = async function() {
    const ids = selectedIds();

    if (!ids.length) {
        showMessage('warning', 'Veuillez sélectionner au moins une ligne (Erreur 404).');
        return;
    }

    if (!confirm(`Supprimer définitivement ${ids.length} ligne(s) du journal ? Pensez à archiver au préalable si nécessaire.`)) {
        return;
    }

    const formData = new FormData();
    ids.forEach(id => formData.append('ids[]', id));
    formData.append('_token', csrfToken);

    setActionsBusy(true);

    try {
        const t = await $.ajax({
            url: serveur() + '/api/secure/admin/journal-roles/delete',
            method: 'POST',
            headers: {
                'X-API-Custom-403': 'true',
                'X-Internal-Front': 'front-app'
            },
            data: formData,
            processData: false,
            contentType: false,
        });

        if (Number(t.code) !== http_200) {
            showMessage(t.type || 'error', t.message || "Une erreur inconnue est survenue (Erreur 500).");
            return;
        }

        showMessage('success', t.message);
        loadJournal(getFilters());
    } catch (error) {
        const trace = prepareTechnicalDetails(error);
        showMessage('critical', `Une erreur inattendue a interrompu la suppression (Erreur 500).`, trace);
    } finally {
        setActionsBusy(false);
    }
}

// Chargement initial du journal
loadJournal();
