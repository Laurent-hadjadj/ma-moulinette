/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/** Import des dépendances */
import 'foundation-sites/dist/css/foundation.min.css';
import 'motion-ui/dist/motion-ui.css';
import '../../../styles/common/common.css';
import '../../../styles/common/police.css';
import '../../../styles/mon-application/admin-log.css';

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

import 'what-input';
import 'foundation-sites';
import 'motion-ui';

import '../../common/foundation.js';

/** Affiche le détails de l'utilisateur */
import '../../auth/details.js';

/** On importe les paramètres serveur */
import {serveur} from '../../common/properties.js';
import { http_200, content_type } from '../../common/constante.js';

/** On importe le gestionnaire de message */
import { showMessage, hideMessage, prepareTechnicalDetails } from '../../common/messageHelper.js';

// Appliquer les filtres sélectionnés
    $('#bouton-apply-filters').on('click', function () {
        loadLogs(getFilters());
    });

    // Téléchargement de la sélection
    $('#bouton-download-zip').on('click', function () {
        downloadSelection();
    });

    // On sélectionne tous les filtres
    $('#select-all').on('change', function () {
        $('.log-select').prop('checked', this.checked);
    });

    // On gère les cases à coché
    $('#logs-table').on('click', 'tbody tr', function (e) {
        if (e.target.type !== 'checkbox') {
            const cb = $(this).find('.log-select');
            cb.prop('checked', !cb.prop('checked'));
        }
    });

    /**
     * [Description for getFilters]
     *
     * @return json
     *
     * Created at: 15/12/2025 08:37:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    const getFilters = function() {
        const params = {};

        const env = $('#filter-env').val();
        if (env) {
            params.env = env;
        }

        const types = [];
        $('.filter-type:checked').each(function () {
            types.push($(this).val());
        });
        if (types.length) {
            params.types = types;
        }
        return params;
    }

    /**
     * [Description for formatSize]
     *
     * @param mixed bytes
     *
     * @return integer
     *
     * Created at: 15/12/2025 08:37:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    const formatSize = function(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }

    /**
     * [Description for loadLogs]
     *
     * @param mixed params
     *
     * @return json
     *
     * Created at: 15/12/2025 08:38:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    const loadLogs = async function(params = {}) {
        const options = {
                url:  serveur()+`/api/secure/admin/logs/api/list`,
                method: 'GET',
                dataType: 'json',
                data: params,
                contentType: content_type,
                headers: {
                    'X-API-Custom-403': 'true',
                    'X-Internal-Front': 'front-app'
                }
        };

        $('#logs-loading').removeClass('hide');

        try {
            const t = await $.ajax(options);

            if (Number(t.code) !== http_200){
                const hasTrace = !!t.trace;
                const trace = hasTrace ? prepareTechnicalDetails(t.trace) : null;
                const message = t.message || t.error || "Une erreur inconnue est survenue (Erreur 500).";
                showMessage(t.type || 'error', message, trace);
                $('#logs-loading').addClass('hide');
                return;
            }

            const tbody = $('#logs-table tbody');
            tbody.empty();

            if (!t.count) {
                tbody.append(`
                    <tr>
                        <td colspan="6" class="text-center">
                            Aucun log trouvé
                        </td>
                    </tr>
                `);
                return;
            }

            t.logs.forEach(function (log) {
                tbody.append(`
                    <tr>
                        <td class="log-name">${log.name}</td>
                            <td>${log.env ?? '-'}</td>
                            <td>
                                <span class="log-badge log-${log.type}">
                                    ${log.type}
                                </span>
                            </td>
                            <td class="text-right">${formatSize(log.size)}</td>
                            <td>${log.mtime}</td>
                            <td class="text-center">
                                <input type="checkbox"
                                        class="log-select"
                                        value="${log.name}">
                            </td>
                    </tr>
                `);
            });
        } catch (error) {
            $('#logs-loading').addClass('hide');
            // Gestion d'erreurs génériques
            const trace = prepareTechnicalDetails(error);
            const message = `Une erreur inattendue a interrompu l'affichage des journaux (Erreur 500).`
            showMessage('critical', message, trace);
            $('#logs-loading').addClass('hide');
        }
    }

    /** Téléchargement des fichiers sélectionnés sous forme de ZIP */
    /**
     * [Description for downloadSelection]
     *
     * @return void
     *
     * Created at: 15/12/2025 08:40:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    const downloadSelection = function() {
        const files = [];

        $('.log-select:checked').each(function () {
            files.push($(this).val());
        });

        if (!files.length) {
            showMessage('warning', 'Veuillez sélectionner au moins un fichier (Erreur 404).');
            return;
        }

        if (!confirm(`Télécharger ${files.length} fichier(s) ?`)) {
            return;
        }

        // Créer un FormData pour envoyer les fichiers comme un formulaire HTML
        const formData = new FormData();
        // Envoie chaque fichier comme "files[]=app-dev.log"
        files.forEach(file => {
                formData.append('files[]', file);
        });

        // Requête AJAX avec jQuery
        $.post({
                url: serveur() + '/api/secure/admin/logs/download-selection',
                method: 'POST',
                headers: {
                    'X-API-Custom-403': 'true',
                    'X-Internal-Front': 'front-app'
                },
                data: formData,
                processData: false, // Empêche jQuery de transformer les données
                contentType: false, // Laisse le navigateur définir le Content-Type
                xhrFields: {
                        responseType: 'blob' // Pour gérer le téléchargement du fichier
                },
                success: function(data) {
                        // Créer un lien de téléchargement pour le blob reçu
                        const blob = new Blob([data]);
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'logs_selectionnes.zip'; // Nom du fichier téléchargé
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        a.remove();
                },
                error: function(xhr, status, error) {
						// Gérer les erreurs (y compris les erreurs 401/403)
						if (xhr.status === 401 || xhr.status === 403) {
								showMessage('error', 'Accès refusé. Veuillez vous reconnecter.');
						} else {
								showMessage('error', `Erreur: ${error}`);
						}
				}
        });
    }

    // Chargement initial des logs
    loadLogs();
