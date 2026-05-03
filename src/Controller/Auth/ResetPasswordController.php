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

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;
use App\Form\ResetPasswordFormType;
use App\Repository\UtilisateurRepository;
use App\Service\UserAgentTrackingFacade;

/**
 * [Description ResetPasswordController]
 */
class ResetPasswordController extends AbstractController
{
    use AppUserAware;

    /** Définition des constantes */
    private static string $europeParis = "Europe/Paris";
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $noData = 'Pas de données';

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    public function __construct(
        private EntityManagerInterface $em,
        ParameterBagInterface $params,
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $passwordHasher,
        // AUDIT 2026-05 : utilisé par $this->tracking->track('RESET_PASSWORD') dans index() — ne pas retirer même si PhpStan signale "property.onlyWritten"
        private UserAgentTrackingFacade $tracking
    ) {
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
     * @return array<int|string, mixed>
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
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for resetMotDePasse]
     * Validation et lancement du formulaire de réinitialisation du mot de passe
     *
     * @param Request $request
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
        $this->tracking->track('RESET_PASSWORD');

        $utilisateur = $token->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            $this->logger->error('[Reset-Password] 🚫 Utilisateur non valide dans le token.');
            throw new  UserNotFoundException('$user doit être une instance de User.');
        }

        /** On récupère le login de l'utilisateur connecté */
        $courriel = $utilisateur->getCourriel();
         /**  On récupère les valeurs pour resetPassword */
        $resetPasswordCount = $utilisateur->getResetPasswordCount();

        /** On créé un objet DateTime */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));

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
            if ($isValidPassword === false) {
                    /** On verrouille après 5 tentatives */
                    if ($resetPasswordCount >= 5) {
                        return $this->redirectToRoute('logout');
                    }
                    $this->logger->warning("[Reset-Password] ⚠️ Mot de passe incorrect pour $courriel (tentative n°$resetPasswordCount).");

                    /** On prepare un message flash */
                $r = 5 - $resetPasswordCount;
                    $s = ($r === 1) ? '' : 's';
                $message = "Votre mot de passe est incorrect (" . $r . " tentative" . $s . " restante" . $s . ").";
                    $this->addFlash('notice', [
                        'type' => 'warning',
                        'message' => $message
                    ]);

                    /** On incrémente le nombre de tentative */
                    $utilisateur->setResetPasswordCount($resetPasswordCount + 1);
                    $utilisateur->setDateModification($date);
                    $utilisateur->setActif(false);
                    $this->em->flush();

                    $this->logger->info("[Reset-Password] ℹ️ le nombre de tentative a été incrémenté pour [$courriel].");
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
            $message = "📌 Votre mot de passe a été changé avec succès.";
            $this->addFlash('notice', [
                'type' => 'success',
                'message' => $message
            ]);
            return $this->redirectToRoute('accueil');
        }

        $render = $this->genericRender();
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
    #[Route('/api/secure/mot-de-passe/mise-a-jour', name: 'api_reset_mot_de_passe', methods: 'POST')]
    public function apiResetMotDePasse(Request $request): Response
    {
        /** On instancie l'EntityRepository */
        $userEntity = $this->em->getRepository(Utilisateur::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si le body est correcte */
        if ($data === null || !property_exists($data, 'reset_password')) {
            $this->logger->error('[API Reset-Password] ❌ Body invalide dans la requête.', [
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
            }

        $reset_password = $data->reset_password ?? false;

        /** On créé un objet DateTime */
        $date_modification = new \DateTime('now', new \DateTimeZone(self::$europeParis));
        /** on récupère l'adresse mél de l'utilisateur qui fait la demande */
        $courriel = $this->appUser()->getCourriel();

        /** On met à jour la table propriétés */
        $map = [
                'reset_password' => $reset_password,
                'date_modification' => $date_modification,
                'courriel' => $courriel
        ];

        $r = $userEntity->updateUtilisateurResetPassword($map);
        if ($r['code'] != 200) {
            $this->logger->error('[API Reset-Password] ❌ Échec de la requête updateUtilisateurResetPassword.', [
                'code' => $r['code'],
                'erreur' => $r['erreur'] ?? self::$noData,
                'courriel' => $courriel
            ]);
            return new JsonResponse([
                'code' => $r['code'],
                'type' => 'error',
                'message' => "Échec de mise à jour du status de mise à jour du mot de passe (Erreur {$r['code']}).",
                'trace' => $r['erreur'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        $this->logger->info("[API Reset-Password] ℹ️ Succès update pour $courriel.");
        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }
}
