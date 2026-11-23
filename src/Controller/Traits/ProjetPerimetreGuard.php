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

namespace App\Controller\Traits;

use App\Entity\ListeProjet;
use App\Repository\ListeProjetRepository;

/**
 * Vérifie qu'une clé Maven appartient bien au groupe fonctionnel de
 * l'utilisateur authentifié. Extrait de SuiviController/CosuiController::listeProjet(),
 * qui dupliquaient déjà la même logique — la 3e/4e reprise (OwaspController +
 * ApiOwaspPeintureController) a fait basculer la duplication en abstraction.
 *
 * Requiert sur la classe utilisatrice : `EntityManagerInterface $em`,
 * `LoggerInterface $logger`, et le trait AppUserAware (méthode `appUser()`).
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
trait ProjetPerimetreGuard
{
    private static string $perimetreErreur404 = "Vous devez être rattaché à un groupe fonctionnel (Erreur 404).";
    private static string $perimetreErreur406 = "Je n'ai pas trouvé de projets pour ton groupe fonctionnel. " .
        "Vérifie le nom du tag utilisé dans SonarQube (Erreur 406).";

    /**
     * [Description for verifierPerimetreProjet]
     *
     * @param string $maven_key
     * @param string $logTag Préfixe des logs (ex. "[OWASP]"), pour distinguer l'appelant.
     *
     * @return array<int|string, mixed> ['code' => 200] si autorisé,
     *         sinon ['code' => 404|406|..., 'message' => string|null]
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function verifierPerimetreProjet(string $maven_key, string $logTag): array
    {
        $groupes = $this->appUser()->getListeGroupeFonctionnel();
        if (empty($groupes)) {
            $this->logger->warning("{$logTag} ⚠️ Utilisateur sans groupe fonctionnel");
            return ['code' => 404, 'message' => self::$perimetreErreur404];
        }

        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);
        $tagPrefixes = ListeProjetRepository::normalizeGroupesToTagPrefixes($groupes);
        $requestListe = $listeProjetRepository->selectListeProjetByGroupe($tagPrefixes);
        if ($requestListe['code'] != 200) {
            return ['code' => $requestListe['code']];
        }

        $projets = $requestListe['liste'];
        if (empty($projets)) {
            return ['code' => 406, 'message' => self::$perimetreErreur406];
        }

        foreach ($projets as $item) {
            if (isset($item['id']) && $item['id'] === $maven_key) {
                return ['code' => 200];
            }
        }

        $this->logger->warning("{$logTag} ⚠️ Le projet n'est pas présent dans la liste de projets de l'utilisateur.");

        return ['code' => 406, 'message' => "Le projet n'est pas présent dans la liste de projets de l'utilisateur."];
    }
}
