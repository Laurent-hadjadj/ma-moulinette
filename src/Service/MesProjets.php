<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ListeProjet;
use Doctrine\ORM\EntityManagerInterface;

/**
 * [Description ListeProjets]
 */
class MesProjets
{
    public static $erreur406 = "Je n'ai pas trouvé de projets pour ton équipe. ".
                                "Vérifies le nom du tag utilisé dans SonarQube (erreur 406).";

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    /**
     * [Description for listeProjet]
     *
     * @param mixed $groupes
     *
     * @return array
     *
     * Created at: 24/09/2024 14:54:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function liste($groupes): array
    {
        /** On instancie l'entityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

        /** On recherche les projets pour les équipes rattaché à l'utilisateur */
        $in = '';
        foreach ($groupes as $groupe) {
            if ($groupe !== 'null') {
                /** On met en minuscule */
                $minus = trim(strtolower($groupe));
                /** On construit la clause in et on remplace les espaces par des tirets  */
                $in = $in." tag LIKE '".preg_replace('/\s+/', '-', $minus)."%' OR ";
            }
        }

        /** On supprime le dernier OR */
        $inTrim = rtrim($in, " OR ");

        /** On construit la requête de selection des projets en fonction de(s) (l')équipes */
        $map = ['clause_where'=>$inTrim];
        $requestListe = $listeProjetRepository->selectListeProjetByGroupe($map);
        if ($requestListe['code'] != 200) {
            return ['code' => $requestListe['code'], 'erreur' => $requestListe['erreur']];
        }

        $projets = $requestListe['liste'];

        /** j'ai pas trouvé de projet pour cette équipe. */
        if (empty($projets)) {
            return ['code' => 406, 'message' => static::$erreur406];
        }

        return ['code' => 200, 'projets' => $projets];
    }

}
