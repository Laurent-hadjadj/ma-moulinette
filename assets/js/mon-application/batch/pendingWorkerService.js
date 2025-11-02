/**
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/**
 * pendingWorkerService
 *
 * Service principal du front qui pilote le worker de traitement pending.
 *
 * Améliorations :
 *  - Token aléatoire pour sécuriser la communication
 *  - Auto-reconnexion si le worker est silencieux
 *  - Monitoring local (sessionStorage)
 *  - Pause / reprise / stop complets
 */
const pendingWorkerService = {
  worker: null,
  token: null,
  lastUpdate: Date.now(),
  infoBulleSelector: '#info-bulle',
  infoTipsSelector: '#info-bulle-tips',

  start({ debug = false } = {}) {
    if (this.worker) return; // déjà démarré

    this.worker = new Worker('/workers/pendingWorker.js', { name: 'pending-worker' });
    this.token = crypto.randomUUID();

    if (debug) {
      console.log('[pendingWorkerService] ▶️ Worker créé');
      this.initDebugPanel();
      this.logDebug('▶️ Worker démarré');
    }

    /* === Réception des messages du worker === */
    this.worker.onmessage = (event) => {
      const { type, data, message } = event.data;
      this.lastUpdate = Date.now();

      switch (type) {
        case 'data':
          if (debug) {
            this.updateDebugPanel({ status: '✅ OK', data });
            console.log('[pendingWorkerService] 📊 Données', data);
          }
          this.updateInfoBulle(data);
          break;
        case 'status':
          if (debug) {
              this.updateDebugPanel({ status: message });
              console.info('[pendingWorkerService] ℹ️', message);
          }
          break;
        case 'error':
          this.updateDebugPanel({ status: '❌ Erreur', error: message });
          console.error('[pendingWorkerService] ⚠️', message);
          sessionStorage.setItem('ma_moulinette_pendingWorkerService', `[❌] ${message}`);
          break;
        default:
          if (debug) console.warn('[pendingWorkerService] 🔸 Message inconnu', event.data);
      }
    };

     /* === Démarrage du worker === */
    this.worker.postMessage({ command: 'debug', value: debug, token: this.token });

    this.worker.postMessage({ command: 'start', token: this.token });
    if (debug) console.log('[pendingWorkerService] ✅ Service démarré');
    sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] ✅ Service démarré.');

    /* === Pause/reprise selon visibilité === */
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) this.pause({ debug });
      else this.resume({ debug });
    });

    /* === Surveillance du worker (auto-reconnexion) === */
    this.monitorWorker({ debug });
  },

  pause({ debug = false } = {}) {
    if (!this.worker) return;

    this.worker.postMessage({ command: 'stop', token: this.token });
    this.worker.terminate();
    this.worker = null;

    if (debug) {
      console.error('[pendingWorkerService] ⏸️ Pause (onglet inactif');
      this.updateDebugPanel({ status: '⏸️ Pause (onglet inactif)' });
    }
    sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] ⏸️ Pause (onglet inactif.)');
  },

  resume({ debug = false } = {}) {
    if (!this.worker) {
      this.start({ debug });
      if (debug) {
        console.error('[pendingWorkerService] ▶️ Reprise (onglet actif).');
        this.updateDebugPanel({ status: '▶️ Reprise (onglet actif)' });
      }
    }
  },

  stop({ debug = false } = {}) {
    if (!this.worker) return;
    this.worker.postMessage({ command: 'stop', token: this.token });
    this.worker.terminate();
    this.worker = null;

    if (debug) {
      console.error('[pendingWorkerService] 🛑 Service arrêté manuellement.');
      this.updateDebugPanel({ status: '🛑 Service arrêté manuellement' });
    }
    sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] 🛑 Service arrêté manuellement');
  },

  /* === Mise à jour de la bulle === */
  updateInfoBulle({debug }, t) {
    const $infoBulle = $(this.infoBulleSelector);
    const $tips = $(this.infoTipsSelector);

    if (!t || typeof t.pending === 'undefined') {
      sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] 🛑 Données invalides', t);
      if (debug) console.warn('[pendingWorkerService] Données invalides :', t);
      return;
    }

    $infoBulle.addClass('loading');
    $infoBulle.removeClass('bulle-info-vide bulle-info-start bulle-info-end bulle-info-error');

    if (t.pending > 0) {
      $infoBulle.addClass('bulle-info-start').html(t.pending);
      $tips.html('Nombre de projet planifié.');
      } else if (t.pending === 0 && t.in_progress > 0) {
        $infoBulle.addClass('bulle-info-end').html(t.in_progress);
        $tips.html('Un projet est en cours de traitement.');
      } else {
        $infoBulle.addClass('bulle-info-end').html('0');
        $tips.html('Aucun projet planifié.');
      }

      setTimeout(() => $infoBulle.removeClass('loading'), 300);
    },

    /* === Auto-reconnexion si worker silencieux (>60s) === */
    monitorWorker({ debug = false } = {}) {
    setInterval(() => {
      if (this.worker && Date.now() - this.lastUpdate > 60_000) {
        console.warn('[pendingWorkerService] ⚠️ Worker silencieux → redémarrage');
        this.stop({ debug });
        this.start({ debug });
      }
    }, 30_000);
  },
  
  initDebugPanel() {
    if ($('#pending-debug-monitor').length) return; // déjà présent

    // créer le panneau
    $('body').append($('#pending-debug-monitor'));
    $('#debug-close').on('click', () => $('#pending-debug-monitor').fadeOut(200));

    $('#pending-debug-monitor').fadeIn(200);
    this.logDebug('🔧 Debug Monitor initialisé');
  },

  logDebug(message) {
    const now = new Date().toLocaleTimeString();
    const $log = $('#debug-log');
    $log.prepend(`[${now}] ${message}\n`);
    const logs = $log.text().split('\n');
    if (logs.length > 50) $log.text(logs.slice(0, 50).join('\n'));
  },

  updateDebugPanel({ status, data, error } = {}) {
    if (!$('#pending-debug-monitor').is(':visible')) return;

    if (status) $('#debug-status').text(status);
    if (data) {
      $('#debug-last-check').text(new Date().toLocaleTimeString());
      $('#debug-pending').text(data.pending ?? '–');
      $('#debug-in-progress').text(data.in_progress ?? '–');
    }
    if (error) this.logDebug('⚠️ ' + error);
  }
}
