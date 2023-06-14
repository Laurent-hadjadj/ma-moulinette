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

namespace App\Security;

use App\Repository\Main\UtilisateurRepository;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

//https://github.com/NouvelleTechno/e-commerce-Symfony-6/blob/main/src/Security/UsersAuthenticator.php

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'login';

    private UtilisateurRepository $utilisateurRepository;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        UtilisateurRepository $utilisateurRepository,
        UrlGeneratorInterface $urlGenerator)
    {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->urlGenerator = $urlGenerator;
    }

    public function authenticate(Request $request): Passport
    {
        $courriel = $request->request->get('courriel', '');
        $motDePasse = $request->request->get('password', '');

        $request->getSession()->set(Security::LAST_USERNAME, $courriel);

        // On cherche si l'utilisateur existe !
        return new Passport(
            /**
             * Si l'utilisateur n'existe pas on génére une exception.
             * Si l'utilisateur existe mais que son ststut est 'FALSE',
             *  i.e. l'attribut 'actif', on génére une excption.
             * Si l'utilisateur existe et son statut est à "TRUE" et
             *  que le mot de passe est correcte :
             * 1 - On ajoute le support CSRF ;
             * 2 - On ajoute le support de "Remember-me" ;
             */

            new UserBadge($courriel, function($utilisateurIdentifier)
            {
               //'actif => TRUE
                $utilisateur = $this->utilisateurRepository
                    ->findOneBy(['courriel' => $utilisateurIdentifier, 'actif'=>true]);
                    if (!$utilisateur) {
                    throw new UserNotFoundException();
                }
                return $utilisateur;
            }),
                new PasswordCredentials($motDePasse),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                (new RememberMeBadge())->enable(),
            ]
        );
    }

    // si le courriel existe et le credential est bon on redirige vers home
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('home'));
    }

    // retourne l'URL de connexion
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

}
