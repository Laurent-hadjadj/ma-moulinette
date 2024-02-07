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

/** Symfony Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/** Accès aux tables SLQLite*/
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Main\UtilisateurRepository;

/** API */
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

use App\Form\ResetPasswordFormType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * [Description ResetPasswordController]
 */
class ResetPasswordController extends AbstractController
{
    /** Définition des constantes */
    public static $europeParis = "Europe/Paris";
    public static $regex = "/\s+/u";
    public static $dateFormat = "Y-m-d H:i:s";

    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private EntityManagerInterface $em,
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->em = $em;
    }


    /**
     * [Description for resetMotDePasse]
     * Validation et lancement du formulaire de réinitialisation du mot de passe
     * si init>0
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param TokenInterface $token
     *
     * @return Response
     *
     * Created at: 01/02/2024 20:07:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/mot-de-passe/mise-a-jour', name: 'reset_mot_de_passe')]
    public function resetMotDePasse(Request $request, UserPasswordHasherInterface $passwordHasher, TokenInterface $token): Response
    {
        /** on récupère le login de l'utilisateur connecté */
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
        $form = $this->createForm(ResetPasswordFormType::class);
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
                '%s : %s', "[Erreur 001]","Votre mot de passe est incorrect."
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
                '%s : %s', "[Info 001]","Votre mot de passe a été changé."
            ));
            return $this->redirectToRoute('reset_mot_de_passe');
        }

        return $this->render('auth/reset.html.twig', [
            'resetPasswordForm' => $form->createView(),
            'courriel'=>$courriel,
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ]);
    }

    /**
     * [Description for apiResetMotDePasse]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 07/02/2024 12:11:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/mot-de-passe/mise-a-jour', name: 'api_reset_mot_de_passe', methods:'POST')]
    public function apiResetMotDePasse(Request $request): Response
    {
        /** On créé on objet de reponse HTTP */
        $response = new JsonResponse();

        /** On récupère le filtre de recherche */
        $data = json_decode($request->getContent());
        $mode = $data->mode;
        $init = $data->init;

        /** On créé un objet DateTime */
        $date = new DateTime();
        $timezone = new DateTimeZone(static::$europeParis);
        $date->setTimezone($timezone);
        $formatDate = $date->format(static::$dateFormat);

        /** on récupère l'adresse mél de l'utilisateur qui fait la demande */
        $courriel = $this->getUser()->getCourriel();

        /** On met à jour les préférences. */
        $sql = "UPDATE utilisateur
        SET init = '$init', date_modification='$formatDate'
        WHERE courriel='$courriel';";
        $trim = trim(preg_replace(static::$regex, " ", $sql));
        $exec = $this->em->getConnection()->prepare($trim)->executeQuery();

        if ($mode !== 'TEST') {
            $exec->fetchAllAssociative();
        }

        $data = ['mode' => $mode,Response::HTTP_OK];
        return $response->setData($data);
    }
}
