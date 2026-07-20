<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Admin;

use App\Controller\Traits\AppUserAware;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\{RoleManagerService, UserRoleLoggerService};
use App\Security\SuspiciousActivityDetector;
use Symfony\Component\Asset\Packages;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Crud, Filters};
use EasyCorp\Bundle\EasyAdminBundle\Field\{FormField, TextField, EmailField, AvatarField, ChoiceField, BooleanField, DateTimeField};
use Psr\Log\LoggerInterface;

/**
 * @extends AbstractCrudController<Utilisateur>
 */
/* MODIF 2026-07-20 : restriction serveur manquante — seule la carte du
 * dashboard (home.html.twig, is_granted('ROLE_GESTIONNAIRE')) filtrait
 * l'accès. */
#[IsGranted('ROLE_GESTIONNAIRE', message: 'Vous ne disposez pas des droits suffisants pour accéder à cette page.', statusCode: 403)]
class UtilisateurCrudController extends AbstractCrudController
{
    use AppUserAware;

    private static string $europeParis = 'Europe/Paris';
    // MODIF 2026-05-26 : extraction des string literals dupliqués (S1192).
    private const ROLE_LABEL_AUCUN_ACCES = 'Aucun accès';

    /**
     * [Description for __construct]
     *
     * Created at: 02/01/2023, 18:37:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $emm,
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $passwordHasher,
        private RoleManagerService $roleManager,
        private UserRoleLoggerService $roleLogger,
        private SuspiciousActivityDetector $detector,
        private Packages $assets,
    ) {
    }

    /**
     * [Description for getEntityFqcn]
     *
     * @return string
     *
     * Created at: 02/01/2023, 18:37:28 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
    }

    /**
     * [Description for configureFilters]
     *
     * @param Filters $filters
     *
     * @return Filters
     *
     * Created at: 02/01/2023, 18:37:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('courriel');
    }

    /**
     * [Description for configureCrud]
     *
     * @param Crud $crud
     *
     * @return Crud
     *
     * Created at: 06/04/2026 18:36:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des utilisateurs')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un utilisateur')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un utilisateur')
            ->setPageTitle(Crud::PAGE_DETAIL, "Détails de l'utilisateur");
    }

    /**
     * [Description for configureFields]
     *
     * @param string $pageName
     *
     * @return iterable
     *
     * Created at: 02/01/2023, 18:37:34 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFields(string $pageName): iterable
    {
        // mapping des rôles vers les classes de badge Bootstrap
        $badges = [
                'ROLE_NONE' => 'secondary',
                'ROLE_UTILISATEUR' => 'primary',
                'ROLE_COLLECTE' => 'success',
                'ROLE_SUIVI' => 'warning',
                'ROLE_BATCH' => 'warning',
                'ROLE_ACTUATOR' => 'warning',
                'ROLE_ACTIVITY' => 'info',
                'ROLE_SECURITY' => 'danger',
                'ROLE_GESTIONNAIRE' => 'danger',
                'ROLE_INTERNAL' => 'dark',
            ];

        // liste complète des rôles pour les formulaires (filtrée ensuite selon le rôle de l’éditeur et de l’utilisateur édité)
        $allRoles = [
            self::ROLE_LABEL_AUCUN_ACCES => 'ROLE_NONE',
            'Utilisateur' => 'ROLE_UTILISATEUR',
            'Collecte' => 'ROLE_COLLECTE',
            'Suivi' => 'ROLE_SUIVI',
            'Activité' => 'ROLE_ACTIVITY',
            'Batch' => 'ROLE_BATCH',
            'Actuator' => 'ROLE_ACTUATOR',
            'Sécurité' => 'ROLE_SECURITY',
            'Sécu. Analytics' => 'ROLE_SECURITY_ANALYTICS',
            'Gestionnaire' => 'ROLE_GESTIONNAIRE',
            'Interne' => 'ROLE_INTERNAL',
        ];

        $entityInstance = null;
        $context = $this->getContext();
        // on récupère l’entité en cours d’édition pour adapter dynamiquement les champs en fonction de son état et du rôle de l’éditeur
        if ($context) {
                $entityInstance = $context->getEntity()->getInstance();
        }

        yield FormField::addColumn(6);
        yield AvatarField::new('avatar')
            ->formatValue(
                /**
                 * Asset Mapper + Packages -> respecte le base path Symfony
                 * (donc le X-Forwarded-Prefix injecte par le reverse proxy en prod).
                 */
                function ($callback, ?Utilisateur $utilisateur) {
                    $this->logger->debug('UtilisateurCrudController::configureFields - avatar - formatValue', [
                        'utilisateur' => $utilisateur?->getCourriel(),
                        'avatar' => $utilisateur?->getAvatar(),
                        'callback' => $callback,]);
                    return $this->assets->getUrl('avatar/' . ($utilisateur?->getAvatar() ?? 'personne.png'));
                })
            ->setFormTypeOption('disabled',  in_array($pageName, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT], true))
            ->hideOnForm();

        /* MODIF 2026-07-20 : 'disabled' retiré — champ calculé (getPersonne()),
         * hideOnForm() le retire déjà des formulaires Créer/Modifier dans tous
         * les cas, l'option précédente était donc morte et trompeuse. */
        yield TextField::new('personne')
            ->hideOnForm();

        yield TextField::new('prenom')
            ->hideOnIndex();

        yield TextField::new('nom')
            ->hideOnIndex();

        yield EmailField::new('courriel')
            ->setFormTypeOption('disabled', $pageName === Crud::PAGE_EDIT);

        // rôle de l’éditeur
        if ($this->isGranted('ROLE_INTERNAL')) {
            $assignableRoles = $allRoles;
        } elseif ($this->isGranted('ROLE_GESTIONNAIRE')) {
            $assignableRoles = [
                self::ROLE_LABEL_AUCUN_ACCES => 'ROLE_NONE',
                'Utilisateur' => 'ROLE_UTILISATEUR',
                'Collecte' => 'ROLE_COLLECTE',
                'Suivi' => 'ROLE_SUIVI',
                'Sécurité' => 'ROLE_SECURITY',
            ];

            // Si on édite un gestionnaire → on garde visible son rôle
            if ($entityInstance && in_array('ROLE_GESTIONNAIRE', $entityInstance->getRoles(), true)) {
                $assignableRoles['Gestionnaire'] = 'ROLE_GESTIONNAIRE';
            }
        } else {
            $assignableRoles = [self::ROLE_LABEL_AUCUN_ACCES => 'ROLE_NONE'];
        }

        // rôle de l’utilisateur édité
        $currentRoles = $entityInstance ? $entityInstance->getRoles() : [];

        // si l’utilisateur n’a pas de rôle ou que son rôle est ROLE_NONE, on affiche uniquement ROLE_UTILISATEUR pour éviter les incohérences
        if ($currentRoles === ['ROLE_NONE']) {
            $assignableRoles['Utilisateur'] = 'ROLE_UTILISATEUR';
        }

        $helper_role = 'Accès restreint.';
        if ($this->isGranted('ROLE_INTERNAL')) {
            $helper_role = 'Accès complet';
        }

        if ($this->isGranted('ROLE_GESTIONNAIRE')) {
            $helper_role = 'Accès limité aux rôles standards';
        }

        // affichage en badges colorés sur la page d’index, formulaire avec cases à cocher pour l’édition/création
        yield ChoiceField::new('roles')
            ->onlyOnIndex()
            ->setSortable(false)
            ->setChoices($allRoles)
            ->setFormTypeOption('choice_translation_domain', false)
            ->renderAsBadges($badges);

        yield ChoiceField::new('roles')
            ->onlyOnForms()
            ->setChoices($assignableRoles)
            ->allowMultipleChoices()
            ->renderExpanded()
            ->setFormTypeOption('choice_attr', function ($choice, $key, $value) use ($entityInstance) {
                $currentRoles = $entityInstance->getRoles();

                /** Empêcher ROLE_NONE si actif */
                if (
                    $entityInstance->isActif() &&
                    $value === 'ROLE_NONE'
                ) {
                    return ['disabled' => 'disabled'];
                }

                /** empêcher suppression ROLE_GESTIONNAIRE */
                if (
                    in_array('ROLE_GESTIONNAIRE', $currentRoles, true) &&
                    $value === 'ROLE_GESTIONNAIRE'
                ) {
                    return ['disabled' => 'disabled'];
                }

                /** empêcher modification ROLE_INTERNAL */
                if (
                    in_array('ROLE_INTERNAL', $currentRoles, true)
                ) {
                    return ['disabled' => 'disabled'];
                }

                return [];
            })
            ->setFormTypeOption('choice_translation_domain', false)
            ->setHelp($helper_role);

        yield BooleanField::new('actif')->renderAsSwitch(false);

        /** On récupère la liste des groupes utilisateurs */
        $sql = "SELECT groupe_utilisateur, description FROM ma_moulinette.groupe_utilisateur ORDER BY groupe_utilisateur ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $result = $l->fetchAllAssociative();

        /** si la table est vide */
        if (empty($result)) {
            $result = [["groupe_utilisateur" => "En attente", "description" => "Utilisateur en attente d’affectation."]];
        }
        $key = [];
        $val = [];

        foreach($result as $value) {
            array_push($key, $value['groupe_utilisateur']." - ".$value['description']);
            array_push($val, $value['groupe_utilisateur']);
        }

        yield FormField::addColumn(6);
        yield ChoiceField::new('groupeUtilisateur')
            ->setChoices(array_combine($key, $val))
            ->setFormTypeOption('choice_translation_domain', false)
            ->setHelp('Sélectionne le groupe utilisateur.');

        /** On récupère la liste des groupes fonctionnels */
        $sql = "SELECT groupe_fonctionnel, description FROM ma_moulinette.groupe_fonctionnel ORDER BY groupe_fonctionnel ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $result = $l->fetchAllAssociative();

        $choices = [];

        foreach ($result as $value) {
            $description = $value['description'];

            if (mb_strlen($description, 'UTF-8') > 18) {
                $description = mb_substr($description, 0, 18, 'UTF-8') . '...';
            }

            $label = $value['groupe_fonctionnel'] . ' - ' . $description;
            $choices[$label] = $value['groupe_fonctionnel'];
        }

        yield ChoiceField::new('listeGroupeFonctionnel')
            ->setLabel('Groupe fonctionnel')
            ->setChoices($choices)
            ->allowMultipleChoices()
            ->setFormTypeOption('choice_translation_domain', false)
            ->setFormTypeOption('placeholder', 'Choisissez un groupe fonctionnel')
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Sélectionne le groupe fonctionnel.');

        yield DateTimeField::new('dateModification')
            ->setTimezone(self::$europeParis)
            ->hideOnForm();
        yield DateTimeField::new('dateEnregistrement')
            ->setTimezone(self::$europeParis)
            ->hideOnForm();
    }

    /**
     * [Description for persistEntity]
     *
     * @param EntityManagerInterface $em
     *
     * @return void
     *
     * Created at: 05/04/2026 16:13:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        /* MODIF 2026-05-07 : guard instanceof. */
        // @phpstan-ignore-next-line instanceof.alwaysTrue (garde défensive EasyAdmin)
        if (!$entityInstance instanceof Utilisateur) {
            return;
        }

        $entityInstance->setAvatar('personne.png');
        $entityInstance->setResetPassword(false);

        $entityInstance->setPassword(
            $this->passwordHasher->hashPassword(
                $entityInstance,
                bin2hex(random_bytes(32))
            )
        );

        $roles = $this->roleManager->normalize(
            $entityInstance->getRoles(),
            [], // nouveau user = pas d'ancien rôle
            $entityInstance,
            $this->appUser()
        );

        $entityInstance->setRoles($roles);
        $groupe_utilisateur = $entityInstance->getGroupeUtilisateur();

        $conn = $this->emm->getConnection();
        $result = $conn->fetchOne(
            "SELECT groupe_id FROM ma_moulinette.groupe_utilisateur WHERE groupe_utilisateur = :groupe LIMIT 1",
            ['groupe' => $groupe_utilisateur]
        );

        $entityInstance->setGroupeId($result);

        $entityInstance->setListeGroupeFonctionnel(
            $entityInstance->getListeGroupeFonctionnel()
        );

        $entityInstance->setDateModification(
            new \DateTime('now', new \DateTimeZone(self::$europeParis))
        );

        parent::persistEntity($em, $entityInstance);
    }

    /**
     * [Description for updateEntity]
     *
     * @param EntityManagerInterface $em
     *
     * @return void
     *
     * Created at: 02/01/2023, 18:37:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        /* MODIF 2026-05-07 : guard instanceof. */
        // @phpstan-ignore-next-line instanceof.alwaysTrue (garde défensive EasyAdmin)
        if (!$entityInstance instanceof Utilisateur) {
            return;
        }

        $conn = $this->emm->getConnection();

        /** Récupération sécurisée */
        // On récupère les rôles actuels de l’utilisateur depuis la base de données pour éviter les manipulations malveillantes
        $result = $conn->fetchOne(
            "SELECT roles FROM ma_moulinette.utilisateur WHERE courriel = :courriel LIMIT 1",
            ['courriel' => $entityInstance->getCourriel()]
        );

        // Si l’utilisateur n’existe pas ou n’a pas de rôles, on considère qu’il n’a que ROLE_NONE
        $currentRoles = json_decode($result, true) ?: [];

        $oldRoles = $currentRoles;
        $oldActive = $entityInstance->isActif();

        /** NORMALISATION CENTRALISÉE */
        $newRoles = $this->roleManager->normalize(
            $entityInstance->getRoles(),
            $currentRoles,
            $entityInstance,
            $this->appUser()
        );

        // Analyse des changements de rôles pour détecter les activités suspectes
        $alerts = $this->detector->analyze($entityInstance, $this->appUser(), $currentRoles, $newRoles);

        $newActive = $entityInstance->isActif();

        // si les rôles ou le statut actif ont changé, on log l’événement avant de mettre à jour l’entité pour conserver les anciennes valeurs dans le log
        if ($oldRoles !== $newRoles || $oldActive !== $newActive) {
            $this->roleLogger->log(
            $entityInstance,
            $this->appUser(),
            $oldRoles,
            $newRoles,
            $oldActive,
            $newActive,
            $alerts);
        }

        $entityInstance->setRoles($newRoles);
        $groupe_utilisateur = $entityInstance->getGroupeUtilisateur();

        // On récupère le groupe_id à partir du groupe_utilisateur
        $groupeId = $conn->fetchOne(
            "SELECT groupe_id FROM ma_moulinette.groupe_utilisateur WHERE groupe_utilisateur = :groupe LIMIT 1",
            ['groupe' => $groupe_utilisateur]
        );
        $entityInstance->setGroupeId($groupeId);

        // si aucun groupe utilisateur n’est sélectionné, on met "Aucun" par défaut pour éviter les incohérences
        if (empty($entityInstance->getGroupeUtilisateur())) {
            $entityInstance->setGroupeUtilisateur('Aucun');
        }
        // si  aucun groupe fonctionnel n'est sélectionné, on met une liste vide par défaut pour éviter les incohérences
        $entityInstance->setListeGroupeFonctionnel(
            $entityInstance->getListeGroupeFonctionnel()
        );

        // On met à jour la date de modification à chaque mise à jour de l’entité
        $entityInstance->setDateModification(
            new \DateTime('now', new \DateTimeZone(self::$europeParis))
        );

        parent::updateEntity($em, $entityInstance);
    }
}
