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

namespace App\Controller\Activite;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Activite;
use App\Entity\ActiviteHistorique;
use App\Service\ClientActivite;

use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class ActiviteController extends AbstractController
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
    #[Route('/activite', name: 'activite', methods: 'GET')]
    public function index(ClientActivite $client): Response
    {
        /** On instancie l'EntityRepository */
        $activiteEntity = $this->em->getRepository(Activite::class);

        // On récupère l'année actuelle
        $dateMoins1 = new \DateTime();
            $dateMoins1->setTimezone(new \DateTimeZone('Europe/Paris'));
        $anneeActuelle = $dateMoins1->format('Y');

        $url = $this->getParameter(static::$sonarUrl)
        . "/api/ce/activity";
        $respond = $client->http($url);

        if ($activiteEntity->selectActivite($anneeActuelle)['request'] == [] ){
            $this->addFlash('notice', ['type'=>'alert', 'titre'=> '[ACTIVITE-003]', 'message'=>" Votre La liste des analyses sonarQube est vide. Veuillez rafraîchir la liste"]);
            $request["-"] = [
                'annee' => "-",
                'nb_jour' => "-",
                'nb_analyse' => "-",
                'nb_reussi' => "-",
                'nb_echec' => "-",
                'max_temps' => "-",
                'moyenne_analyse' => "-",
                'taux_reussite' => "-"
            ];
            return $this->render('activite/index.html.twig', [
                'marque_entreprise_short' => $this->getParameter('marque.entreprise.short'),
                'marque_entreprise_long' => $this->getParameter('marque.entreprise.long'),
                'logo_entreprise' => $this->getParameter('logo.entreprise'),
                'env' => $this->getParameter('environnement'),
                'data' => $request,'version' => $this->getParameter('version'),
                'dateCopyright' => \date("Y"),
                Response::HTTP_OK]);
        }

        $dateBase = new \DateTime($activiteEntity->dernierDate()['request'][0]['date']);
        $dateSonar = new \DateTime($respond['tasks'][0]['executedAt']);

        if($dateSonar > $dateBase){
            // Ici on calcule l'interval de jour entre la base et sonar
            $interval = $dateBase->diff(new \DateTime($respond['tasks'][0]['executedAt']))->format('%d');
            $this->addFlash('notice', ['type'=>'warning', 'titre'=> '[ACTIVITE-002]', 'message'=>"Vous pouvez mettre à jour  La liste des analyses SonarQube. Il y a " .$interval. " jours de retard"]);
        }
        if($dateSonar == $dateBase){
            $this->addFlash('notice', ['type'=>'default', 'titre'=> '[ACTIVITE-001]', 'message'=>" La liste des analyses SonarQube est à jour."]);
        }

        $historiqueActiviteEntity = $this->em->getRepository(ActiviteHistorique::class);

        $result = $historiqueActiviteEntity->selectActivite();
        for ($i = 0; $i < count($result['request']); $i++){
            $formatedDate = new \DateTimeImmutable($result['request'][$i]['max_temps']);
            $result['request'][$i]['max_temps'] = $formatedDate->format('H:i:s');
        }

        $result['request'][0]["date_enregistrement"] = (new \DateTime($result['request'][0]["date_enregistrement"]))->format('d-m-Y H:i:s');

        $render=static::genericRender();
        return $this->render('activite/index.html.twig', $render);
    }

}
