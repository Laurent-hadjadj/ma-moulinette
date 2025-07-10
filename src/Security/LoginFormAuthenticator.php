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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Doctrine\ORM\EntityManagerInterface;

use App\Repository\UtilisateurRepository;

/**
 * [Description LoginFormAuthenticator]
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'login';

    private UtilisateurRepository $utilisateurRepository;
    private UrlGeneratorInterface $urlGenerator;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $em;

    public function __construct(
        UtilisateurRepository $utilisateurRepository,
        UrlGeneratorInterface $urlGenerator,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->urlGenerator = $urlGenerator;
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
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
            throw new UserNotFoundException('Identifiant invalide ou utilisateur inactif.');
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
                'titre' => 'Mot de passe mis à jour',
                'message' => 'Votre mot de passe a été automatiquement mis à jour pour garantir une meilleure compatibilité.'
            ]);
        } else {
            throw new UserNotFoundException('Mot de passe incorrect.');
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
        (bool) $resetPassword = $token->getUser()->isResetPassword();
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        /** si target n'est pas null,
         * on est déjà connecté, on ne peut pas se reconnecter */
        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }
        /** Ce n'est pas la première connexion ! */
        if ($resetPassword === false){
            return new RedirectResponse($this->urlGenerator->generate('accueil'));
        } else {
            return new RedirectResponse($this->urlGenerator->generate('reset_mot_de_passe'));
        }
    }

    // retourne l'URL de connexion
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

}
