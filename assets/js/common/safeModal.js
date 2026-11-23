/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) Ma-Moulinette - 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

export const modalSafe = (() => {

    let lastTrigger = null;

    function getTrigger(id) {
        return document.querySelector(`[data-open="${id}"]`);
    }


    /**
     * [Description for normalise]
     * Tolère un identifiant nu ('ma-modale') en plus d'un sélecteur ('#ma-modale').
     * Sans ce garde-fou, un appel sans '#' cible un nom de balise inexistant et
     * la modale ne s'ouvre / ne se ferme jamais, en silence.
     * @param mixed selector
     *
     * @return mixed
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    function normalise(selector) {
        if (typeof selector !== 'string' || selector === '') return selector;
        return /^[#.[]/.test(selector) ? selector : `#${selector}`;
    }

    /**
     * [Description for safeOpen]
     *
     * @param mixed selector
     *
     * @return mixed
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    function safeOpen(selector) {
        const modal = document.querySelector(normalise(selector));
        if (!modal) return;

        lastTrigger = document.activeElement;

        modal.removeAttribute('inert');
        $(modal).foundation('open');

        requestAnimationFrame(() => {
            const closeBtn = modal.querySelector('[data-close], .close-button');
            if (closeBtn) closeBtn.focus();
        });
    }

    /**
     * [Description for safeClose]
     *
     * @param mixed selector
     *
     * @return mixed
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    function safeClose(selector) {
        const modal = document.querySelector(normalise(selector));
        if (!modal) return;

        const trigger = getTrigger(modal.id) || lastTrigger || document.body;

        modal.setAttribute('inert', '');

        document.activeElement?.blur();

        if (trigger && typeof trigger.focus === 'function') {
            trigger.focus();
        }

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                $(modal).foundation('close');

                $(modal).one('closed.zf.reveal', () => {
                    modal.removeAttribute('inert');
                });
            });
        });
    }

    return { open: safeOpen, close: safeClose };

})();
