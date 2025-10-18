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

namespace App\Controller\Owasp;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\OwaspTop10;

/**
 * [Description OwaspController]
 */
class OwaspController extends AbstractController
{

    private static $page = "owasp/index.html.twig";

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

        /**
     * [Description for __construct]
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private ParameterBagInterface $params,
        private EntityManagerInterface $em)
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
     * Created at: 30/10/2024 08:21:04 (Europe/Paris)
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
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:14:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp', name: 'owasp')]
    public function index(): Response
    {
        /** On charge le template du render */
        $render=static::genericRender();

        /** On instancie l'entityRepos */
        $owaspTop10Repos = $this->em->getRepository(OwaspTop10::class);

        /** On récupère les informations du projet de la table historique */
        $map = ['referential_version' => 2017];
        $owasp_2017 = $owaspTop10Repos->selectOwaspTop10Referential($map);
        if ($owasp_2017['code'] != 200) {
            $message = $owasp_2017['erreur'];
            $this->addFlash('notice', [
                'type' => 'alert',
                'message' => $message
            ]);
            return $this->render(static::$page, $render);
        }

        $map=['referential_version' => 2021];
        $owasp_2021=$owaspTop10Repos->selectOwaspTop10Referential($map);
        if ($owasp_2021['code'] != 200) {
            $message = $owasp_2021['erreur'];
            $this->addFlash('notice', [
                'type'=>'alert',
                'message'=>$message
            ]);
            return $this->render(static::$page, $render);
        }

        if (count($owasp_2017['liste']) === 0 && count($owasp_2021['liste']) === 0){
            $message = "Les informations concernant les référentiels OWASP n'ont pas été trouvés.";
            $this->addFlash('notice', [
                'type'=>'warning',
                'message'=>$message
            ]);
        }

        $render['sonar_version'] = $this->getParameter('sonar.version');
        $render['serveur'] = $this->getParameter('sonar.url');
        $render['owasp_2017'] = $owasp_2017;
        $render['owasp_2021'] = $owasp_2021;
        return $this->render(static::$page, $render);
    }

    /**
     * [Description for details]
     *
     * @param int $id
     *
     * @return Response
     *
     * Created at: 21/11/2024 20:16:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp/detail/{id}', name: 'owasp_detail', condition: "params['id'] < 21", methods: ['GET', 'HEAD'])]
    public function details(int $id): Response
    {
        /** On charge le template du render */
        $render = static::genericRender();

        /** On instancie l'entityRepository */
        $owaspTop10Repos = $this->em->getRepository(OwaspTop10::class);

        /** On récupère les informations du projet de la table historique */
        $map = [ 'menace' => $id ];
        $liste=$owaspTop10Repos->selectOwaspTop10Details($map);
        if ($liste['code'] != 200) {
            $message = $liste['erreur'];
            $this->addFlash('notice', [
                'type' => 'alert',
                'message'=>$message
            ]);
            return $this->render(static::$page, $render);
        }
        $render['menace'] = $id;
        $render['owasp'] = $liste['details'][0];
        return $this->render('owasp/detail.html.twig', $render);
    }
}
