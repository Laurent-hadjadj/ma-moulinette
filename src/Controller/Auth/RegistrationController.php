<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;

use App\Form\RegistrationFormType;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Logger */
use Psr\Log\LoggerInterface;

class RegistrationController extends AbstractController
{

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
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): Response {
        // si on est déjà connecté en renvoi vers la page d'accueil !!!
        if ($this->getUser()->getUserIdentifier()) {
            return $this->redirectToRoute('home');
        }

        /** On créé un objet utilisateur. */
        $utilisateur = new Utilisateur();
        /** on prépare le formulaire. */
        $form = $this->createForm(RegistrationFormType::class, $utilisateur);
        /** On récupère la requête. */
        $form->handleRequest($request);

        /** Le formulaire est valide */
        if ($form->isSubmitted() && $form->isValid()) {
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone('Europe/Paris'));

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
            $preferences=
            ["statut"=>["projet"=>false, "favori"=>false, "version"=>false,"bookmark"=>false]];
            $utilisateur->setPreference($preferences);

            /** On enregistre le petit malin dans le pot de miel */
            if (!empty(trim($honeyPot))) {
                // Spam detected!
                $warning = sprintf('🐛 SPAM detected. honeypot content: %s IP: %s', $honeyPot, $request->getClientIp());
                $logger->warning($warning);
            } else {
                $em->persist($utilisateur);
                $em->flush();
            }

            /** Connexion automatique ? */
            /** "return $userAuthenticator->authenticateUser($utilisateur, $authenticator,$request);" */

            /** On préfère rediriger l'utilisateur sur la page de bienvenu des nouveaux utilisateurs */
            return $this->render('welcome/index.html.twig', [
                'nom' => $utilisateur->getNom(),
                'prenom' => $utilisateur->getPrenom(),
                'courriel' => $utilisateur->getCourriel(),
                'marque_entreprise_short' => $this->getParameter('marque.entreprise.short'),
                'marque_entreprise_long' => $this->getParameter('marque.entreprise.long'),
                'logo_entreprise' => $this->getParameter('logo.entreprise'),
                'env' => $this->getParameter('environnement'),
                'version' => $this->getParameter('version'),
                'dateCopyright' => \date('Y'),
                'rgaa' => $this->getParameter('rgaa')
            ]);
        }

        return $this->render('auth/register.html.twig', [
            'registrationForm' => $form->createView(),
            'marque_entreprise_short' => $this->getParameter('marque.entreprise.short'),
            'marque_entreprise_long' => $this->getParameter('marque.entreprise.long'),
            'logo_entreprise' => $this->getParameter('logo.entreprise'),
            'env' => $this->getParameter('environnement'),
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y'),
            'rgaa' => $this->getParameter('rgaa')
        ]);
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
        return $this->render('welcome/index.html.twig', [
            'nom' => 'HADJADJ',
            'prenom' => 'Laurent',
            'courriel' => 'laurent.hadjadj@ma-petite-entreprise.fr',
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y'),
            'marque_entreprise_short' => $this->getParameter('marque.entreprise.short'),
            'marque_entreprise_long' => $this->getParameter('marque.entreprise.long'),
            'logo_entreprise' => $this->getParameter('logo.entreprise'),
            'env' => $this->getParameter('environnement'),
            'rgaa' => $this->getParameter('rgaa')
        ]);
    }

}
