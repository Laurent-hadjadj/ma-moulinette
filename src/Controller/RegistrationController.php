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

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Main\Utilisateur;

use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

use DateTime;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $em): Response
    {
        // On créé un objet utilisateur.
        $utilisateur = new Utilisateur();
        // on prépare le formulaire.
        $form = $this->createForm(RegistrationFormType::class, $utilisateur);
        // On récupère la requête.
        $form->handleRequest($request);

        // Le formulaire est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $date = new DateTime();

            // J'enregistre l'url de l'image
            $avatar=$form->get('avatar')->getData();
            $utilisateur->setAvatar($avatar);

            // J'enregistre le nom en majuscule
            $utilisateur->setNom(strtoupper($form->get('nom')->getData()));

            // J'enregistre le Prenom
            $utilisateur->setPrenom(ucfirst($form->get('prenom')->getData()));

            // J'enregistre en base de données
            $courriel=$form->get('courriel')->getData();
            // On canonise l'adresse.
            $utilisateur->setCourriel(strtolower($courriel));

            // On hash le mot de passe
            $utilisateur->setPassword(
                $userPasswordHasher->hashPassword(
                    $utilisateur,
                    $form->get('plainPassword')->getData()
                )
            );

            // On desactive l'utilisateur
            $utilisateur->setActif(FALSE);

            // En enregistre la date de création
            $utilisateur->setDateEnregistrement($date);
            $em->persist($utilisateur);
            $em->flush();

            // Connexion automatique ?
            //return $userAuthenticator->authenticateUser($utilisateur, $authenticator,$request);

            // On préfére redirider l'utilisateur sur la page de bienvenu des nouveaux tiliasteur
            return $this->render('welcome/index.html.twig', [

                'nom'=>$utilisateur->getNom(),
                'prenom'=>$utilisateur->getPrenom(),
                'courriel'=>$utilisateur->getCourriel(),
                'version' => $this->getParameter('version'),
                'dateCopyright' => \date('Y')
            ]);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ]);
    }

    #[Route('/welcome', name: 'welcome')]
    public function welcome()
    {
        return $this->render('welcome/index.html.twig', [
            'nom' =>'HADJADJ',
            'prenom' =>'Laurent',
            'courriel' =>'laurent.hadjadj@ma-petite-entreprise.fr',
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ]);
    }

}
