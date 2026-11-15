<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Security;

/**
 * Liste blanche des requêtes autorisées sur l'instance de DÉMONSTRATION (`APP_MODE_DEMO=1`).
 *
 * **Refus par défaut** : une route absente de cette liste est interdite, y compris une route ajoutée plus tard. Il ne
 * suffit pas de filtrer par méthode HTTP, car de nombreuses routes de lecture sont en `POST` (`/api/secure/peinture/*`,
 * `/api/secure/liste/...`) tandis que certaines routes `GET` écrivent (`/profil/details`, `/repartition`,
 * `/api/secure/utilisateur/change-me`).
 *
 * Chaque motif a été retenu après lecture du contrôleur (aucune écriture en base, aucun appel sortant autre que vers
 * `SONAR_URL`, invalide en démo). Les routes de collecte, d'enregistrement, d'administration, de dépôt de rapports,
 * de préférences et de changement de mot de passe ne figurent volontairement PAS ici ; voir
 * `mkDocs/docs/ma-moulinette/architecture/demo.md` pour le détail.
 */
final class ModeDemo
{
    /** Méthodes de lecture : seules les routes ci-dessous les concernent. */
    private const LECTURE = ['GET', 'HEAD'];

    /**
     * Pages et lectures en `GET`/`HEAD`.
     *
     * @var list<string>
     */
    private const MOTIFS_LECTURE = [
        '#^/$#',
        '#^/(login|logout|logout/au-revoir|error)$#',
        '#^/(plan-du-site|mention-legale|donnees-personnelles)$#',
        '#^/\.well-known/[A-Za-z0-9._-]+$#',
        '#^/accueil$#',
        '#^/accueil/favori/liste/version$#',
        '#^/api/secure/accueil/favori/liste/projet$#',
        '#^/projet(/mes-projets|/cosui)?$#',
        '#^/suivi(/set|/rapport/pdf)?$#',
        '#^/owasp(/detail/[^/]+|/rapport/pdf)?$#',
        '#^/clean-code(/synthese(/pdf)?)?$#',
        '#^/dependency-check(/[^/]+)*$#',
        '#^/zap(/rapport/pdf)?$#',
        '#^/activity(/rapport/pdf)?$#',
        '#^/(preferences|profil)$#',
        '#^/statistiques(/projet)?$#',
        '#^/api/public/healthcheck/status$#',
    ];

    /**
     * Lectures en `POST` (corps JSON de critères, aucune écriture).
     *
     * @var list<string>
     */
    private const MOTIFS_POST_LECTURE = [
        '#^/api/secure/peinture/.+$#',
        '#^/api/secure/projet/liste$#',
        '#^/api/secure/liste/v[12]\.0/version$#',
        '#^/api/secure/get/version$#',
        '#^/api/secure/suivi/version/liste$#',
        '#^/api/secure/favori/check$#',
        '#^/api/secure/accueil/tags$#',
        '#^/api/secure/activity/dessin$#',
        '#^/api/secure/quality/(langage|off)$#',
        '#^/api/secure/(status|health|system/info)$#',
    ];

    /** Seule écriture tolérée : l'ouverture de session (formulaire de connexion). */
    private const MOTIFS_POST_SESSION = [
        '#^/login$#',
    ];

    private function __construct()
    {
    }

    /**
     * Indique si une requête est autorisée en mode démo.
     *
     * @param string $methode méthode HTTP (casse indifférente)
     * @param string $chemin  chemin de la requête (`Request::getPathInfo()`)
     */
    public static function autorise(string $methode, string $chemin): bool
    {
        $methode = strtoupper($methode);

        // Normalisation : un « / » final ou des « // » ne doivent pas contourner un motif ni en élargir la portée.
        $chemin = '/' . trim((string) preg_replace('#/{2,}#', '/', $chemin), '/');

        // Les séquences de remontée ou les octets de contrôle n'ont rien à faire dans une requête légitime.
        if (str_contains($chemin, '..') || preg_match('/[\x00-\x1F\x7F]/', $chemin) === 1) {
            return false;
        }

        if (\in_array($methode, self::LECTURE, true)) {
            return self::correspond($chemin, self::MOTIFS_LECTURE);
        }

        if ($methode === 'POST') {
            return self::correspond($chemin, self::MOTIFS_POST_LECTURE)
                || self::correspond($chemin, self::MOTIFS_POST_SESSION);
        }

        return false;
    }

    /**
     * @param list<string> $motifs
     */
    private static function correspond(string $chemin, array $motifs): bool
    {
        foreach ($motifs as $motif) {
            if (preg_match($motif, $chemin) === 1) {
                return true;
            }
        }

        return false;
    }
}
