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

namespace App\Controller\Activity;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Activity;
use App\Entity\ActivityHistorique;

use App\Service\Client;
use Symfony\Component\HttpFoundation\Response;

class ActivityController extends AbstractController
{

    public static $sonarUrl = "sonar.url";
    public static $page = "activity/index.html.twig";

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $params,
        private Client $client)
    {
        $this->em = $em;
        $this->client = $client;
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
    }

    /**
     * [Description for genericRender]
     *
     * @return array
     *
     * Created at: 21/12/2024 20:50:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer' => null,
            'logo_entreprise' => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long' => $this->marqueEntrepriseLong,
            'env' => $this->environnement,
            'version' => $this->version,
            'date_copyright' => $this->dateCopyright];
    }

    /**
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/activity', name: 'activity', methods: 'GET')]
    public function index()
    {
        /** On instancie l'EntityRepository */
        $activityEntity = $this->em->getRepository(Activity::class);

        // On récupère l'année actuelle
        $dateMoins1 = new \DateTime();
            $dateMoins1->setTimezone(new \DateTimeZone('Europe/Paris'));
        $actualYear = $dateMoins1->format('Y');

        // Initialise les valeurs par défaut
        $data['year'] = '---';
        $data['day'] = '---';
        $data['analyse'] = '---';
        $data['success'] = '---';
        $data['fail'] = '---';
        $data['max_time'] = '---';
        $data['analyse_average'] = '---';
        $data['success_rate'] = '---';
        $data['date_enregistrement'] = '---';

        $url = $this->getParameter(static::$sonarUrl);
        $queryParams = [];
        $result = $this->client->httpActivity("$url/api/ce/activity".http_build_query($queryParams));

        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            $this->addFlash('notice', [
                'type' => 'alert',
                'titre' => '[ACTIVITÉ]',
                'message' => $result['erreur']
            ]);
            $render = static::genericRender();
            $render['data'] = [$data];
            return $this->render(static::$page, $render);
        }


        /** On appel directement la requête et on récupère le résultat */
        if (empty($activityEntity->selectActivity($actualYear)['request'])) {
            // Ajouter un message flash pour informer l'utilisateur que la liste des analyses est vide
            $this->addFlash('notice', [
                'type' => 'alert',
                'titre' => '[ACTIVITÉ-003]',
                'message' => "La liste des analyses SonarQube est vide. Veuillez rafraîchir la liste"
            ]);

            // Initialiser la variable $request avec des valeurs par défaut
            $request['-'] = [
                'year' => "-",
                'day' => "-",
                'analyse' => "-",
                'success' => "-",
                'fail' => "-",
                'max_time' => "-",
                'analyse_average' => "-",
                'success_rate' => "-"
            ];

            $render=static::genericRender();
            $render['data'] = $request;
            return $this->render(static::$page, $render);
        }

        $dateBase = new \DateTime($activityEntity->dernierDate()['request'][0]['date']);
        $dateSonar = new \DateTime($respond['tasks'][0]['executedAt']);

        if($dateSonar > $dateBase){
            // Ici on calcule l'interval de jour entre la base et sonar
            $interval = $dateBase->diff(new \DateTime($respond['tasks'][0]['executedAt']))->format('%d');
            $this->addFlash('notice', ['type'=>'warning', 'titre'=> '[ACTIVITÉ-002]', 'message'=>"Vous pouvez mettre à jour  La liste des analyses SonarQube. Il y a " .$interval. " jours de retard"]);
        }
        if($dateSonar == $dateBase){
            $this->addFlash('notice', ['type'=>'default', 'titre'=> '[ACTIVITÉ-001]', 'message'=>" La liste des analyses SonarQube est à jour."]);
        }

        $historiqueActivityEntity = $this->em->getRepository(ActivityHistorique::class);

        $result = $historiqueActivityEntity->selectActivity();
        for ($i = 0; $i < count($result['request']); $i++){
            $formatedDate = new \DateTimeImmutable($result['request'][$i]['max_time']);
            $result['request'][$i]['max_time'] = $formatedDate->format('H:i:s');
        }

        $result['request'][0]["date_enregistrement"] = (new \DateTime($result['request'][0]["date_enregistrement"]))->format('d-m-Y H:i:s');

        $render=static::genericRender();
        return $this->render(static::$page, $render);
    }

}
