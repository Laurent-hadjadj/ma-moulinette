/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

// Fonction pour échapper les caractères spéciaux HTML
const escapeHtml = function(text) {
    return $('<div>').text(text).html();
}

/**
 * [Description for isHtmlEscaped]
 *
 * @param mixed text
 *
 * @return string
 *
 * Created at: 22/06/2025 12:35:47 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const isHtmlEscaped = function(text) {
    return /&lt;|&gt;|&amp;|&quot;|&#39;/.test(text);
}

/**
 * [Description for extractSemanticalErrorMessage]
 *
 * @param mixed rawJsonString
 *
 * @return string
 *
 * Created at: 09/06/2025 13:21:22 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
export const extractSemanticalErrorMessage = function(rawData) {
    try {
        const responseText = String(rawData);
        const regex = /<!--\s*(.*?)\s*-->/gs;
        const matches = [...responseText.matchAll(regex)];

        if (matches.length > 0) {
            // Priorité aux commentaires spécifiques
            for (const match of matches) {
                const content = match[1].trim();
                if (
                    content.startsWith('[Semantical Error]') ||
                    content.startsWith('Attempted to call')
                ) {
                    return content.length > 500 ? content.slice(0, 500) + '...' : content;
                }
            }
            // Sinon, premier commentaire
            const first = matches[0][1].trim();
            return first.length > 500 ? first.slice(0, 500) + '...' : first;
        }

        // Fallback : tronque le texte brut
        return responseText.slice(0, 500) + (responseText.length > 500 ? '...' : '');
    } catch (error) {
        sessionStorage.setItem('error', error.message);
        return "Erreur lors de l'analyse de la réponse serveur.";
    }
}

/**
 * [Description for formatErrorDetails]
 *
 * @param mixed details
 *
 * @return string
 *
 * Created at: 22/06/2025 12:31:33 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const formatErrorDetails = function(details) {
    const safeDetails = isHtmlEscaped(details) ? details : escapeHtml(details);
    return `
    <div class="margin-top-1">
        <button id="toggle-error-details" class="message-bouton-details small">
        + Afficher les détails techniques
        </button>
    </div>
    <div id="error-details" class="message-show-details">
        <details class="flex-details">
        <summary>Détails techniques</summary>
        <pre>${safeDetails}</pre>
        </details>
    </div>`;
}

/**
  * [Description for prepareTechnicalDetails]
  *
  * @param mixed traceCandidate
  *
  * @return string
  *
  * Created at: 22/06/2025 13:22:21 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
  */
export function prepareTechnicalDetails(traceCandidate) {
        // Si traceCandidate est null ou undefined, retourne une chaîne vide
        if (traceCandidate == null) return '';

        // Si traceCandidate est une chaîne, retourne-la telle quelle
        if (typeof traceCandidate === 'string') {
                return traceCandidate;
        }

        // Si traceCandidate est un objet
        if (typeof traceCandidate === 'object') {
                // Si l'objet a une propriété message, retourne sa représentation sous forme de chaîne
                if (traceCandidate.message) {
                        return traceCandidate.toString();
                }

                let data = traceCandidate.responseJSON;

                // Si responseJSON n'existe pas mais responseText existe, essaie de parser responseText en JSON
                if (!data && traceCandidate.responseText) {
                        try {
                                data = JSON.parse(traceCandidate.responseText);
                        } catch {
                                // Si le parsing échoue, retourne le texte brut
                                return traceCandidate.responseText;
                        }
                }

                // Si data existe, construit une chaîne de sortie
                if (data) {
                        let out = data.detail || data.title || traceCandidate.message || 'Erreur inconnue.';
                        if (Array.isArray(data.trace)) {
                                out += '\n\n' + data.trace.map(t => {
                                        if (t.file && t.line) return `${t.file}:${t.line}`;
                                        return JSON.stringify(t);
                                }).join('\n');
                        }
                        return out;
                }

                // Si data n'existe pas, retourne la représentation JSON de traceCandidate
                return JSON.stringify(traceCandidate, null, 2);
        }

        // Si traceCandidate n'est ni une chaîne ni un objet, retourne sa représentation JSON
        return JSON.stringify(traceCandidate, null, 2);
}

/**
 * [Description for showMessage]
 *  Affiche les messages JS
 *
 * @param string type
 * @param string message
 * @param string technicalDetails
 *
 * @return void
 *
 * Created at: 08/06/2025 22:42:19 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
export const showMessage = function(type, message, technicalDetails = null) {
    const messageElement = $('#message-box');
    const textElement = $('#message-text');
    const buttonElement = $('#message-button-close');

    // Réinitialise les classes d'alerte et le style inline
    messageElement.removeClass('alert primary secondary success warning hide');
    buttonElement.removeClass('alert primary secondary success warning');
    messageElement.css('display', '');

    // Icône par type
    const icons = {
        success: '✅',
        alert: '❌',
        warning: '⚠️',
        info: 'ℹ️',
        primary: '📌',
        secondary: '📄',
        critical: '🔴',
        debug: '🛠️',
        delete: '🗑️',
        notAuthorize: '🚫'
    };

    const icon = icons[type] || '🔔';

    // Applique la classe de style
    messageElement.addClass(type);
    buttonElement.addClass(type);

    // Message principal
    const role = (type === 'alert') ? 'alert' : 'status';
    $('#message-box').attr('role', role);
    let html = `<span class="message-icon" aria-hidden="true">${icon}</span> ${message}`;

    let cleanedDetails = null;
    if (technicalDetails) {
        cleanedDetails = extractSemanticalErrorMessage(technicalDetails);
        if (cleanedDetails) {
            html += formatErrorDetails(cleanedDetails);
        }
    }

    // Injection HTML
    textElement.html(html);
    messageElement.removeClass('hide');

    // Comportement du bouton toggle
    if (technicalDetails) {
        $('#toggle-error-details').on('click', function () {
            const details = $('#error-details');
            const isVisible = details.is(':visible');
            // Anime et affiche/masque
            details.slideToggle(200);
            // Met à jour le texte du bouton
            $(this).text(isVisible ? '+ Afficher les détails techniques' : '− Masquer les détails techniques');
        });
    }
};

/**
 * [Description for hideMessage]
 *
 * @return void
 *
 * Created at: 08/06/2025 19:25:07 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
export const hideMessage = function() {
    const messageElement = $('#message-box');
    messageElement.addClass('hide');
    //'messageElement.css('display', 'none');
}
