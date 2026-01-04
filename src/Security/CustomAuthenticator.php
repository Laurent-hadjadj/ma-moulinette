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

namespace App\Security;

use Symfony\Component\HttpFoundation\{Response, Request, RedirectResponse, JsonResponse};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\{AuthenticationException, CustomUserMessageAuthenticationException};
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\{CsrfTokenBadge, RememberMeBadge, UserBadge};
use Symfony\Component\Security\Http\Authenticator\Passport\{Passport, SelfValidatingPassport};
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use App\Service\{UserAgentTrackingFacade, UserAgentAnalysisService};

/**
 * [Description CustomAuthenticator]
 */
class CustomAuthenticator extends AbstractLoginFormAuthenticator
{

    public const LOGIN_ROUTE = 'login';

    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private RouterInterface $router,
        private UserAgentTrackingFacade $tracking
        ) {
    }

    /**
     * [Description for authenticate]
     * Processus d'authentification
     *
     * @param Request $request
     *
     * @return Passport
     *
     * Created at: 31/01/2024 14:57:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function authenticate(Request $request): Passport
    {
        $courriel = strtolower($request->request->get('courriel', ''));
        $passwordRaw = $request->request->get('password', '');

        $passwordNormalized = \Normalizer::normalize($passwordRaw, \Normalizer::FORM_C);

        $utilisateur = $this->utilisateurRepository->findOneBy([
            'courriel' => $courriel,
            'actif' => true,
        ]);

        if (!$utilisateur) {
            throw new CustomUserMessageAuthenticationException('Identifiant invalide ou utilisateur inactif.');
        }
        if ($this->passwordHasher->isPasswordValid($utilisateur, $passwordNormalized)) {
            // OK — rien à faire
        } elseif ($this->passwordHasher->isPasswordValid($utilisateur, $passwordRaw)) {
            // Ancien hash (non normalisé) — on corrige
            $correctHash = $this->passwordHasher->hashPassword($utilisateur, $passwordNormalized);
            $utilisateur->setPassword($correctHash);
            $this->em->flush();

              // Ajout d'un message flash
            $request->getSession()->getFlashBag()->add('notice', [
                'type' => 'success',
                'message' => 'Votre mot de passe a été automatiquement mis à jour pour garantir une meilleure compatibilité.'
            ]);
        } else {
            throw new CustomUserMessageAuthenticationException('Mot de passe incorrect.');
        }
        // On cherche si l'utilisateur existe !
        return new SelfValidatingPassport(
            new UserBadge($courriel, fn() => $utilisateur),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                (new RememberMeBadge())->enable(),
            ]
        );
    }

    /* si le courriel existe et le credential est bon on redirige vers accueil */
    /**
     * [Description for onAuthenticationSuccess]
     * Si le courriel existe et le credential est bon on redirige vers accueil
     * Si l'attribut est égale à 1 on redirige sur la page
     * de changement du mot de passe
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     *
     * @return Response|null
     *
     * Created at: 31/01/2024 14:56:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->tracking->track('LOGGED');
        $user = $token->getUser();
        if ($user instanceof \App\Entity\Utilisateur && $user->isResetPassword()) {
            return new RedirectResponse($this->router->generate('reset_mot_de_passe'));
        }

        return new RedirectResponse($this->router->generate('accueil'));
    }

    /**
     * [Description for getLoginUrl]
     *
     * @param Request $request
     *
     * @return string
     *
     * Created at: 26/09/2025 10:08:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }

    /**
     * [Description for start]
     *
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return Response
     *
     * Created at: 26/09/2025 10:08:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Si requête AJAX / JSON -> renvoyer JSON (pour les front-end fetch/ajax)
        if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json')) {
            return new JsonResponse([
                'x-api-custom-401' => true,
                'code' => 401,
                'message' => 'Votre session a expiré. Veuillez vous reconnecter.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Sinon redirection classique vers la page de login
        $request->getSession()->getFlashBag()->add('notice', [
                'type' => 'warning',
                'message' =>  'Votre session a expiré, veuillez vous reconnecter (Erreur 401).'
        ]);
        return new RedirectResponse($this->getLoginUrl($request));
    }

}
