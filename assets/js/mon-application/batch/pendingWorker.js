// 🧵 Web Worker pour la mise à jour du statut de traitement
const delay = 30000; // 30 secondes
let isFetching = false;

/**
  * [Description for fetchPending]
  *
  * @return boolean
  *
  * Created at: 28/10/2025 16:03:47 (Europe/Paris)
  * @author     Laurent HADJADJ <laurent_h@me.com>
  * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
  */
async function fetchPending({ debug = false } = {}) {
  if (isFetching) return;
  isFetching = true;

  try {
    if (debug) console.log('[pendingWorker] 🔄 Vérification à', new Date().toLocaleTimeString());
    const response = await fetch('/api/traitement/pending', {
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app',
        'Content-Type': 'application/json'
      }
    });

    if (!response.ok) throw new Error('Erreur réseau ' + response.status);
    const data = await response.json();
    if (debug) console.log('[pendingWorker] ↩️ Données reçues', data);

    postMessage({ status: 'ok', data });

    // Si aucun in_progress et des pending, lancer automatiquement
    if (data.in_progress === 0 && data.pending > 0) {
      // appeler endpoint backend pour démarrer le prochain pending
      //await fetch('/api/traitement/start-next-pending', { method: 'POST' });
      if (debug) console.log('on lance le prochain traitement ?');
    }

  } catch (error) {
    postMessage({ status: 'error', error: error.message });
  } finally {
    isFetching = false;
  }
}

// Premier appel immédiat
fetchPending();
setInterval(fetchPending, delay);
