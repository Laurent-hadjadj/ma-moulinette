// 🧵 Web Worker pour la mise à jour du statut de traitement
const delay = 30000; // 30 secondes
let isFetching = false;
console.log('OK');
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
  console.log('start');
  if (isFetching) return;
  isFetching = true;
  console.log('restart');
  try {
    console.log('[pendingWorker] 🔄 Vérification à', new Date().toLocaleTimeString());
    const response = await fetch('/api/traitement/pending', {
      headers: {
        'X-API-Custom-403': 'true',
        'X-Internal-Front': 'front-app',
        'Content-Type': 'application/json'
      }
    });

    if (!response.ok) throw new Error('Erreur réseau ' + response.status);
    const data = await response.json();
    console.log('[pendingWorker] ↩️ Données reçues', data);

    postMessage({ status: 'ok', data });

    // Si aucun in_progress et des pending, lancer automatiquement
    if (data.in_progress === 0 && data.pending > 0) {
      // appeler endpoint backend pour démarrer le prochain pending
      //await fetch('/api/traitement/start-next-pending', { method: 'POST' });
      console.log('on lance le prochain');
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
