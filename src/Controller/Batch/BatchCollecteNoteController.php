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

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteInformationProjetController]
 */
class BatchCollecteNoteController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $removeReturnline = "/\s+/u";

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
     * @param string $type
     *
     * @return array
     *
     * Created at: 21/05/2024 23:51:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function batchCollecteNote(string $mavenKey, string $type): array
    {
        /** On instancie l'EntityRepository */
        $noteRepository = $this->em->getRepository(Notes::class);

       /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $url = "$tempoUrl/api/measures/component?component=$mavenKey&metricKeys=$type"."_rating";

        /** Appelle le client HTTP */
        $result = $this->client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey, 'type'=>$type];
        $request=$noteRepository->deleteNotesMavenKey($map);
        if ($request['code']!=200) {
            return ['code' => $request['code'], 'requête'=>'deleteNoteMavenKey'];
        }

        /** Création de la date du jour */
        $date = new \DateTime('now', new \DateTimeZone("Europe/Paris"));

        /** Enregistrement des nouvelles valeurs */
        /** Attention la valeur de la note est en float dans SonarQube, on le converti en integer */
        foreach ($result['component']['measures'] as $mesure) {
            $map=['maven_key' => $mavenKey, 'type' => $type, 'value' => intval($mesure['value']), 'date_enregistrement' => $date];
            $request=$noteRepository->insertNotes($map);
            if ($request['code']!=200) {
                return ['code' => $request['code'], 'erreur' => $request['erreur'],'requête'=>'insertNote'];
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
        return ['code' => 200, "note_$type" => ["value" => $note]];
    }
}
