<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ListeProjet;
use App\Repository\ListeProjetRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * [Description ListeProjets]
 */
class MesProjets
{
    public static string $erreur406 = "Je n'ai pas trouvé de projets pour ton groupe fonctionnel. ".
                                "Vérifies le nom du tag utilisé dans SonarQube (erreur 406).";

    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * [Description for listeProjet]
     *
     * @param array<int, string> $groupes
     *
     * @return array<int|string, mixed>
     *
     * Created at: 24/09/2024 14:54:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function liste(array $groupes): array
    {
        /** On instancie l'entityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

        $tagPrefixes = ListeProjetRepository::normalizeGroupesToTagPrefixes((array) $groupes);
        $requestListe = $listeProjetRepository->selectListeProjetByGroupe($tagPrefixes);
        if ($requestListe['code'] != 200) {
            return ['code' => $requestListe['code'], 'erreur' => $requestListe['erreur']];
        }

        $projets = $requestListe['liste'];

        /** j'ai pas trouvé de projet pour cette équipe. */
        if (empty($projets)) {
            return ['code' => 406, 'message' => self::$erreur406];
        }

        return ['code' => 200, 'projets' => $projets];
    }

}
