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
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $authenticator, EntityManagerInterface $em): Response
    {
        // On créé un objet utilisateur.
        $utilisateur = new Utilisateur();
        // on prépare le formulaire.
        $form = $this->createForm(RegistrationFormType::class, $utilisateur);
        // On récupère la requête.
        $form->handleRequest($request);

        // le formulaire est valide
        if ($form->isSubmitted() && $form->isValid()) {

            $date = new DateTime();

            #j'enregistre en base de données
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

            $utilisateur->setDateEnregistrement($date);

            $em->persist($utilisateur);
            $em->flush();

            return $userAuthenticator->authenticateUser(
                $utilisateur,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')

        ]);
    }
}
