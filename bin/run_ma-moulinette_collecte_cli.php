<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

/**
 * Script de collecte des données SonarQube depuis Ma-Moulinette
 * --------------------------------------------------------
 * 🧩 Version : 1.4.1
 * 🗂️ class   : `run_ma-moulinette_collecte_cli.php`
 * 🧑‍💻 Auteur : Laurent HADJADJ
 * 🗓️ Dernière mise à jour : 2025-11-12
 *
 * 🎯 Étapes :
 *
 * 1️⃣ Connexion à l'application ma-moulinette via token (dans le body)
 * 2️⃣ Récupération des traitements automatiques non démarrés
 * 3️⃣ Démarrage de chaque traitement
 * 4️⃣ Sauvegarde locale des résultats + rotation des logs
 *
 * 🧩 Usage :
 *
 * php run_ma-moulinette_collecte_cli.php --url="http?s://ma.moulinette.fr" --token="XXXXX" [--flush=2] [--keep-days=7] [--max-log-size=10] [--dry-run] [--debug] [--help]
 *
 * 💬 Options :
 *   --url=""          URL du serveur Ma-Moulinette [obligatoire]
 *   --token=""        Token d'accès [obligatoire]
 *   --flush=N         Nombre de traitements à envoyer par lot [facultatif]
 *   --keep-day=N      Durée de conservation des logs en jour (int) [facultatif]
 *   --max-log-size=N  Taille max des journaux en MO (int) [facultatif]
 *   --dry-run         Simulation : aucune modification réelle, uniquement un rapport [facultatif]
 *   --debug           Affiche les information de debug de la méthode callApi() [facultatif]
 *   --help            Affiche cette aide [facultatif]
 */

// -----------------------------------------------------
// === PARAMÈTRES CLI ===
// -----------------------------------------------------

// Forcer le garbage collector à nettoyer tout ce qui traîne
gc_collect_cycles();
gc_mem_caches();

$options = getopt("", ["url:", "token:", "flush::", "keep-days::", "max-log-size::", "dry-run", "debug", "help"]);

if (isset($options['help'])) {
    echo <<<HELP
php run_ma-moulinette_collecte_cli.php --url="http?s://ma.moulinette.fr" --token="XXXXX" [--flush=2] [--keep-days=7] [--max-log-size=10] [--dry-run] [--debug]

💬 Options :
    📌 --url=""         URL du serveur Ma-Moulinette [obligatoire]
    📌 --token=""       Token d'accès [obligatoire]
    📌 --flush=N        Nombre de traitements à envoyer par lot [facultatif]
    📌 --keep-days=N    Durée de conservation des logs en jour (int) [facultatif]
    📌 --max-log-size=N Taille max des journaux en Mo (int) [facultatif]
    📌 --dry-run        Simulation : aucune modification réelle, uniquement un rapport [facultatif]
    📌 --debug          Affiche les informations de debug de la méthode callApi() [facultatif]
    📌 --help           Affiche cette aide [facultatif]
HELP;
    exit(0);
}

if (!isset($options['url']) || !isset($options['token'])) {
    die("❌ Utilisation : php run_ma-moulinette_collecte_cli.php --url=\"http?s://ma.moulinette.fr\" --token=\"XXXXX\" [--flush=2] [--keep-days=7] [--max-log-size=10] [--dry-run] [--debug] [--help]\n");
}

$MA_MOULINETTE_URL = rtrim($options['url'], '/');
$TOKEN = trim($options['token']);
$FLUSH = isset($options['flush']) ? (int)$options['flush'] : 0;
$DRY_RUN = isset($options['dry-run']);
$KEEP_DAYS = isset($options['keep-days']) ? (int)$options['keep-days'] : 7;
$MAX_LOG_SIZE = isset($options['max-log-size']) ? (int)$options['max-log-size'] : 10;
$DEBUG = isset($options['debug']);

// -----------------------------------------------------
// === CONFIG SSL / TLS et PROXY ===
// -----------------------------------------------------
$VERIFY_PEER = false;     // false = ne vérifie pas le certificat
$VERIFY_HOST = 0;         // 0 = ne vérifie pas le nom d’hôte SSL
$CIPHERS = "DEFAULT:!DH"; // TLS 1.3 compatible

// 🌐 Configuration PROXY (laisser vide si non utilisé)
$USE_PROXY = false; // false = désactiver le proxy
$PROXY_URL = "http://proxy.ma-moulinette.fr:8080";
$PROXY_USER_PWD = "";

// 🚫 Liste des domaines à ignorer pour le proxy
$NO_PROXY = ["localhost", "127.0.0.1"];

// -----------------------------------------------------
// === LOGGING ===
// -----------------------------------------------------

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) { mkdir($logDir, 0775, true); }
$logFile = sprintf('%s/ma_moulinette_%s.log', $logDir, gmdate('Ymd_His'));

/**
 * [Description for logMessage]
 * Journalisation
 *
 * @param string $message
 *
 * @return void
 *
 * Created at: 09/11/2025 10:57:37 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function logMessage(string $message, string $level = 'INFO'): void {
    global $logFile;
    $date = gmdate('[Y-m-d H:i:s]');
    $level = strtoupper($level);

    // Coloration console
    $colors = [
        'INFO' => "\033[32m",    // vert
        'WARN' => "\033[33m",    // jaune
        'ERROR' => "\033[31m",   // rouge
        'DEBUG' => "\033[36m"    // cyan
    ];
    $reset = "\033[0m";

    $prefix = isset($colors[$level]) ? "{$colors[$level]}[$level]$reset" : "[$level]";
    $line = "$date $prefix $message\n";

    echo $line;
    file_put_contents($logFile, strip_tags($line), FILE_APPEND);
}

/**
 * [Description for rotateLogs]
 *
 * Rotation automatique des logs :
 *  - Tronque si >10 Mo
 *  - Supprime logs JSON de plus de 7 jours
 *
 * @param string $logDir
 * @param string $logFile
 * @param int $maxSize
 * @param int $maxAgeDays
 *
 * @return void
 *
 * Created at: 07/11/2025 09:51:39 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function rotateLogs(string $logDir, string $logFile, int $maxSize = 10485760, int $maxAgeDays = 7): void {
    if (file_exists($logFile) && filesize($logFile) > $maxSize) {
        $backup = $logFile . '.' . date('Ymd_His') . '.bak';
        rename($logFile, $backup);
        file_put_contents($logFile, "🔁 Log tronqué le " . date('Y-m-d H:i:s') . "\n");
    }

    foreach (glob("$logDir/*.bak") as $bak) {
        if (filemtime($bak) < time() - ($maxAgeDays * 86400)) {
            unlink($bak);
        }
    }
}

// Rotation des logs au démarrage
rotateLogs($logDir, $logFile, $MAX_LOG_SIZE * 1024 * 1024, $KEEP_DAYS);

// -----------------------------------------------------
// === AFFICHAGE CONFIGURATION ===
// -----------------------------------------------------

logMessage(" 🔗 Connexion à Ma-Moulinette : $MA_MOULINETTE_URL", 'INFO');
logMessage(" 🧾 Conservation des logs : $KEEP_DAYS jour(s)", 'INFO');
logMessage(" 📏 Taille max des logs : $MAX_LOG_SIZE Mo", 'INFO');
logMessage(" 📂 Répertoire des logs : $logDir", 'INFO');
if ($DRY_RUN) { logMessage(" ⚠️ Mode simulation activé.", 'INFO'); }
if ($DEBUG) { logMessage("🛠️ Mode debug activé : toutes les requêtes/ réponses API seront affichées.\n", 'DEBUG'); }
if ($DRY_RUN) { logMessage("🛠️ Flush activé tout les $FLUSH traitements", 'DEBUG'); }
if ($USE_PROXY && $PROXY_URL) { logMessage(" 🌐 Proxy actif : $PROXY_URL", 'INFO'); }
if (!empty($NO_PROXY)) { logMessage(" 🚫 Pas de proxy pour : " . implode(", ", $NO_PROXY), 'WARN'); }

// -----------------------------------------------------
// === FONCTIONS UTILITAIRES ===
// -----------------------------------------------------

/**
 * [Description for callApi]
 *
 * @param mixed $endpoint
 * @param array $params
 * @param string $method
 *
 * @return json
 *
 * Created at: 05/11/2025 10:22:52 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function callApi($endpoint, $params = [], $method = 'GET', $timeout = 600) {
    global $MA_MOULINETTE_URL, $VERIFY_HOST, $VERIFY_PEER, $CIPHERS;
    global $USE_PROXY, $PROXY_URL, $PROXY_USER_PWD, $NO_PROXY, $DEBUG;

    $url = $MA_MOULINETTE_URL . $endpoint;
    $ch = curl_init();
    $headers = ['Accept: application/json'];

     // Pour POST on envoie du JSON (le back fait json_decode($request->getContent()))
    if ($method === 'POST') {
        $payload = json_encode($params, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_POST, true);
    } elseif ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => $VERIFY_HOST,
        CURLOPT_SSL_VERIFYPEER => $VERIFY_PEER,
        CURLOPT_SSL_CIPHER_LIST => $CIPHERS,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS, // support de http en local
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    // 🔧 Configuration du proxy si activée
    if ($USE_PROXY && !empty($PROXY_URL)) {
        curl_setopt($ch, CURLOPT_PROXY, $PROXY_URL);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
        if (!empty($PROXY_USER_PWD)) { curl_setopt($ch, CURLOPT_PROXYUSERPWD, $PROXY_USER_PWD); }
    }

    // Ignorer le proxy pour certains domaines
    if (!empty($NO_PROXY)) { curl_setopt($ch, CURLOPT_NOPROXY, implode(",", $NO_PROXY)); }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        logMessage("❌ Erreur cURL : " . curl_error($ch), 'ERROR');
        curl_close($ch);
        return ['code' => 500, 'message' => 'Erreur cURL'];
    }

    curl_close($ch);

    $json = json_decode($response, true);

    if ($DEBUG) {
        logMessage("🛠️ API CALL: URL=$url | CODE=$status | RESPONSE=" . ($response ?: '(vide)'), 'DEBUG');
    }
    if (!is_array($json)) {
        logMessage(" ⚠️ Réponse non JSON de $url : $response", 'WARN');
        return ['code' => $status, 'message' => 'Invalid JSON', 'response' => $response];
    }

    return $json;
}

// -----------------------------------------------------
// === OUTILS LOTS ===
// -----------------------------------------------------

/**
 * [Description for getTraitementAutomatique]
 * Récupère la liste des traitements automatiques non démarrés
 *
 * @return array
 *
 * Created at: 07/11/2025 09:13:01 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function getTraitementAutomatique():array {
    global $TOKEN;
    $data = callApi("/api/public/traitement/automatique/liste", ["token" => $TOKEN], "POST");

    if (!isset($data['code']) || $data['code'] !== 200) {
        logMessage("❌ Erreur récupération traitements : " . json_encode($data, JSON_UNESCAPED_UNICODE), 'ERROR');
        return [];
    }

    $liste = $data['liste_traitement'] ?? [];

    if (empty($liste)) {
        logMessage(" 📭 Aucun traitement automatique disponible.", 'INFO');
        return [];
    }

    return $liste;
}

/**
 * [Description for startTraitement]
 *
 * Lance un traitement automatique
 * @param array $traitement
 *
 * @return json
 *
 * Created at: 07/11/2025 09:24:05 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function startTraitement(array $traitement) {
    global $TOKEN, $DRY_RUN;

    $params = [
        'token' => (string) $TOKEN,
        'nom_traitement' => (string) $traitement['nom_traitement'],
        'portefeuille' => (string) $traitement['portefeuille'],
        'traitement_id' => (string) $traitement['traitement_id']
    ];

    if ($DRY_RUN) {
        logMessage(" ⚠️ Simulation : démarrage du traitement {$traitement['nom_traitement']}", 'WARN');
        return ['code' => 200, 'message' => 'Simulation réussie'];
    }

    return callApi("/api/public/traitement/automatique/start", $params, "POST");
}

// -----------------------------------------------------
// === SCRIPT PRINCIPAL ===
// -----------------------------------------------------

logMessage(" ℹ️ Début du batch Ma-Moulinette ($MA_MOULINETTE_URL)", 'INFO');

$startGlobal = microtime(true);
$memoryStart = round((memory_get_usage(true) /1024 /1024), 2);
logMessage(" ℹ️ Mémoire au démarrage : {$memoryStart} MB", 'INFO');

$traitements = getTraitementAutomatique();
if (empty($traitements)) {
    logMessage(" ℹ️ Aucun traitement à exécuter. Fin du batch.", 'INFO');
    exit(0);
}

// Appliquer flush si défini
if ($FLUSH > 0 && count($traitements) > $FLUSH) {
    logMessage(" ⚠️ Mode flush activé : envoi seulement des $FLUSH premiers traitements sur " . count($traitements), 'WARN');
    $traitements = array_slice($traitements, 0, $FLUSH);
}

// Suivi du statut global
$hasError = false;

foreach ($traitements as $traitement) {
    $nom = $traitement['nom_traitement'];
    $projets = $traitement['nombre_projet'] ?? '?';
    logMessage(" ⚙️ Traitement : $nom ($projets projets)", 'INFO');

    $start = microtime(true);

    try {
        $result = startTraitement($traitement);
    } catch (Throwable $e) {
        logMessage("❌ Exception PHP pendant $nom : {$e->getMessage()}", 'ERROR');
        $hasError = true;
        continue;
    }

    $duration = round(microtime(true) - $start, 2);
    // S'assurer que le résultat contient bien 'code' et 'message'
    $code = $result['code'] ?? 0;
    $msg = $result['message'] ?? '(aucun message)';
    $status = ($code === 200) ? 'INFO' : 'ERROR';
    $icon = ($code === 200) ? ' ✅' : '❌';

    logMessage("$icon Terminé : $nom | {$duration}s | Code=$code | Message=$msg", $status);

    if ($code !== 200) {
        $hasError = true; // marque une erreur API
    }

    // En mode debug, afficher la réponse complète si nécessaire
    if ($DEBUG) {
        logMessage("🔍 Réponse brute API : " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 'DEBUG');
    }
}

// -----------------------------------------------------
// === ENREGISTREMENT DU PROFILING LOCAL ===
// -----------------------------------------------------

$totalTime = round(microtime(true) - $start, 2);
logMessage(" ⏱️ Batch terminé en {$totalTime}s", 'INFO');

if ($DRY_RUN) {
    logMessage(" ⚠️ Mode simulation : aucune modification réelle envoyée.", 'WARN');
}

// -----------------------------------------------------
// 🆕 === CODE DE SORTIE GLOBAL POUR VTOM ===
// -----------------------------------------------------

if ($hasError) {
    logMessage("🚨 Fin du batch avec anomalies (code de retour 1).", 'ERROR');
    exit(1);
} else {
    logMessage(" ✅ Batch terminé sans erreur (code de retour 0).", 'INFO');
    exit(0);
}
