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
 * 🧵 pendingWorker.js
 * Web Worker - surveille les traitements en attente / en cours.
 * Commandes supportées : start / stop
 *
 * Sécurité + robustesse :
 *  - Token d’authentification (évite les postMessage externes)
 *  - Retry exponentiel en cas d’échec réseau
 *  - Timeout de 10s par requête
 *  - Journalisation (DEBUG)
 */
const DELAY = 15000; // 15 secondes
const DEBUG = false;
let isFetching = false;
let intervalId = null;
let retryDelay = DELAY;
let expectedToken = null;

/* === Utilitaire log conditionnel === */

/**
 * [Description for log]
 *
 * @param mixed ...args
 *
 * @return [type]
 *
 * Created at: 02/11/2025 13:33:03 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
const log = function(...args) {
  if (DEBUG) console.log('[pendingWorker]', ...args);
}

/* === Fonction principale === */

/**
  * [Description for fetchPending]
  *
  * @return boolean
  *
  * Created at: 28/10/2025 16:03:47 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
  */
async function fetchPending() {
  if (isFetching) return;
  isFetching = true;

  try {
    log('🔄 Vérification à', new Date().toLocaleTimeString());

    const response = await fetch('/api/traitement/pending', {
      method: 'GET',
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app',
        'Content-Type': 'application/json'
      },
      cache: 'no-store',
      signal: AbortSignal.timeout(10_000),
    });

    if (!response.ok) throw new Error('Erreur réseau ' + response.status);

    const data = await response.json();

    postMessage({ type: 'data', data });
    log('↩️ Données reçues', data);

    // Reset retryDelay après succès
    retryDelay = DELAY;

    // Si aucun in_progress et des pending, lancer automatiquement
    if (data && data.in_progress === 0 && data.pending > 0) {
      log('💡 Aucun en cours, pending dispo → possible lancement auto');
      await fetch('/api/traitement/start-next-pending', { method: 'POST' });
    }

  } catch (error) {
    postMessage({ type: 'error', message: error.message || String(error) });
    log('⚠️ Erreur', error.message);

     // Retry exponentiel
    retryDelay = Math.min(retryDelay * 2, 120_000);
    clearInterval(intervalId);
    intervalId = setInterval(fetchPending, retryDelay);
    postMessage({
      type: 'status',
      message: `Reconnexion dans ${retryDelay / 1000}s`
    });
  } finally {
    isFetching = false;
  }
}

/* === Gestion des commandes du main thread === */
onmessage = (event) => {
  const { command, token } = event.data || {};

  if (!command) return;

  /* ==== Activation du mode debug et propagation === */
  if (command === 'debug') {
    self.DEBUG = !!value;
    postMessage({ type: 'status', message: `Mode DEBUG ${self.DEBUG ? 'activé' : 'désactivé'}` });
    return;
  }

  // Vérification du token de sécurité
  if (!expectedToken) expectedToken = token; // premier message : on l’enregistre
  if (token !== expectedToken) {
    postMessage({ type: 'error', message: 'Token de sécurité invalide' });
    log('❌ Token rejeté');
    return;
  }

  switch (command) {
    case 'start':
      if (intervalId) return; // déjà lancé
      postMessage({ type: 'status', message: 'Worker démarré' });
      log('▶️ Commande START reçue');
      fetchPending();
      intervalId = setInterval(fetchPending, DELAY);
      break;

    case 'stop':
      clearInterval(intervalId);
      intervalId = null;
      postMessage({ type: 'status', message: 'Worker arrêté' });
      log('⏹️ Commande STOP reçue');
      break;

    default:
      postMessage({ type: 'error', message: `Commande inconnue: ${command}` });
      log('❓ Commande inconnue', command);
  }
};
