<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

/**
 * Script batch de mise à jour des tags SonarQube
 * --------------------------------------------------------
 * 🧩 Version : 1.1.0
 * 🗂️ class   : `update_sonarqube_tags_cli.php`
 * 🧑‍💻 Auteur : Laurent HADJADJ
 * 🗓️ Dernière mise à jour : 2025-11-05
 *
 * 🧩 Usage :
 *
 * php update_sonarqube_tags_cli.php --url="https://sonar.exemple.com" --token="XXXXX" [--login=USER --password=PASS] [--dry-run]
 *
 * 💬 Options :
 *   --url       URL du serveur SonarQube (obligatoire)
 *   --token     Token d'accès SonarQube (obligatoire)
 *   --login     Compte utilisateur (facultatif si utilisation du token)
 *   --password  Mot de passe (facultatif si utilisation du token)
 *   --dry-run   Simulation : aucune modification réelle, uniquement un rapport
 */

// -----------------------------------------------------
// === PARAMÈTRES CLI ===
$options = getopt("", ["url:", "token:", "login::", "password::", "dry-run", "debug"]);
if (!isset($options['url'])) {
    die("❌ Utilisation : php update_sonarqube_tags_cli.php --url=\"https://sonar.exemple.com\" --token=\"XXXXX\" [--login=USER --password=PASS] [--dry-run]\n");
}

$SONAR_URL = rtrim($options['url'], '/');
$DRY_RUN = isset($options['dry-run']);
$DEBUG = isset($options['debug']);

// Authentification
$TOKEN = $options['token'] ?? null;
$LOGIN = $options['login'] ?? null;
$PASSWORD = $options['password'] ?? null;

if ($TOKEN) {
    $AUTH = $TOKEN . ":";  // token
} elseif ($LOGIN && $PASSWORD) {
    $AUTH = $LOGIN . ":" . $PASSWORD;  // login/password
} else {
    die("❌ Erreur : il faut soit --token, soit --login et --password\n");
}

// -----------------------------------------------------
// === CONFIG SSL / TLS ===
// -----------------------------------------------------
$VERIFY_PEER = 0;                   // 0 = ne vérifie pas le certificat
$VERIFY_HOST = 0;                   // 0 = ne vérifie pas le nom d’hôte SSL
$CIPHERS = "DEFAULT:!DH";           // TLS 1.3 compatible

// 🌐 Configuration PROXY (laisser vide si non utilisé)
$USE_PROXY = 0;  // 0 = désactiver le proxy
$PROXY_URL = "";
$PROXY_USERPWD = "";
// 🚫 Liste des domaines à ignorer pour le proxy (optionnel)
// si authentification requise : "user:password"
$NO_PROXY = ["localhost", "127.0.0.1"];

// -----------------------------------------------------
// === FONCTIONS UTILITAIRES ===
// -----------------------------------------------------

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) { mkdir($logDir, 0775, true); }
$logFile = sprintf('%s/update_sonar_tags_%s.log', $logDir, gmdate('Ymd_His'));

/**
 * [Description for logMessage]
 *
 * @param string $message
 * @param string $level
 *
 * @return void
 *
 * Created at: 08/04/2026 14:38:51 (Europe/Paris)
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
function callApi($endpoint, $params = [], $method = 'GET') {
    global $DEBUG, $SONAR_URL, $AUTH, $VERIFY_HOST, $VERIFY_PEER, $CIPHERS;
    global $USE_PROXY, $PROXY_URL, $PROXY_USERPWD, $NO_PROXY;

    $url = $SONAR_URL . $endpoint;
    $ch = curl_init();

    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $AUTH,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_SSL_VERIFYHOST => $VERIFY_HOST,
        CURLOPT_SSL_VERIFYPEER => $VERIFY_PEER,
        CURLOPT_SSL_CIPHER_LIST => $CIPHERS,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 90,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    // 🔧 Configuration du proxy si activée (0 = false, 1 = true)
    if ($USE_PROXY==1 && !empty($PROXY_URL)) {
        curl_setopt($ch, CURLOPT_PROXY, $PROXY_URL);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
        if (!empty($PROXY_USERPWD)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $PROXY_USERPWD);
        }
    }

    // Ignorer le proxy pour certains domaines
    if (!empty($NO_PROXY)) {
        curl_setopt($ch, CURLOPT_NOPROXY, implode(",", $NO_PROXY));
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($DEBUG){
        logMessage("Appel API : $method $url", 'DEBUG');
        logMessage("Paramètres : " . json_encode($params), 'DEBUG');
        logMessage("Authentification : " . (strpos($AUTH, ":") === 0 ? "Token" : "Login/Password"), 'DEBUG');
        logMessage("SSL : " . ($VERIFY_PEER ? "Vérifié" : "Non vérifié") . ", Ciphers: $CIPHERS", 'DEBUG');
        logMessage("Proxy : " . ($USE_PROXY ? "Activé ($PROXY_URL)" : "Désactivé"), 'DEBUG');

        logMessage("URL finale : $url", 'DEBUG');
        logMessage("Méthode : $method", 'DEBUG');
        logMessage("Statut HTTP : $status", 'DEBUG');
        logMessage("Corps de la réponse : ".substr($response, 0, 40), 'DEBUG');
        logMessage("Résultat de l'appel API : " . ($status >= 200 && $status < 300 ? "Succès" : "Erreur"), 'DEBUG');
        logMessage("Détails : " . ($status >= 200 && $status < 300 ? "Données traitées" : "Code d'erreur $status"), 'DEBUG');
    }

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        if($DEBUG){ logMessage("❌ Erreur cURL : $error_msg",'DEBUG'); }
        die("❌ Erreur cURL : $error_msg\n");
    }

    if ($status == 401) {
        if($DEBUG){ logMessage("🔒 Erreur 401 : accès non autorisé à $url",'DEBUG'); }
        die("🔒 Erreur 401 : accès non autorisé à $url\n");
    }

    if ($status < 200 || $status >= 300) {
        if($DEBUG){ logMessage("❌ Erreur API $status sur $url : $response",'DEBUG'); }
        die("❌ Erreur API $status sur $url : $response\n");
    }

    curl_close($ch);

    $json = json_decode($response, true);
    return $json ? $json : $response;
}

/**
 * [Description for sanitizeTag]
 * Nettoyage automatique du tag pour SonarQube
 *
 * @param string $tag
 *
 * @return string
 *
 * Created at: 05/11/2025 12:48:22 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function sanitizeTag(string $tag) {
    $tag = strtolower($tag);                       // tout en minuscules
    $tag = preg_replace('/[^a-z0-9]/', '-', $tag); // supprime tout sauf a-z0-9
    return $tag ?: "aucun";                        // si vide, mettre "aucun"
}

/**
 * [Description for getAllProjects]
 *
 * @return array
 *
 * Created at: 05/11/2025 10:22:47 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function getAllProjects():array {
    $projects = [];
    $page = 1;
    while (true) {
        $data = callApi("/api/components/search_projects", ["p" => $page, "ps" => 100], "GET");
        if (!isset($data['components'])) { break; }
        foreach ($data['components'] as $comp) {
            $projects[] = $comp['key'];
        }
        if (count($data['components']) < 100) { break; }
        $page++;
    }
    return $projects;
}

/**
 * [Description for setProjectTag]
 *
 * @param string $project_key
 * @param string $tag_value
 *
 * @return void
 *
 * Created at: 05/11/2025 10:22:42 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function setProjectTag(string $project_key, string $tag_value) {
    global $DRY_RUN;
    $tag_value = sanitizeTag($tag_value);
    if ($DRY_RUN) {
        logMessage("Tag simulé : '$tag_value'", 'INFO');
        return;
    }
    callApi("/api/project_tags/set", ["project" => $project_key, "tags" => $tag_value], "POST");
}

/**
 * [Description for geProjectGroup]
 *
 * @param string $project_key
 *
 * @return array|null
 *
 * Created at: 05/11/2025 10:24:56 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function getProjectGroup(string $project_key):array|null {
    $data = callApi("/api/permissions/groups", ["projectKey" => $project_key], "GET");
    if (!isset($data['groups'])) { return null; }

    foreach ($data['groups'] as $g) {
        $permissions = $g['permissions'] ?? [];
        $desc = $g['description'] ?? "";

        // Vérifie si groupe valide (permissions conformes & description contient "2021")
        if (!empty($permissions)
            && count(array_diff($permissions, ["codeviewer","securityhotspotadmin","user"])) == 0
            && strpos($desc, "2021") !== false) {
            return [
                'name' => $g['name'],
                'permissions' => $permissions
            ];
        }
    }
    return null;
}

/**
 * [Description for isArchivedProject]
 *
 * @param string $project_key
 *
 * @return bool
 *
 * Created at: 05/11/2025 14:04:36 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
function isArchivedProject(string $project_key): bool {
    $groups = callApi("/api/permissions/groups", ["projectKey"=>$project_key])['groups'] ?? [];
    foreach($groups as $g){
        if($g['name']==="Archive" && !empty($g['permissions'])){
            return true;
        }
    }
    return false;
}

// -----------------------------------------------------
// === SCRIPT PRINCIPAL ===
// -----------------------------------------------------
// -----------------------------------------------------
// === AFFICHAGE CONFIGURATION ===
logMessage("🔗 Connexion à SonarQube : $SONAR_URL", 'INFO');
if ($DRY_RUN) { logMessage("🧪 Mode simulation activé (aucune modification ne sera envoyée).", 'INFO'); }
if ($USE_PROXY==1 && $PROXY_URL) { logMessage("🌐 Proxy actif : $PROXY_URL", 'INFO'); }
if (!empty($NO_PROXY)) {
    $no_proxy = implode(", ", $NO_PROXY);
    logMessage("🚫 Pas de proxy pour : $no_proxy", 'INFO');
}

if ($DEBUG) {
    logMessage ("url : $SONAR_URL", 'DEBUG');
    logMessage("auth : $AUTH", 'DEBUG');
    $a = ($VERIFY_HOST == 1) ? 'true' : 'false';
    logMessage("verify_host : $a", 'DEBUG');
    $b = ($VERIFY_PEER == 1) ? 'true' : 'false';
    logMessage("verify_peer : $b", 'DEBUG');
    logMessage("ciphers : $CIPHERS", 'DEBUG');
    $c = ($USE_PROXY == 1) ? 'true' : 'false';
    logMessage("use_proxy : $c", 'DEBUG');
    $d = (empty($PROXY_URL) ? 'None' : $PROXY_URL);
    logMessage("proxy_url : $d", 'DEBUG');
    $e = (empty($PROXY_USERPWD) ? 'None' : $PROXY_USERPWD);
    logMessage("proxy_userpwd : $e ", 'DEBUG');
    $f = (empty($PROXY_USERPWD) ? 'None' : json_encode($NO_PROXY));
    logMessage("no_proxy : $f", 'DEBUG');
}

$archive = 0;
$proceeded = 0;
$warning = 0;
$mapping = [];

$projects = getAllProjects();
logMessage("📦 Nombre de projets récupérés : " . count($projects), 'INFO');

foreach ($projects as $key) {
    logMessage("🧩 Projet : $key", 'INFO');

    try {
        logMessage("🗑️ Suppression des tags...", 'INFO');
        setProjectTag($key,"aucun");

        // Détection "Archive" par nom
        if(isArchivedProject($key)){
            $group_tag = sanitizeTag("archive");
            $archive++;
        } else {
            $group_data = getProjectGroup($key);
            $group = $group_data['name'] ?? null;
            if($group!==null){
                $group_tag = sanitizeTag($group);
                $proceeded++;
            } else {
                $group_tag = "aucun";
                logMessage("⚠️ Aucun groupe valide trouvé, tag 'aucun' conservé", 'WARN');
                $warning++;
            }
        }
        logMessage("Tag assigné : $group_tag", 'INFO');
        setProjectTag($key, $group_tag);

        $mapping[] = [
            "maven_key" => $key,
            "groupe" => $group_tag
        ];
    } catch (Exception $e) {
        logMessage("❌ Erreur sur $key : " . $e->getMessage(), 'ERROR');
    }
}

// Sauvegarde du mapping
$file = "mapping_projets_groupes.json";
file_put_contents($file, json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

logMessage("✅ Traitement terminé.", 'INFO');
logMessage("  📌 Projets archivés   : $archive", 'INFO');
logMessage("  📌 Projets tagués     : $proceeded", 'INFO');
logMessage("  📌 Projets sans tag   : $warning", 'INFO');
logMessage("🗂️ Résultat sauvegardé dans : $file", 'INFO');
if ($DRY_RUN) {
    logMessage("⚠️ Aucun changement réel n’a été effectué (mode simulation).", 'WARN');
}
