<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Utilisateur;
use App\Form\ResetPasswordFormType;
use App\Repository\UtilisateurRepository;

/**
 * [Description ResetPasswordController]
 */
class ResetPasswordController extends AbstractController
{
    /** Définition des constantes */
    public static $europeParis = "Europe/Paris";
    public static $reference = "<strong>[Auth]</strong> ";
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
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->em = $em;
        $this->params = $params;
        $this->passwordHasher = $passwordHasher;

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
    public function resetMotDePasse(Request $request, TokenInterface $token): Response
    {
        $utilisateur = $token->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            $this->logger->error('❌ [Reset-Password] Utilisateur non valide dans le token');
            throw new  UserNotFoundException('$user doit être une instance de User.');
        }

        /** On récupère le login de l'utilisateur connecté */
        $courriel = $token->getUser()->getCourriel();
         /**  On récupère les valeurs pour resetPassword */
        $resetPasswordCount = $token->getUser()->getResetPasswordCount();

        /** On créé un objet DateTime */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /**
         * Le mot de passe actuel de l'utilisateur est valide,
         * on l'autorise à changer son mot de passe.
         */
        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** on récupère l'ancien mot de passe et on le vérifie */
            $ancienMotDePasse = $form->get('ancienMotDePasse')->getData();
            $isValidPassword = $this->passwordHasher->isPasswordValid($utilisateur, $ancienMotDePasse);

                /** Si l'ancien mot de passe est incorrecte */
                if ($isValidPassword === false){
                    /** On verrouille après 5 tentatives */
                    if ($resetPasswordCount >= 5) {
                        return $this->redirectToRoute('logout');
                    }
                    $this->logger->warning("⚠️ [Reset-Password] Mot de passe incorrect pour $courriel (tentative n°$resetPasswordCount)");

                    /** On prepare un message flash */
                    $titre = "Oups !!! ";
                    $r = 5-$resetPasswordCount;
                    $s = ($r === 1) ? '' : 's';
                    $message = "Votre mot de passe est incorrect (".$r." tentative".$s." restante".$s.").";
                    $this->addFlash('notice', ['type' => 'warning', 'titre' => $titre, 'message' => $message]);

                    /** On incrémente le nombre de tentative */
                    $utilisateur->setResetPasswordCount($resetPasswordCount + 1);
                    $utilisateur->setDateModification($date);
                    $utilisateur->setActif(false);
                    $this->em->flush();

                    $this->logger->info("ℹ️ [Reset-Password] le nombre de tentative a été incrémenté $courriel");
                    return $this->redirectToRoute('reset_mot_de_passe');
                }

            // Encode(hash) le mot de passe en clair.
            $plainPassword = \Normalizer::normalize($form->get('plainPassword')->getData(), \Normalizer::FORM_C);
            $encodedPassword = $this->passwordHasher->hashPassword($utilisateur, $plainPassword);

            // Mises à jour supplémentaires
            $utilisateur->setResetpassword(false);
            $utilisateur->setResetpasswordCount(1);
            $utilisateur->setPassword($encodedPassword);
            $utilisateur->setDateModification($date);
            $this->em->flush();

            /** On prepare un message flash */
            $titre = "[Auth] ";
            $message = "Votre mot de passe a été changé avec succès.";
            $this->addFlash('notice', ['type' => 'primary', 'titre' => $titre, 'message' => $message]);
            return $this->redirectToRoute('accueil');
        }

        $render = static::genericRender();
        $render['resetPasswordForm'] = $form->createView();
        $render['courriel'] = $courriel;
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
    public function apiResetMotDePasse(Request $request): Response
    {
        /** On instancie l'EntityRepository */
        $userEntity = $this->em->getRepository(Utilisateur::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On créé on objet de response HTTP */
        $response = new JsonResponse();

        /** On teste si le body est correcte */
        if ($data === null || !property_exists($data, 'reset_password')) {
            $this->logger->warning('⚠️ [API Reset-Password] Body invalide dans la requête');
            return $response->setData([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$reference.static::$erreur400,
                'trace' => null], Response::HTTP_OK);
            }

        /** On récupère le filtre de recherche */
        $data = json_decode($request->getContent());
        $reset_password = $data->reset_password ?? (bool) false;

        /** On créé un objet DateTime */
        $date_modification = new \DateTime('now', new \DateTimeZone(static::$europeParis));
        /** on récupère l'adresse mél de l'utilisateur qui fait la demande */
        $courriel = $this->getUser()->getCourriel();

        /** On met à jour la table propriétés */
        $map = [
                'reset_password' => $reset_password,
                'date_modification' => $date_modification,
                'courriel' => $courriel
        ];

        $r = $userEntity->updateUtilisateurResetPassword($map);
        if ($r['code'] != 200) {
            $this->logger->error("❌ [API Reset-Password] Erreur update BDD pour $courriel Erreur({$r['erreur']}.");
            return $response->setData([
                'code' => $r['code'],
                'type' => 'alert',
                'message' => static::$reference . `Échec de mise à jour du status de mise à jour du mot de passe (Erreur {$r['erreur']}).`,
                'trace' => $r['erreur']], Response::HTTP_OK);
        }

        $this->logger->info("ℹ️ [API Reset-Password] Succès update pour $courriel");
        return $response->setData(['code' => 200], Response::HTTP_OK);
    }
}
