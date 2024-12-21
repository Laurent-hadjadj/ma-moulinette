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

namespace App\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;

use App\Form\RegistrationFormType;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Logger */
use Psr\Log\LoggerInterface;

/**
 * [Description RegistrationController]
 */
class RegistrationController extends AbstractController
{
    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    public function __construct(
        private ParameterBagInterface $params,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
        $this->em = $em;
        $this->logger = $logger;
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
     * Created at: 21/12/2024 21:03:28 (Europe/Paris)
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
     * [Description for register]
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @param EntityManagerInterface $em
     *
     * @return Response
     *
     * Created at: 15/12/2022, 21:07:50 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/register', name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher): Response {
        // si on est déjà connecté en renvoi vers la page d'accueil !!!
        if ($this->getUser()->getUserIdentifier()) {
            return $this->redirectToRoute('accueil');
        }

        /** On créé un objet utilisateur. */
        $utilisateur = new Utilisateur();
        /** on prépare le formulaire. */
        $form = $this->createForm(RegistrationFormType::class, $utilisateur);
        /** On récupère la requête. */
        $form->handleRequest($request);

        /** Le formulaire est valide */
        if ($form->isSubmitted() && $form->isValid()) {
            $date = new \DateTime();
            $date->setTimezone(new \DateTimeZone('Europe/Paris'));

            /** je récupère les données du HoneyPot  */
            $honeyPot = $form->get('email')->getData();
            /** J'enregistre l'url de l'image */
            $avatar = $form->get('avatar')->getData();
            $utilisateur->setAvatar($avatar);

            /** J'enregistre le nom en majuscule */
            $utilisateur->setNom(strtoupper($form->get('nom')->getData()));

            /** J'enregistre le Prénom */
            $utilisateur->setPrenom(ucfirst($form->get('prenom')->getData()));

            /** J'enregistre en base de données */
            $courriel = $form->get('courriel')->getData();
            /** On canonise l'adresse. */
            $utilisateur->setCourriel(strtolower($courriel));

            /** On hash le mot de passe */
            $utilisateur->setPassword(
                $userPasswordHasher->hashPassword(
                    $utilisateur,
                    $form->get('plainPassword')->getData()
                )
            );

            /** On désactive l'utilisateur */
            $utilisateur->setActif(false);

            /** En enregistre la date de création */
            $utilisateur->setDateEnregistrement($date);

            /** On initialise les préférences par défaut */
            $preferences = ['statut' => ['suivi_projet' => false, 'favori_projet' => false,
                            'favori_version' => false,'bookmark'=> false],
                            'suivi_projet' => [], 'favori_projet' => [],'favori_version' => [],
                            'bookmark' =>[]];
            $utilisateur->setPreference($preferences);

            /** On enregistre le petit malin dans le pot de miel */
            if (!empty(trim($honeyPot))) {
                // Spam detected!
                $warning = sprintf('🐛 SPAM detected. honeypot content: %s IP: %s', $honeyPot, $request->getClientIp());
                $this->logger->warning($warning);
            } else {
                $this->em->persist($utilisateur);
                $this->em->flush();
            }

            /** Connexion automatique ? */
            /** "return $userAuthenticator->authenticateUser($utilisateur, $authenticator,$request);" */

            /** On préfère rediriger l'utilisateur sur la page de bienvenu des nouveaux utilisateurs */
            $render=static::genericRender();
            $render['nom'] = $utilisateur->getNom();
            $render['prenom'] = $utilisateur->getPrenom();
            $render['courriel'] = $utilisateur->getCourriel();
            $render['rgaa'] = $this->getParameter('rgaa');
            return $this->render('welcome/index.html.twig', $render);
        }

        $render=static::genericRender();
        $render['registrationForm'] = $form->createView();
        $render['rgaa'] = $this->getParameter('rgaa');
        return $this->render('auth/register.html.twig', $render);
    }

    /**
     * [Description for welcome]
     *
     * @return [type]
     *
     * Created at: 15/12/2022, 21:07:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/welcome', name: 'welcome')]
    public function welcome()
    {
        $render=static::genericRender();
        $render['nom'] = 'HADJADJ';
        $render['prenom'] = 'Laurent';
        $render['courriel'] = 'laurent.hadjadj@ma-petite-entreprise.fr';
        $render['rgaa'] = $this->getParameter('rgaa');
        return $this->render('welcome/index.html.twig', $render);
    }

}
