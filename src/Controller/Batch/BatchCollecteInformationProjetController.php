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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Logger */
use Psr\Log\LoggerInterface;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\InformationProjet;

/** Client HTTP */
use App\Service\Client;
use App\Service\IsValideMavenKey;

/**
 * [Description BatchCollecteInformationProjetController]
 */
class BatchCollecteInformationProjetController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $removeReturnline = "/\s+/u";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private IsValideMavenKey $isValidMavenKey,
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->isValidMavenKey = $isValidMavenKey;
    }

    /**
     * [Description for batchInformationVersion]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 09/12/2022, 17:13:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    #[Route('/api/batch/information/version', name: 'batch_information_version', methods: ['POST'])]
    public function batchInformationVersion(Request $request): response
    {
        /** On instancie l'entityRepository */
        $informationProjetEntity = $this->em->getRepository(InformationProjet::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        $isValide=$this->isValidMavenKey->isValide($data->maven_key);
        if ($isValide===404) {
            return $response->setData(['code'=>404]);
        }

        /** On compte toutes les version de type (RELEASE, SNAPSHOT, AUTRE) */
        $map=['maven_key' => $data->maven_key];
        $toutesLesVersions=$informationProjetEntity->countInformationProjetAllType($map);
        if ($toutesLesVersions['code']!=200) {
            return $response->setData(['code' => $toutesLesVersions['code'], 'methode'=>'touteLesVersions']);
        }

        /** On compte les releases */
        $map=['maven_key'=>$data->maven_key, 'type'=>'RELEASE'];
        $release=$informationProjetEntity->countInformationProjetType($map);
        if ($release['code']!=200) {
            return $response->setData(['code' => $release['code'], 'methode'=>'releases']);
        }

        /** On compte les snapshots */
        $map=['maven_key'=>$data->maven_key, 'type'=>'SNAPSHOT'];
        $snapshot=$informationProjetEntity->countInformationProjetType($map);
        if ($snapshot['code']!=200) {
            return $response->setData(['code' => $snapshot['code'], 'methode'=>'snapshot']);
        }

        /** On récupère la dernière version et sa date de publication */
        $map=['maven_key'=>$data->maven_key];
        $infoRelease=$informationProjetEntity->selectInformationProjetVersionLast($map);
        if ($infoRelease['code']!=200) {
            return $response->setData(['code' => $infoRelease['code'], 'methode'=>'infoRelease']);
        }

        $toutesLesVersions = isset($release['nombre'][0]['total']) ? $release['nombre'][0]['total'] : 0;
        $release = isset($release['nombre'][0]['total']) ? $release['nombre'][0]['total'] : 0;
        $snapshot = isset($snapshot['nombre'][0]['total']) ? $snapshot['nombre'][0]['total'] : 0;

        /** On calcul la valeur pour les autres types de version */
        $lesAutres = $toutesLesVersions - $release - $snapshot;

        return $response->setData([
            'release' => $release, 'snapshot' => $snapshot,  'autre' => $lesAutres,
            'projet' => $infoRelease['version'][0]['projet'],
            'date' => $infoRelease['version'][0]['date'],
        ]);
    }

    /**
     * [Description for batchInformation]
     * Collecte des données pour la table information_projet
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 09/12/2022, 16:42:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchCollecteInformation(Client $client, $mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $informationProjetEntity = $this->em->getRepository(InformationProjet::class);

        /** On forge la requête */
        $url = $this->getParameter(static::$sonarUrl) .
                "/api/project_analyses/search?project=" . $mavenKey;

        /** On appel le client http */
        $result = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));

        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (array_key_exists('code', $result)){
            if ($result['code']===401) {
                return ['code' => 401];
            }
            if ($result['code']===404){
                return ['code' => 404];
            }
        }

        /** On créé un objet date */
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On supprime les informations pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $request=$informationProjetEntity->deleteInformationProjetMavenKey($map);
        if ($request['code']!=200) {
            return ['code' => $request['code'], 'methode'=>'deleteInformationProjetMavenKey'];
        }

        /** On ajoute les informations du projet dans la table information_projet. */
        foreach ($result['analyses'] as $information) {
            /**
             *  La version du projet doit être xxx-release, xxx-snapshot ou xxx
             *  Dans ce cas, le tableau renvoi toujours [0] pour la version et
             *  [1] pour le type de version (release, snaphot ou null)
             */
            $explode = explode('-', $information['projectVersion']);
            if (empty($explode[1])) {
                $explode[1] = 'N.C';
            }
            $map=['maven_key'=>$mavenKey, 'analyse_key'=>$information['key'],
                'date'=>new \DateTime($information['date']),
                'project_version', $information['projectVersion'],
                'type'=>strtoupper($explode[1]),
                'date_enregistrement'=>$date];
        }
        $update=$informationProjetEntity->updateInformationProjet($map);
        if ($update['code']!=200) {
            return ['code' => $update['code'], 'methode'=>'deleteInformationProjetMavenKey'];
        }

        /** On appel la méthode de traitement des données */
        $versions = $this->batchInformationVersion($request, $mavenKey);
        return [ 'code'=>200, 'information' => $versions ];
    }

}
