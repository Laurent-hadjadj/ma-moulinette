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

namespace App\Controller\Repartition;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

use App\Controller\Batch\BatchCollecteRepartitionController;
use App\Entity\Repartition;
use App\Service\ExtractName;


/**
 * [Description RepartitionController]
 */
class RepartitionController extends AbstractController
{

    private static $titre = "[Répartition-Module]";
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static $page = 'projet/repartition-module.html.twig';

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:32:06 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ExtractName $serviceExtractName,
        private ParameterBagInterface $params,
        private BatchCollecteRepartitionController $batchCollecteRepartition)
    {
        $this->em = $em;
        $this->serviceExtractName = $serviceExtractName;
        $this->params = $params;
        $this->batchCollecteRepartition = $batchCollecteRepartition;

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
            'date_copyright' => $this->dateCopyright];
    }

    private function addFlashAndRender(string $type, string $message, string $debug, array $render): Response
    {
        $this->addFlash('notice', ['type' => $type, 'titre' => static::$titre,
        'message' => $message, 'debug' => $debug] );
        return $this->render(static::$page, $render);
    }

    /**
     * [Description for decodeToken]
     *
     * @param string $token
     *
     * @return string|null
     *
     * Created at: 14/02/2025 12:20:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeToken(string $token): ?string
    {
        //token=BGR2ZQL5ZQLjA3kzpv5gLF1go3IfnJ5yqUEyBzkyYJAbLKD=
        //b64=OTE2MDY5MDYwN3xmci5tYS1tb3VsaW5ldHRlOmxlLWNoYXQ=
        //rot13=BGR2ZQL5ZQLjA3kzpv5gLF1go3IfnJ5yqUEyBzkyYJAbLKD=
        $string = str_rot13($token);
        $decoded = base64_decode($string);
        $parts = preg_split("/[|]+/", $decoded);
        return (count($parts) === 2) ? strtolower($parts[1]) : null;
    }

    /**
     * [Description for projetRepartition]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:32:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/repartition', name: 'repartition', methods: ['GET'])]
    public function repartition(Security $security, Request $request): Response
    {
        // Instanciation des repositories
        $repartitionRepository = $this->em->getRepository(Repartition::class);

        /** On charge le template du render */
        $render = static::genericRender();
        $render['mon_application'] = 'N.C';
        $render['maven_key'] = 'N.C';
        $render['setup'] = 'N.C';
        $render['statut'] = 'N.C';
        $render['information'] = [
            'BUG' => ['total' => 0, 'blocker' => 0 , 'critical' => 0 , 'major' => 0, 'minor' => 0, 'info' => 0],
            'VULNERABILITY' => ['total' => 0, 'blocker' => 0 , 'critical' => 0 , 'major' => 0, 'minor' => 0, 'info' => 0 ],
            'CODE_SMELL' => ['total' => 0, 'blocker' => 0 , 'critical' => 0 , 'major' => 0, 'minor' => 0, 'info' => 0 ]
        ];
        $token = $request->get('token');
        $debug = '';

        /** On teste si la clé est valide */
        if (empty($token)) {
            return $this->addFlashAndRender('alert', static::$erreur400, $debug, $render);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $this->addFlashAndRender('warning', static::$erreur403, $debug, $render);
        }

        /** On récupère la maven_key du token */
        $mavenKey = $this->decodeToken($token);
        if (null === $mavenKey) {
            return $this->addFlashAndRender('alert', static::$erreur400, $debug, $render);
        }

        /** On lance la collecte des informations par type et sévérité */
        $categories = ['BUG', 'VULNERABILITY', 'CODE_SMELL'];
        $information = [];

        foreach ($categories as $category) {
            $information[$category] = $this->batchCollecteRepartition->CollecteRepartitionModule($mavenKey, $category);
            if ($information[$category]['code'] != 200){
                return $this->addFlashAndRender('alert', "La collecte des données SonarQube à échouée.", $debug, $render);
            }
        }


        /** On enregistre le nom du projet */
        $app = $this->serviceExtractName->extractNameFromMavenKey($mavenKey);

        /** On calcul une valeur pour le setup */
        $setup = (int) round(microtime(true) * 1000);

        /** on enregistre les informations dans la table repartition */
        $map = [
            'maven_key' => $mavenKey,
            'name' => $app,
            'setup' => $setup,

            // Initialisation des valeurs des bugs
            'bug_blocker' => $information['BUG']['blocker'] ?? 0,
            'bug_critical' => $information['BUG']['critical'] ?? 0,
            'bug_major' => $information['BUG']['major'] ?? 0,
            'bug_minor' => $information['BUG']['minor'] ?? 0,
            'bug_info' => $information['BUG']['info'] ?? 0,

            // Initialisation des valeurs des vulnérabilités
            'vulnerability_blocker' => $information['VULNERABILITY']['blocker'] ?? 0,
            'vulnerability_critical' => $information['VULNERABILITY']['critical'] ?? 0,
            'vulnerability_major' => $information['VULNERABILITY']['major'] ?? 0,
            'vulnerability_minor' => $information['VULNERABILITY']['minor'] ?? 0,
            'vulnerability_info' => $information['VULNERABILITY']['info'] ?? 0,

            // Initialisation des valeurs des code smells
            'code_smell_blocker' => $information['CODE_SMELL']['blocker'] ?? 0,
            'code_smell_critical' => $information['CODE_SMELL']['critical'] ?? 0,
            'code_smell_major' => $information['CODE_SMELL']['major'] ?? 0,
            'code_smell_minor' => $information['CODE_SMELL']['minor'] ?? 0,
            'code_smell_info' => $information['CODE_SMELL']['info'] ?? 0,

            'mode_collecte' => 'COLLECTE',
            'utilisateur_collecte' => $security->getUser()->getCourriel(),
            'date_enregistrement' => new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'))
        ];

        $repartition = $repartitionRepository->selectOrUpdateRepartitionInitial($map);
        if ($repartition['code'] != 200) {
            return $this->addFlashAndRender('alert', "L'enregistrement des données initiales a échouées.", $repartition['erreur'], $render);
        }

        // Mise à jour du rendu
        $render['mon_application'] = $app;
        $render['maven_key'] = $mavenKey;
        $render['setup'] = $setup;
        $render['statut'] = 'initial';
        $render['information'] = $information;
        return $this->render(static::$page, $render);
    }
}
