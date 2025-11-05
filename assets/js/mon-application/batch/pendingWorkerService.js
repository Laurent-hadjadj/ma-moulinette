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

/** Intégration de jquery */
import $ from 'jquery';
window.$ = $;

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
 *  - Gestion d'une bulle pour in_progress et une pour le workers
 */
export const pendingWorkerService = {
  worker: null,
  token: null,
  monitorId: null,
  channel: null,
  lastUpdate: Date.now(),
  monitorPeriod: 30_000,
  silentThreshold: 60_000,
  infoBulleWip: '#info-bulle-in-progress',
  infoBulleWorkers: '#info-bulle-workers',

  start({ debug = false } = {}) {
    // évite le multi-onglet
    this.instanceId = this.instanceId || crypto.randomUUID();
    this.channel = new BroadcastChannel('pending-monitor');

    let leaderPresent = false;
    let pongTimer = null;

    this.channel.onmessage = (e) => {
      const msg = e.data || {};

      // si un leader répond
      if (msg.type === 'pong' && msg.from !== this.instanceId) {
        leaderPresent = true;
        if (pongTimer) clearTimeout(pongTimer);
      }

      // si on est leader et qu’un autre onglet fait un ping → on répond
      if (msg.type === 'ping' && this.worker) {
        this.channel.postMessage({ type: 'pong', from: this.instanceId });
      }
    };

    // ping et attente courte
    this.channel.postMessage({ type: 'ping', from: this.instanceId });

    // attends 250 ms avant de décider si on devient leader
    pongTimer = setTimeout(() => {
      if (leaderPresent) {
        if (debug) console.warn('[pendingWorkerService] 🔕 Leader détecté → pas de worker ici');
        return; // stop ici, pas de worker dans cet onglet
      }

      // === On devient leader ===
      if (this.worker) return;
      this.lastUpdate = Date.now();
      this.worker = new Worker('/workers/pendingWorker.js', { name: 'pending-worker' });
      this.token = crypto.randomUUID();

      if (debug) {
        console.log('[pendingWorkerService] ▶️ Worker créé');
        this.initDebugPanel(true); // active affichage
        this.logDebug('▶️ Worker démarré');
      } else {
        this.initDebugPanel(false); // garde le panneau caché
      }

      /* === Réception des messages du worker === */
      this.worker.onmessage = (event) => {
        this.lastUpdate = Date.now();

        const payload = event.data || {};
        if (payload.command === 'debug') return;

        let { type, data, message } = payload;
        if (!type && payload.pending !== undefined) { type='data'; data=payload; }

        if (debug) console.log('[pendingWorkerService] 🧩 Message brut reçu', payload);

        switch (type) {
          case 'data':
            if (debug) {
              this.updateDebugPanel({ status: '✅ OK', data });
              console.log('[pendingWorkerService] 📊 Données', data);
            }
            this.updateInfoBulle(data, { debug });
            break;
          case 'status':
            if (debug) {
                this.updateDebugPanel({ status: message });
                console.info('[pendingWorkerService] ℹ️', message);
            }
            if (typeof message === 'string' && message.includes('Tentative')) {
              $(this.infoBulleWorkers).removeClass('bulle-ok bulle-error').addClass('bulle-retry').attr('title', message);
            } else if (typeof message === 'string' && message.includes('Échec après')) {
              $(this.infoBulleWorkers).removeClass('bulle-ok bulle-retry').addClass('bulle-error').attr('title', message);
            }
            break;
          case 'error':
            this.updateDebugPanel({ status: '❌ Erreur', error: message });
            $(this.infoBulleWorkers).addClass('bulle-info-error').attr('title', "Une erreur s'est  produite.");
            if (debug) console.error('[pendingWorkerService] ⚠️', message);
            sessionStorage.setItem('ma_moulinette_pendingWorkerService', `[❌] ${message}`);
            break;
          default:
            if (debug) console.warn('[pendingWorkerService] Message inconnu', event.data);
        }
      };

      /* === Démarrage du worker === */
      this.worker.postMessage({ command: 'debug', value: debug, token: this.token });
      this.worker.postMessage({ command: 'start', token: this.token });

      if (debug) console.log('[pendingWorkerService] ✅ Service démarré');
      sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] ✅ Service démarré.');

      // On annonce qu’on est le leader
      this.channel.postMessage({ type: 'pong', from: this.instanceId });

      /* === Surveillance du worker (auto-reconnexion) === */
      this.monitorWorker({ debug });
    }, 250);
  },

  pause({ debug = false } = {}) {
    if (!this.worker && !this.monitorId) return;

    if (this.worker) {
      this.worker.postMessage({ command: 'stop', token: this.token });
      this.worker.terminate();
      this.worker = null;
    }

    if (this.monitorId) {
      clearInterval(this.monitorId);
      this.monitorId = null;
    }

    if (debug) {
      console.error('[pendingWorkerService] ⏸️ Pause (onglet inactif');
      this.updateDebugPanel({ status: '⏸️ Pause (onglet inactif)' });
    }
    sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] ⏸️ Pause (onglet inactif.)');
  },

  resume({ debug = false } = {}) {
    if (!this.worker) this.start({ debug });
    if (debug) {
        console.error('[pendingWorkerService] ▶️ Reprise (onglet actif).');
        this.updateDebugPanel({ status: '▶️ Reprise (onglet actif)' });
    }
  },

  stop({ debug = false } = {}) {
    if (this.worker) {
      this.worker.postMessage({ command: 'stop', token: this.token });
      this.worker.terminate();
      this.worker = null;
    }

     // invalide le token actuel
    this.token = null;

    // coupe le moniteur
    if (this.monitorId) {
      clearInterval(this.monitorId);
      this.monitorId = null;
    }

    if (this.channel) {
      this.channel.close();
      this.channel = null;
    }

    if (debug) {
      console.error('[pendingWorkerService] 🛑 Service arrêté manuellement.');
      this.updateDebugPanel({ status: '🛑 Service arrêté manuellement' });
    }
    sessionStorage.setItem('ma_moulinette_pendingWorkerService', '[pendingWorkerService] 🛑 Service arrêté manuellement');
  },

  /* === Mise à jour de la bulle === */
  updateInfoBulle(t, { debug = false } = {}) {
    const $infoBulleWorkers = $(this.infoBulleWorkers);
    const $infoBulleWip = $(this.infoBulleWip);

    if (!t || typeof t.pending === 'undefined') {
      $infoBulleWip.removeClass('bulle-ok').addClass('bulle-error').text('!').attr('title', 'Erreur de communication avec le worker.');
      $infoBulleWorkers.removeClass('bulle-ok bulle-retry').addClass('bulle-error').text('!').attr('title', 'Erreur de communication avec le worker.');

      if (debug) console.warn('[pendingWorkerService] Données invalides :', t);
      return;
    }

    $infoBulleWorkers.addClass('loading').removeClass('bulle-retry bulle-error');
    $infoBulleWip.addClass('loading').removeClass('bulle-error');
    setTimeout(() => {
      $infoBulleWorkers.removeClass('loading');
      $infoBulleWip.removeClass('loading');
    }, 300);

    if (t.pending > 0) {
      $infoBulleWorkers
        .removeClass('bulle-error bulle-retry')
        .addClass('bulle-ok')
        .text(t.pending)
        .attr('title', 'Nombre de projets en attente de traitement.');
      }

    if (t.pending === 0 && t.in_progress === 0){
        $infoBulleWorkers
          .removeClass('bulle-error bulle-retry')
          .addClass('bulle-ok')
          .text('0')
          .attr('title', 'Aucun projets en attente de traitement.');
        $infoBulleWip
          .removeClass('bulle-error')
          .addClass('bulle-ok')
          .text('0')
          .attr('title', 'Aucun traitement en cours.');
      }

    if (t.in_progress > 0) {
        $infoBulleWip
          .removeClass('bulle-error')
          .addClass('bulle-ok')
          .text(t.in_progress)
          .attr('title', 'Un projet est en cours de traitement.');
      } else {
          $infoBulleWip
          .removeClass('bulle-error')
          .addClass('bulle-ok')
          .text(t.in_progress)
          .attr('title', 'Aucun projet en cours de traitement.');
      }

      setTimeout(() =>
        {
          $infoBulleWorkers.removeClass('loading');
          $infoBulleWip.removeClass('loading');
        }, 300);
  },

  /* === Auto-reconnexion si worker silencieux (>60s) === */
  monitorWorker({ debug = false } = {}) {
    // évite doublons
    if (this.monitorId) return;

    const firstDelay = 2 * 15_000; // 30 s (2 cycles du polling à 15 s)

    // on diffère le premier check pour laisser le worker envoyer ses 1ers messages
    setTimeout(() => {
      // sécurise: si un monitor a déjà été posé entre-temps, on ne double pas
      if (this.monitorId) return;

      this.monitorId = setInterval(() => {
        if (!this.worker) return; // rien à faire si worker absent

        const delta = Date.now() - this.lastUpdate;
        if (debug) console.log('[pendingWorkerService] ⏱️ delta=', delta, 'ms');

        if (delta > this.silentThreshold) {
          // le worker n'a rien envoyé depuis > 60 s -> restart propre
          if (debug) console.warn('[pendingWorkerService] ⚠️ Worker silencieux → redémarrage');
          this.stop({ debug });   // stop coupe aussi le monitorId
          this.start({ debug });  // redémarre worker + rebranche 1 monitor (unique)
        }
      }, this.monitorPeriod);
    }, firstDelay);
  },

  initDebugPanel(debug = false) {
    const $panel = $('#pending-debug-monitor');
    if (!$panel.length) return;

    // Active / désactive l'affichage en fonction du debug
    if (debug) {
      $panel.removeClass('debug-hidden').addClass('debug-visible');
      this.logDebug('🔧 Debug Monitor activé');
    } else {
      $panel.removeClass('debug-visible').addClass('debug-hidden');
    }

    // Bouton de fermeture
    $('#debug-close').off('click').on('click', () => {
      $panel.removeClass('debug-visible').addClass('debug-hidden');
    });
  },

  logDebug(message) {
    const now = new Date().toLocaleTimeString();
    const $log = $('#debug-log');
    $log.prepend(`[${now}] ${message}\n`);
    const logs = $log.text().split('\n');
    if (logs.length > 50) $log.text(logs.slice(0, 50).join('\n'));
  },

  updateDebugPanel({ status, data, error } = {}) {
    const $panel = $('#pending-debug-monitor');
    if (!$panel.length) return; // rien à faire si panneau absent

    // Toujours mettre à jour, même s'il est caché
    if (status) $('#debug-status').text(status);

    if (data) {
      $('#debug-last-check').text(new Date().toLocaleTimeString());
      $('#debug-pending').text(data.pending ?? '–');
      $('#debug-in-progress').text(data.in_progress ?? '–');
    }

    if (error) {
      $('#debug-status').text('❌ Erreur');
      this.logDebug('⚠️ ' + error);
    }
  }
}
