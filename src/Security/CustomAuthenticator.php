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
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Exception\{ExceptionInterface, InvalidCredentialsException};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\{AuthenticationException, CustomUserMessageAuthenticationException};
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\{Passport, SelfValidatingPassport, Badge\UserBadge};
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Routing\RouterInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Utilisateur, GroupeUtilisateur};
use App\Repository\UtilisateurRepository;
use Symfony\Component\Uid\NilUlid;

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
        private LoggerInterface $logger,
        private Ldap $ldap,
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
        // Soit on saisie une adresse mail, soit un UPN (ex: prenom.nom)

        $login = strtolower(trim($request->request->get('login', '')));
        $passwordRaw = $request->request->get('password', '');

        $passwordNormalized = \Normalizer::normalize($passwordRaw, \Normalizer::FORM_C);

        // Normalisation du login
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $courriel = $login;
            $samAccountName = strstr($login, '@', true);
        } else {
            $samAccountName = $login;
            $courriel = $login . ($_ENV['LDAP_UPN_SUFFIX'] ?? '@ma-moulinette.fr');
        }

        $this->logger->info('[AUTH] ℹ️ Login normalisé', [
            'input' => $login,
            'email' => $courriel,
            'sam' => $samAccountName
        ]);

        $utilisateur = $this->utilisateurRepository->findOneBy([
            'courriel' => $courriel,
            'actif' => true,
        ]);

        // ======================= Auth local =======================
        if ($utilisateur) {
            if ($this->passwordHasher->isPasswordValid($utilisateur, $passwordNormalized)) {
                // mot de passe OK
                return $this->createPassport($login, $utilisateur, $request);
            }

            if ($this->passwordHasher->isPasswordValid($utilisateur, $passwordRaw)) {
                // ancien hash non normalisé → mise à jour
                $correctHash = $this->passwordHasher->hashPassword($utilisateur, $passwordNormalized);
                $utilisateur->setPassword($correctHash);
                $this->em->flush();

                $request->getSession()->getFlashBag()->add('notice', [
                    'type' => 'success',
                    'message' => 'Votre mot de passe a été automatiquement mis à jour.'
                ]);

                return $this->createPassport($login, $utilisateur, $request);
            }

            // mot de passe local incorrect → fallback LDAP
        }

        // ======================= Auth LDAP =======================
        try {

            // Bind direct via UPN
            // Utiliser le courriel comme DN pour le bind LDAP
            $userDn = $courriel;

            $this->ldap->bind($userDn, $passwordRaw);

            // Bind technique pour recherche
            $this->ldap->bind(
                $_ENV['LDAP_BIND_DN'] ?? '',
                $_ENV['LDAP_BIND_PASSWORD'] ?? ''
            );

            // Recherche utilisateur
            $query = $this->ldap->query(
                $_ENV['LDAP_BASE_DN'] ?? '',
                sprintf('(&(objectClass=user)(sAMAccountName=%s))', $login)
            );

            $filter = sprintf(
                '(&(objectClass=user)(|(sAMAccountName=%s)(mail=%s)))',
                $this->ldap->escape($samAccountName, '', Ldap::ESCAPE_FILTER),
                $this->ldap->escape($courriel, '', Ldap::ESCAPE_FILTER)
            );
            $query = $this->ldap->query(
                $_ENV['LDAP_BASE_DN'] ?? '',
                $filter
            );
            $result = $query->execute();

            if (empty($result) || count($result) === 0) {
                $this->logger->warning('[LDAP] 👻 Utilisateur LDAP introuvable.', [
                    'user' => $userDn,
                    'query' => $result ?? 'Aucune données remontées.',
                ]);
                throw new CustomUserMessageAuthenticationException('Identifiant ou mot de passe incorrect.');
            }

            // renvoie le DN : CN=NOM Prénom,OU=UTILISATEURS,...
            $entry = $result[0];
            $nom = $entry->getAttribute('sn')[0] ?? $login;
            $prenom = $entry->getAttribute('givenName')[0] ?? '';
            // Par défaut, on attribue un rôle minimal. L'administrateur pourra ensuite attribuer les rôles appropriés.
            $roles = ['ROLE_NONE'];

            // --------------------
            // Auto-provision / comptes existants inactifs
            // --------------------
            $utilisateurExist = $this->utilisateurRepository->findOneBy(['courriel' => $courriel]);
            if ($utilisateurExist) {
                if (!$utilisateurExist->isActif()) {
                    $this->logger->info("[LDAP] ℹ️ Utilisateur existant mais inactif : {$courriel}");
                    throw new CustomUserMessageAuthenticationException(
                        "Votre compte existe mais n’est pas encore habilité. Veuillez contacter l’administrateur."
                    );
                }

                // Utilisateur existant et actif → connexion LDAP OK
                return $this->createPassport($courriel, $utilisateurExist, $request);
            }

            // --------------------
            // Création auto-provision LDAP
            // --------------------
            $utilisateur = new Utilisateur();
            $utilisateur->setAvatar('personne.png');
            $utilisateur->setCourriel($courriel);
            $utilisateur->setNom($nom);
            $utilisateur->setPrenom($prenom);
            $utilisateur->setRoles($roles);
            $utilisateur->setGroupeUtilisateur('En attente');
            $preference = '{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false,"bookmark":false}, "suivi_projet":[],"favori_projet":[],"favori_version":[]}';
            $utilisateur->setPreference(json_decode($preference, true));
            $utilisateur->setActif(false); // non habilité
            $utilisateur->setResetPassword(false);
            $utilisateur->setPassword(
                $this->passwordHasher->hashPassword($utilisateur, bin2hex(random_bytes(32)))
            );
            $utilisateur->setDateEnregistrement(new \DateTimeImmutable());

            // Groupe par défaut
            $groupeDefault = $this->em->getRepository(GroupeUtilisateur::class)
                ->findOneBy(['groupeId' => '00000000000000000000000000']);
            if (!$groupeDefault) {
                throw new CustomUserMessageAuthenticationException(
                    'Le groupe par défaut "Utilisateur en attente" est introuvable.'
                );
            }
            $utilisateur->setGroupeId(new NilUlid()); // Générer un nouvel ULID pour le groupeId

            $this->em->persist($utilisateur);
            $this->em->flush();

            $this->logger->info("[LDAP] ℹ️ Utilisateur LDAP provisionné mais non habilité", [
                'user' => $courriel,
                'roles' => $roles
            ]);

            throw new CustomUserMessageAuthenticationException(
                "Votre compte a été créé mais n’est pas encore autorisé. Veuillez contacter l’administrateur."
            );
        } catch (InvalidCredentialsException $e) {
            $this->logger->info("[LDAP] ⚠️ Identifiant ou mot de passe incorrect : {$courriel}");
            throw new CustomUserMessageAuthenticationException(
                '[InvalidCredentialsException] Identifiant ou mot de passe incorrect.'
            );

        } catch (ExceptionInterface $e) {
            $this->logger->warning("[LDAP] 🔴 Erreur LDAP : " . $e->getMessage(), [
                'user' => $courriel
            ]);
            throw new CustomUserMessageAuthenticationException(
                "Impossible de se connecter à l'annuaire d'entreprise."
            );
        }
    }

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
        $user = $token->getUser();
        if ($user instanceof \App\Entity\Utilisateur && $user->isResetPassword()) {
            return new RedirectResponse($this->router->generate('reset_mot_de_passe'));
        }

        return new RedirectResponse($this->router->generate('accueil'));
    }

    /**
     * [Description for onAuthenticationFailure]
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response
     *
     * Created at: 11/02/2026 16:26:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function onAuthenticationFailure(
    Request $request,
    AuthenticationException $exception
    ): Response {
        $this->logger->warning("[AuthenticationException] ⚠️ Échec d'authentification pour " .     $request->request->get('courriel'), [
            'error' => $exception->getMessage()
        ]);

        // 1. Stocker dans la session (clé Symfony)
            $request->getSession()->set(
                SecurityRequestAttributes::AUTHENTICATION_ERROR,
                $exception
            );

            // 2. AUSSI dans les attributs de la requête
            $request->attributes->set(
                SecurityRequestAttributes::AUTHENTICATION_ERROR,
                $exception
            );

        return new RedirectResponse($this->getLoginUrl($request));
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

        return new RedirectResponse($this->getLoginUrl($request));
    }

    /**
     * [Description for createPassport]
     *
     * @param string $courriel
     * @param Utilisateur $user
     * @param Request $request
     *
     * @return Passport
     *
     * Created at: 11/02/2026 15:56:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function createPassport(string $courriel, Utilisateur $user, Request $request): Passport
    {
        return new SelfValidatingPassport(
            new UserBadge($courriel, fn() => $user),
            [new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token'))]
        );
    }
}
