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

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Bundle\SecurityBundle\Security;
use \Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;

/**
 * [Description ChangeMe]
 */
class ChangeMeController extends AbstractController
{
    use AppUserAware;

    /** Définition des constantes */
    private static string $europeParis = "Europe/Paris";
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $erreur403 = "Vous devez avoir le rôle UTILISATEUR pour réaliser cette action (Erreur 403).";
    private static string $loggerE400 = "[Change-Me] ❌ Requête invalide : clé 'avatar' manquante ou JSON mal formé.";
    private static string $loggerE403 = "[Change-Me] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_UTILISATEUR).";
    private static string $noData = 'Pas de données';

    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private LoggerInterface $logger,
    ) {}

    private const RULES = [
        'chiffre'    => 1,
        'emoticon'   => 35,
        'fille-1'    => 27,
        'fille-2'    => 26,
        'garcon-1'   => 23,
        'garcon-02'  => 24,
    ];

    /**
     * [Description for isValid]
     *
     * @param string $avatar
     *
     * @return bool
     *
     * Created at: 10/04/2026 13:45:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function isValid(string $avatar): bool
    {
        if (!preg_match('#^([a-z0-9-]+)/(\d{2})$#i', $avatar, $matches)) {
            return false;
        }

        $folder = $matches[1];
        $number = (int) $matches[2];

        if (!isset(self::RULES[$folder])) {
            return false;
        }

        return $number >= 1 && $number <= self::RULES[$folder];
    }

    /**
     * [Description for resetMotDePasse]
     * Validation et lancement du formulaire de réinitialisation du mot de passe
     *
     * @param Request $request
     * @param TokenInterface $token
     *
     * @return JsonResponse
     *
     * Created at: 01/02/2024 20:07:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/utilisateur/change-me', name: 'user_change_me')]
    public function userChangeMe(Request $request, TokenInterface $token): JsonResponse
    {

        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/information");

        // On décode le body
        $data = json_decode($request->getContent());

        // Vérifie si l'utilisateur a bien le rôle nécessaire
        if (!$this->isGranted('ROLE_UTILISATEUR')) {
            $this->logger->warning(self::$loggerE403, [
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403
            ], Response::HTTP_OK);
        }

        // Vérification de la validité du corps de la requête
        if (!is_object($data) || !property_exists($data, 'avatar') || !is_string($data->avatar)) {
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        $utilisateur = $token->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            $this->logger->error('[Change-Me] 🚫 Utilisateur non valide dans le token.');
            throw new  UserNotFoundException('$user doit être une instance de User.');
        }

        if (!$this->isValid($data->avatar)) {
            $this->logger->error(
                "[Change-Me] ❌ l'URL de l'avatar est incorrecte.",
                [
                    'utilisateur' => $this->security->getUser()?->getUserIdentifier(),
                    'URL' => $data->avatar ?? 'inconnu'
                ]
            );
            throw new \InvalidArgumentException('Avatar invalide');
        }

        /** On créé un objet DateTime */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));
        $utilisateur->setAvatar($data->avatar . '.png');
        $utilisateur->setDateModification($date);
        $this->em->flush();

        $courriel = $this->appUser()->getCourriel() ?? self::$noData;
        $this->logger->info("[Change-Me] ℹ️ mise à jour de l'avatar pour $courriel.");

        return new JsonResponse(
            [
                'code' => 200,
                'message' => "L'avatar a été mise à jour avec succès."
            ],
            Response::HTTP_OK
        );
    }
}
