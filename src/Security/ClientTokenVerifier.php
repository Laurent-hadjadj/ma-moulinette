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
 * [Description ClientTokenVerifier]
 * Contrôle un jeton d'API partagé (ex. `api.client_token`) avec les mêmes garanties que les jetons
 * des dépôts de rapports (`DependencyCheckTokenSubscriber`, `ZapIngestTokenSubscriber`) :
 *
 * - comparaison en temps constant (`hash_equals`), pour ne pas laisser deviner le jeton par mesure de durée ;
 * - **échec fermé** : si le jeton attendu n'est pas configuré (absent, vide ou valeur d'exemple `changeme`), le contrôle
 *   renvoie {@see ResultatJeton::Desactive} et n'accepte AUCUNE valeur — y compris la chaîne vide, que l'ancienne
 *   comparaison `!==` laissait passer quand le jeton attendu était vide ;
 * - un jeton fourni qui n'est pas une chaîne non vide est refusé.
 *
 * Classe sans état : un appel statique suffit, ce qui n'impose aucun changement de constructeur aux appelants.
 * Elle ne journalise rien : l'appelant ne doit JAMAIS écrire le jeton fourni dans un journal.
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright   Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
final class ClientTokenVerifier
{
    /** Valeur d'exemple laissée dans un fichier d'environnement : le service doit alors rester fermé. */
    public const SENTINELLE = 'changeme';

    private function __construct() {}

    /**
     * [Description for verifier]
     *
     * @param mixed $attendu jeton configuré côté serveur (paramètre ou variable d'environnement)
     * @param mixed $fourni  jeton reçu dans la requête
     *
     * @return ResultatJeton
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public static function verifier(mixed $attendu, mixed $fourni): ResultatJeton
    {
        if (!\is_string($attendu) || $attendu === '' || $attendu === self::SENTINELLE) {
            return ResultatJeton::Desactive;
        }

        if (!\is_string($fourni) || $fourni === '') {
            return ResultatJeton::Refuse;
        }

        return hash_equals($attendu, $fourni) ? ResultatJeton::Valide : ResultatJeton::Refuse;
    }
}
