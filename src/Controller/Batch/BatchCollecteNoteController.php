<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Batch;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Notes;
use App\Entity\Hotspots;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteNoteController]
 */
class BatchCollecteNoteController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $request = "requête : ";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private Client $client,
    ) {
        $this->em = $em;
        $this->client = $client;
    }

    /**
     * [Description for batchNote]
     *
     * @param string $mavenKey
     * @param string $modeCollecte
     * @param string $utilisateurCollecte
     * @param string $type
     *
     * @return array
     *
     * Created at: 21/05/2024 23:51:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function batchCollecteNote(string $mavenKey, string $modeCollecte, string $utilisateurCollecte, string $type): array
    {
        /** On instancie l'EntityRepository */
        $noteRepository = $this->em->getRepository(Notes::class);

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /** Construit l'URL en utilisant http_build_query pour les paramètres de la requête */
        $queryParams = [
            'component' => $mavenKey,
            'metricKeys' => $type."_rating",
        ];
        $queryString = http_build_query($queryParams);

        /** Appelle le client HTTP */
        $result = $this->client->http("$tempoUrl/api/measures/component?$queryString");

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey, 'type'=>$type];
        $delete=$noteRepository->deleteNotesMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'],
                    'error'=>[$delete['erreur'],
                            static::$request=>'deleteNoteMavenKey']
                    ];
        }

        /** Création de la date du jour */
        $date = new \DateTimeImmutable('now', new \DateTimeZone("Europe/Paris"));

        /** Enregistrement des nouvelles valeurs */
        /** Attention la valeur de la note est en float dans SonarQube, on le converti en integer */
        foreach ($result['component']['measures'] as $mesure) {
            $map=[
                'maven_key' => $mavenKey,
                'type' => $type,
                'value' => intval($mesure['value']),
                'mode_collecte' => $modeCollecte,
                'utilisateur_collecte' => $utilisateurCollecte,
                'date_enregistrement' => $date];
            $request=$noteRepository->insertNotes($map);
            if ($request['code']!=200) {
                return ['code' => $request['code'],
                        'error'=>[$request['erreur'],
                        static::$request=>'insertNote']
                ];
            }
        }

        /** Attention, la valeur est un float. */
        $latestNote = \intval($mesure['value']);
       /** Établi une correspondance entre les valeurs des notes et les notes par lettre */
        $noteMap = [
            1 => 'A',
            2 => 'B',
            3 => 'C',
            4 => 'D',
            5 => 'E'
        ];

        /** Vérifier si la dernière valeur de la note existe dans la carte, sinon définir une note par défaut.
        */
        $note = $noteMap[$latestNote] ?? 'Z';

        /** On prépare les données pour l'historique */
        $data=[ 'note_'.$type => $note];

        return ['code' => 200, 'message' => ['value' => $note], 'data' => $data];
    }

    /**
     * [Description for BatchCollecteNoteHotspot]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 03/06/2024 19:15:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteNoteHotspot($mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $hotspotsRepository = $this->em->getRepository(Hotspots::class);

        // Première requête pour obtenir le nombre de hotspots à réviser
        $map=['maven_key'=>$mavenKey, 'status'=> 'TO_REVIEW' ];
        $toReview=$hotspotsRepository->countHotspotsStatus($map);
        if ($toReview['code']!=200) {
            return ['code' => $toReview['code'],
                    'error'=>[$toReview['erreur'],
                    static::$request=>'countHotspotsStatus']
            ];
        }
        // Seconde requête pour obtenir le nombre de hotspots révisés
        $map=['maven_key'=>$mavenKey, 'status'=> 'REVIEWED' ];
        $reviewed=$hotspotsRepository->countHotspotsStatus($map);
        if ($reviewed['code']!=200) {
            return ['code' => $reviewed['code'],
                    'error'=>[$reviewed['erreur'],
                    static::$request=>'countHotspotsStatus']
            ];
        }

        // Initialisation de la note
        $note = "A";
        if (!empty($toReview['to_review']) && $toReview['to_review'] > 0) {
            // Calcul du ratio si 'to_review' n'est pas vide et supérieur à 0
            $ratio = intval($reviewed['reviewed']) * 100 / intval($toReview['to_review']) + intval($reviewed['reviewed']);

            // Détermination de la note en fonction du ratio
            if ($ratio >= 80) {
                $note = "A";
            } elseif ($ratio >= 70) {
                $note = "B";
            } elseif ($ratio >= 50) {
                $note = "C";
            } elseif ($ratio >= 30) {
                $note = "D";
            } else {
                $note = "E";
            }
        }
        /** On prépare les données pour l'historique */
        $data=[ 'note_hotspot' => $note];

        return ['code' => 200, 'message' => ['note_hotspot' => $note], 'data' => $data];
    }
}
