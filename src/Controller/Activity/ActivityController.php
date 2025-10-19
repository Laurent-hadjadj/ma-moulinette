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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Activity;
use App\Entity\ActivityHistorique;
use App\Service\Client;

/**
 * [Description ActivityController]
 */
class ActivityController extends AbstractController
{

    private static $sonarUrl = "sonar.url";
    private static $page = "activity/index.html.twig";

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
        private Client $client
        )
    {
        $this->params = $params;
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
        $activityRepos = $this->em->getRepository(Activity::class);
        $activityHistoriqueRepos = $this->em->getRepository(ActivityHistorique::class);

        // On récupère l'année actuelle
        $actualDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $actualYear = $actualDate->format('Y');

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

        /** On récupère la dernière task */
        $url = $this->getParameter(static::$sonarUrl);
        $queryParams = ['ps' => 1];
        $result = $this->client->httpActivity("$url/api/ce/activity?".http_build_query($queryParams));

        /** On catch les erreurs HTTP */
        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->addFlash('notice', [
                'type' => 'alert',
                'message' => '❌' . $result['erreur'],
            ]);

            $render = static::genericRender();
            $render['data'] = [$data];
            return $this->render(static::$page, $render);
        }

        /** On vérifie si pour l'année courante, il y des enregistrement ou non  */
        $listeAnalyse=$activityRepos->selectActivity($actualYear)['liste'];
        if (empty($listeAnalyse)) {
            // Ajoute un message flash pour informer l'utilisateur que la liste des analyses est vide pour l'année courante.
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => "⚠️ La liste des analyses SonarQube est vide. Veuillez rafraîchir la liste."
            ]);

            $render = static::genericRender();
            $render['data'] = [$data];
            return $this->render(static::$page, $render);
        }

        /** On prend la date la plus récente en base et la dernière de l'analyse */
        $dateBase = new \DateTime($activityRepos->dernierDate()['liste']['date']);
        $dateSonar = new \DateTime($result['json']['tasks'][0]['executedAt']);

        /** Si SonarQube contient des nouvelles tâches */
        if ($dateSonar > $dateBase){
            // Ici on calcule l'interval de jour entre la base et sonar
            $interval = $dateBase->diff(new \DateTime($result['tasks'][0]['executedAt']))->format('%d');
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => "⚠️ Vous pouvez mettre à jour la liste des analyses SonarQube. Il y a " .$interval. " jours de retard."
            ]);
        }

        /** Si SonarQube ne contient pas de nouvelles tâches */
        if ($dateSonar == $dateBase){
            $this->addFlash('notice', [
                'type' => 'default',
                'message' => "📌 La liste des analyses SonarQube est à jour."
            ]);
        }

        /** On récupère la listes des données statistiques. On suppose que la mise à jour a été faite. */
        $listeHistorique = $activityHistoriqueRepos->selectActivity();
        if (empty($listeHistorique['liste'])){
            $this->addFlash('notice', [
                'type' => 'alert',
                'message' => "❌ L'historique n'a pas correctement été initialisé pour cette année."
            ]);

            $render = static::genericRender();
            $render['data'] = [$data];
            return $this->render(static::$page, $render);
        }

        /** Pour chaque durée d'execution on converti les secondes en h:m:s */
        for ($i = 0; $i < count($listeHistorique['liste']); $i++){
            $maxTime = new \DateTimeImmutable($listeHistorique['liste'][$i]['max_time']);
            $listeHistorique['liste'][$i]['max_time'] = $maxTime->format('H:i:s');
        }
        // On injecte la date d'enregistrement à condition que la mise à jour ait été faite !
        $listeHistorique['liste'][0]["date_enregistrement"] ? (new \DateTime($result['request'][0]["date_enregistrement"]))->format('d-m-Y H:i:s') : (new \DateTime('01/01/1980 00:00:00'))->format('d-m-Y H:i:s');

        $render = static::genericRender();
        return $this->render(static::$page, $render);
    }

}
