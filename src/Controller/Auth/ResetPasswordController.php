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
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Main\UtilisateurRepository;
use App\Entity\main\Utilisateur;

use App\Form\ChangePasswordFormType;
use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * [Description ResetPasswordController]
 */
class ResetPasswordController extends AbstractController
{
    /** Définition des constantes */
    public static $europeParis = "Europe/Paris";

    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private EntityManagerInterface $em,
        private UrlGeneratorInterface $urlGenerator,
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
    }


    /**
     * [Description for reset]
     * Validation et lancement du formulaire de réinitialisation du mot de passe
     * Avec un token ou à la première connexion
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param string|null $token
     *
     * @return Response
     *
     * Created at: 01/02/2024 20:07:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/reset/mot-de-passe', name: 'reset_mot_de_passe')]
    public function resetMotDePasse(Request $request, UserPasswordHasherInterface $passwordHasher, TokenInterface $token): Response
    {
        /** on récupère le login de l'utilisateur connecté */
        // intelephense(P1006) faux positif.
        $courriel=$token->getUser()->getCourriel();
         /**  On récupère la valeur de init */
        $init=$token->getUser()->getInit();

        /** On récupère les données de l'utilisateur connecté */
        $utilisateur = $this->utilisateurRepository->findOneBy(['courriel' => $courriel]);
        if (!$utilisateur || null == $utilisateur) {
                throw new UserNotFoundException('L\'utilisateur n\'existe pas');
            }

        /** On créé un objet DateTime */
        $date = new \DateTime();
        $timezone = new \DateTimeZone(static::$europeParis);
        $date->setTimezone($timezone);

        /**
         * Le mot de passe actuel de l'utilisateur est valide,
         * on l'autorise à changer son mot de passe.
         */
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        /** on récupère l'ancien mot de passe et on le vérifie */
        $ancienMotDePasse=$form->get('ancienMotDePasse')->getData();
        $isValid=$passwordHasher->isPasswordValid($utilisateur, $ancienMotDePasse);

        /** Si l'ancien mot de passe est incorrecte */
        if ($isValid===false){
            /** On vérouille après 5 tentatives */
            if ($init>5) {
                return $this->redirectToRoute('logout');
            }

            /** On prepare un message flash */
            $this->addFlash('warning', sprintf(
                '%s - %s', "[Erreur 001] : ","Votre mot de passe est incorrect."
            ));

            /** On incremente le nombre de tentative */
            $utilisateur->setInit($init+1);
            $utilisateur->setDateModification($date);
            $this->em->flush();
            return $this->redirectToRoute('reset_mot_de_passe');
        }
            /** On repasse le statut init à 0 */
            $utilisateur->setInit(0);
            $this->em->flush();

            // Encode(hash) the plain password, and set it.
            $encodedPassword = $passwordHasher->hashPassword(
                $utilisateur,$form->get('plainPassword')->getData()
            );

            $utilisateur->setPassword($encodedPassword);
            $this->em->flush();
            /** On prepare un message flash */
            $this->addFlash('success', sprintf(
                '%s - %s', "[Info 001] : ","Votre mot de passe a été changé."
            ));
            return $this->redirectToRoute('reset_mot_de_passe');
        }

        return $this->render('auth/reset.html.twig', [
            'changePasswordForm' => $form->createView(),
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ]);
    }

}
