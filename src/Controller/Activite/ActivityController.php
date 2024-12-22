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

use App\Entity\Activity;
use App\Entity\ActivityHistorique;
use App\Service\Client;

use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class ActivityController extends AbstractController
{

    public static $sonarUrl = "sonar.url";

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
        private ParameterBagInterface $params)
    {
        $this->em = $em;
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
    public function index(Client $client): Response
    {
        /** On instancie l'EntityRepository */
        $activityEntity = $this->em->getRepository(Activity::class);

        // On récupère l'année actuelle
        $dateMoins1 = new \DateTime();
            $dateMoins1->setTimezone(new \DateTimeZone('Europe/Paris'));
        $actualYear = $dateMoins1->format('Y');

        $url = $this->getParameter(static::$sonarUrl)
        . "/api/ce/activity";
        $respond = $client->http($url);

        if ($activityEntity->selectActivity($actualYear)['request'] == [] ){
            $this->addFlash('notice', ['type'=>'alert', 'titre'=> '[ACTIVITÉ-003]', 'message'=>" Votre La liste des analyses SonarQube est vide. Veuillez rafraîchir la liste"]);
            $request["-"] = [
                'year' => "-",
                'day' => "-",
                'analyse' => "-",
                'success' => "-",
                'fail' => "-",
                'max_time' => "-",
                'analyse_average' => "-",
                'success_rate' => "-"
            ];
            return $this->render('activity/index.html.twig', [
                'marque_entreprise_short' => $this->getParameter('marque.entreprise.short'),
                'marque_entreprise_long' => $this->getParameter('marque.entreprise.long'),
                'logo_entreprise' => $this->getParameter('logo.entreprise'),
                'env' => $this->getParameter('environnement'),
                'data' => $request,'version' => $this->getParameter('version'),
                'dateCopyright' => \date("Y"),
                Response::HTTP_OK]);
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
        return $this->render('activity/index.html.twig', $render);
    }

}
