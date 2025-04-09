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
use App\Entity\Owasp;
use App\Entity\InformationProjet;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteOwaspController]
 */
class BatchCollecteOwaspController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $erreur404 = "Je n'ai pas trouvé le projet dans l'application (Erreur 404).";

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
     * [Description for BatchCollecteOwasp]
     *
     * @param string $mavenKey
     * @param string $modeCollecteur
     * @param string $utilisateurCollecteur
     *
     * @return array
     *
     * Created at: 21/05/2024 23:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteOwasp(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        /** On instancie l'EntityRepository */
        $informationProjet = $this->em->getRepository(InformationProjet::class);
        $owaspRepository = $this->em->getRepository(Owasp::class);

        /** On contrôle la variable mavenKey */
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');

        /** On récupère la version du serveur SonarQube */
        $sonar_version = $this->getParameter('sonar.version');

        /** Tableau des paramètres pour la requête HTTP */
        $queryParamsList = [
            'owasp2017' => ['componentKeys' => $maven_key, 'facets' => 'owaspTop10', 'owaspTop10' => 'a1,a2,a3,a4,a5,a6,a7,a8,a9,a10'],
            'owasp2021' => ['componentKeys' => $maven_key, 'facets' => 'owaspTop10-2021', 'owaspTop10-2021' => 'a1,a2,a3,a4,a5,a6,a7,a8,a9,a10'],
        ];

        /** On construit l'URL */
        $url = $this->getParameter(static::$sonarUrl);

        /** On appelle les requêtes HTTP pour chaque référentiel */
        $owasp2017 = $this->client->httpSonarQube("$url/api/issues/search?".http_build_query($queryParamsList['owasp2017']));
        /** Il ne peut pas y avoir de 404, l'API renvoie toujours une response 200*/
        if (isset($owasp2017['code']) && in_array($owasp2017['code'], [401, 403, 404, 500, 503])) {
            return ['code' => $owasp2017['code'], $owasp2017['erreur']];
        }

        /** On execute si la version de SonarQube est >= 9 */
        $owasp2021 = ['NC'];
        if ((int) $sonar_version > 8){
            $owasp2021 = $this->client->httpSonarQube("$url/api/issues/search?".http_build_query($queryParamsList['owasp2021']));
            if (isset($owasp2021['code']) && in_array($owasp2021['code'], [401, 403, 404, 500, 503])) {
            return ['code' => $owasp2021['code'], 'erreur' => $owasp2021['erreur']];
            }
        }

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map = ['maven_key' => $maven_key];
        $select_information = $informationProjet->selectInformationProjetProjectVersion($map);
            if ($select_information['code'] != 200) {
            return ['code' => $select_information['code'], 'message' => $select_information['erreur']];
        }

        /** Il n'y a pas de projet dans la table ou la collecte des informations du projet a planté ! */
        if (!$select_information['info']) {
            return ['code' => 404, 'message' => static::$erreur404];
        }

        /** On reconstruit les dates au format dateTime */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));
        $date_version = new \DateTimeImmutable($select_information['info'][0]['date'], new \DateTimeZone(static::$europeParis));

        $prepareOwaspData = function($referential) use ($maven_key, $date_version, $date, $select_information, $mode_collecte, $utilisateur_collecte) {
            /** On initialise un tableau avec comme valeur 0 */
            $nombre = array_fill(1, 10, 0);
            $nombre[0] = $referential['total'];
            $effort_total = $referential['effortTotal'];

            /** Pour chaque signalement OWASP a1, a2, a3,... */
            $total = 0;
            foreach ($referential['facets'][0]['values'] as $value) {
                $index = substr($value['val'], 1);
                $nombre[$index] = $value['count'];
                $total += $value['count']; // Ajoute cette valeur au total
            }

            /** On remplie le tableau pour les signalement a1 à a10 pour les clés de sévérité */
            $owaspIssues = array_fill_keys(range(1, 10), array_fill_keys(['blocker', 'critical', 'major', 'info', 'minor'], 0));

            /** Calcul du nombre d'issue par type de signalement OWASP et par type de sévérité */
            if ($referential['total'] != 0) {
                foreach ($referential['json']['issues'] as $issue) {
                    if (in_array($issue['status'], ['OPEN', 'CONFIRMED', 'REOPENED'])) {
                        foreach ($issue['tags'] as $tag) {
                            if (preg_match("/owasp-a(\d+)/", $tag, $matches)) {
                                $owaspIndex = (int)$matches[1];
                                $severity = strtolower($issue['severity']);
                                if (isset($owaspIssues[$owaspIndex][$severity])) {
                                    $owaspIssues[$owaspIndex][$severity]++;
                                }
                            }
                        }
                    }
                }
            }

            $map = [
                    'total' => $total,
                    'maven_key' => $maven_key,
                    'version' => $select_information['info'][0]['project_version'] ?? '0.0.0-SNAPSHOT',
                    'date_version' => $date_version,
                    'effort_total' => $effort_total,
                    'mode_collecte' => $mode_collecte,
                    'utilisateur_collecte' => $utilisateur_collecte,
                    'date_enregistrement' => $date
            ];

            /** On ajoute les valeurs de a1 à a10 */
            for ($i = 1; $i <= 10; $i++) {
                $map["a$i"] = $nombre[$i];
            }

            /** Ajoute le nombre de cas par gravité pour chaque catégorie OWASP */
            foreach ($owaspIssues as $index => $severities) {
                foreach ($severities as $severity => $count) {
                    $map["a{$index}_{$severity}"] = $count;
                }
            }
            /** On renvoi la collection pour le référentiel OWASP */
            return $map;
        };

        $owaspDataList = [];
        /* pour chaque référentiel 2017/2021 */
        if (array_key_exists('total', $owasp2017['json'])) {
            $owaspDataList[] = $prepareOwaspData($owasp2017['json']);
            $owaspDataList[0]['referential_owasp'] = 2017;
            $total_2017 = $owaspDataList[0]['total'];
        }

        $total_2021 = 'NC';
        /** $owasp2021 = ['NC'] on a pas de données pour le référentiel 2021 */
        if (array_key_exists('total', $owasp2021)) {
            $owaspDataList[] = $prepareOwaspData($owasp2021);
            $owaspDataList[0]['referential_owasp'] = 2021;
            $total_2021=$owaspDataList[0]['total'];
        }

        /** On supprime les informations sur le projet pour la dernière analyse. */
        $map = ['maven_key' => $maven_key];
        $delete = $owaspRepository->deleteOwaspMavenKey($map);
        if ($delete['code'] != 200) {
            return ['code' => $delete['code'], 'erreur' => $delete['erreur']];
        }

        /** On enregistre */
        $insert = $owaspRepository->insertOwasp($owaspDataList);
        if ($insert['code'] != 200) {
            return ['code' => $insert['code'], 'erreur' => $insert['erreur']];
        }

        return ['code' => 200, 'owasp2017' => $total_2017, 'owasp2021' => $total_2021,'message' => ['nombre_2017' => $total_2017, 'nombre_2021' => $total_2021, 'data' => $owaspDataList]];
    }
}
