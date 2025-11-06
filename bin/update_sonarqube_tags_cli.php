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
 * Script batch de mise à jour des tags SonarQube
 * --------------------------------------------------------
 * 🧩 Version : 1.1.0
 * 🗂️ class   : `update_sonarqube_tags_cli.php`
 * 🧑‍💻 Auteur : Laurent HADJADJ
 * 🗓️ Dernière mise à jour : 2025-11-05
 *
 * 🎯 Étapes :
 *
 * 1️⃣ Connexion à SonarQube via token (Basic Auth)
 * 2️⃣ Récupère la liste de tous les projets
 * 3️⃣ Supprime tous les tags (met 'aucun')
 * 4️⃣ Détecte le groupe du projet selon les critères :
 *      - permissions = ["codeviewer","securityhotspotadmin","user"] et description contient "2021"
 *      - permissions != [] et name = "Archive"
 * 5️⃣ Réassigne le tag avec le nom du groupe trouvé
 * 6️⃣ Sauvegarde le mapping dans mapping_projets_groupes.json
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
$options = getopt("", ["url:", "token:", "login::", "password::", "dry-run"]);
if (!isset($options['url'])) {
    die("❌ Utilisation : php update_sonarqube_tags_cli.php --url=\"https://sonar.exemple.com\" --token=\"XXXXX\" [--login=USER --password=PASS] [--dry-run]\n");
}

$SONAR_URL = rtrim($options['url'], '/');
$DRY_RUN = isset($options['dry-run']);

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
$VERIFY_PEER = false;     // false = ne vérifie pas le certificat
$VERIFY_HOST = 0;         // 0 = ne vérifie pas le nom d’hôte SSL
$CIPHERS = "DEFAULT:!DH"; // TLS 1.3 compatible

// 🌐 Configuration PROXY (laisser vide si non utilisé)
$USE_PROXY = true;                                 // false = désactiver le proxy
$PROXY_URL = "http://proxy.mon-serveur.fr:8080";
$PROXY_USERPWD = "";
// 🚫 Liste des domaines à ignorer pour le proxy (optionnel)
// si authentification requise : "user:password"
$NO_PROXY = ["localhost", "127.0.0.1"];
// -----------------------------------------------------
// === AFFICHAGE CONFIGURATION ===
echo "🔗 Connexion à SonarQube : $SONAR_URL\n";
if ($DRY_RUN) { echo "🧪 Mode simulation activé (aucune modification ne sera envoyée).\n"; }
if ($USE_PROXY && $PROXY_URL) { echo "🌐 Proxy actif : $PROXY_URL\n"; }
if (!empty($NO_PROXY)) { echo "🚫 Pas de proxy pour : " . implode(", ", $NO_PROXY) . "\n"; }

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
function callApi($endpoint, $params = [], $method = 'GET') {
    global $SONAR_URL, $AUTH, $VERIFY_HOST, $VERIFY_PEER, $CIPHERS;
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

    // 🔧 Configuration du proxy si activée
    if ($USE_PROXY && !empty($PROXY_URL)) {
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

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        die("❌ Erreur cURL : $error_msg\n");
    }

    if ($status == 401) {
        die("🔒 Erreur 401 : accès non autorisé à $url\n");
    }

    if ($status < 200 || $status >= 300) {
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
    $tag = strtolower($tag);                      // tout en minuscules
    $tag = preg_replace('/[^a-z0-9]/', '-', $tag); // supprime tout sauf a-z0-9
    return $tag ?: "aucun";                       // si vide, mettre "aucun"
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
        echo "    [DRY-RUN] Tag simulé : '$tag_value'\n";
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
$archive = 0;
$proceeded = 0;
$warning = 0;
$mapping = [];

$projects = getAllProjects();
echo "📦 Nombre de projets récupérés : " . count($projects) . "\n";

foreach ($projects as $key) {
    echo "\n🧩 Projet : $key\n";

    try {
        echo "  - Suppression des tags...\n";
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
                echo "    ⚠ Aucun groupe valide trouvé, tag 'aucun' conservé\n";
                $warning++;
            }
        }
        echo "    → Tag assigné : $group_tag\n";
        setProjectTag($key, $group_tag);

        $mapping[] = [
            "maven_key" => $key,
            "groupe" => $group_tag
        ];
    } catch (Exception $e) {
        echo "  ❌ Erreur sur $key : " . $e->getMessage() . "\n";
    }
}

// Sauvegarde du mapping
$file = "mapping_projets_groupes.json";
file_put_contents($file, json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n✅ Traitement terminé.\n";
echo "  - 📌 Projets archivés   : $archive\n";
echo "  - 📌 Projets tagués     : $proceeded\n";
echo "  - 📌 Projets sans tag   : $warning\n";
echo "🗂️ Résultat sauvegardé dans : $file\n";
if ($DRY_RUN) {
    echo "⚠️ Aucun changement réel n’a été effectué (mode simulation).\n";
}
