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

/** Symfony Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/** Accès aux tables SLQLite*/
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use App\Entity\Utilisateur;

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
    public static $dateFormat = "Y-m-d H:i:s";
    public static $reference = '<strong>[Auth]</strong>';
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private EntityManagerInterface $em,
        private ParameterBagInterface $params,
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
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
     * Created at: 21/12/2024 21:16:12 (Europe/Paris)
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
    public function resetMotDePasse(Request $request,
        UserPasswordHasherInterface $passwordHasher, TokenInterface $token): Response
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
            /** On verrouille après 5 tentatives */
            if ($init>5) {
                return $this->redirectToRoute('logout');
            }

            /** On prepare un message flash */
            $this->addFlash('warning', sprintf(
                '%s : %s', "[Erreur 001]","Votre mot de passe est incorrect."
            ));

            /** On incrémente le nombre de tentative */
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
                '%s : %s', "[AUTH]","Votre mot de passe a été changé."
            ));
            return $this->redirectToRoute('reset_mot_de_passe');
        }

        $render=static::genericRender();
        $render['resetPasswordForm'] = $form->createView();
        $render['courriel'] = 'laurent.hadjadj@ma-petite-entreprise.fr';
        return $this->render('auth/reset.html.twig', $render);
    }

    /**
     * [Description for apiResetMotDePasse]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 07/02/2024 12:11:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/mot-de-passe/mise-a-jour', name: 'api_reset_mot_de_passe', methods:'POST')]
    public function apiResetMotDePasse(Request $request): JsonResponse
    {
        /** On instancie l'EntityRepository */
        $utilisateurEntity = $this->em->getRepository(Utilisateur::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si le body est correcte */
        if ($data === null || !property_exists($data, 'init')) {
            return new JsonResponse(
                    ['data'=>$data,'code'=>400, 'reference'=>static::$reference,
                    'message'=>static::$erreur400,
                    'type'=>'alert'], Response::HTTP_OK);
            }

        /** On récupère le filtre de recherche */
        $data = json_decode($request->getContent());
        $init = $data->init;

        /** On créé un objet DateTime */
        $date = new DateTime();
        $timezone = new DateTimeZone(static::$europeParis);
        $date->setTimezone($timezone);
        $dateModification = $date->format(static::$dateFormat);

        /** on récupère l'adresse mél de l'utilisateur qui fait la demande */
        $courriel = $this->getUser()->getCourriel();

        /** On met à jour la table propriétés */
        $map=[ 'init'=>$init, 'date_modification'=>$dateModification,
                'courriel'=>$courriel ];
        $r=$utilisateurEntity->updateUtilisateurResetPassword($map);
        if ($r['code']!=200) {
            return new JsonResponse([
                'type' => 'alert',
                'reference' => static::$reference, 'code' => $r['code'],
                'message'=>$r['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse(['code'=>200], Response::HTTP_OK);
    }
}
