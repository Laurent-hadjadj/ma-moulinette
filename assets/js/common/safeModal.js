/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) Lilmod & Lelamed - 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

export const ModalSafe = (() => {

    let lastTrigger = null;

    function getTrigger(id) {
        return document.querySelector(`[data-open="${id}"]`);
    }

    function safeOpen(selector) {
        const modal = document.querySelector(selector);
        if (!modal) return;

        lastTrigger = document.activeElement;

        modal.removeAttribute('inert');
        $(modal).foundation('open');

        requestAnimationFrame(() => {
            const closeBtn = modal.querySelector('[data-close], .close-button');
            if (closeBtn) closeBtn.focus();
        });
    }

    function safeClose(selector) {
        const modal = document.querySelector(selector);
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
