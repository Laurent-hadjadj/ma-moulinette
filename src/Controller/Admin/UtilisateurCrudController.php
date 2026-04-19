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

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Crud, Filters};
use EasyCorp\Bundle\EasyAdminBundle\Field\{FormField, TextField, EmailField, AvatarField, ChoiceField, BooleanField, DateTimeField};
use Psr\Log\LoggerInterface;

/**
 * [Description UtilisateurCrudController]
 */
class UtilisateurCrudController extends AbstractCrudController
{
    private static $europeParis = 'Europe/Paris';

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
        private UserPasswordHasherInterface $passwordHasher)
    {
        $this->emm = $emm;
        $this->logger = $logger;
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
        /** @var mixed $_callback Variable non utilisée */
        yield FormField::addColumn(6);
        yield AvatarField::new('avatar')
            /** @var mixed $value Variable non utilisée */
            ->formatValue(
                function ($callback, ?Utilisateur $utilisateur) {
                    $this->logger->debug('UtilisateurCrudController::configureFields - avatar - formatValue', [
                        'utilisateur' => $utilisateur?->getCourriel(),
                        'avatarUrl' => $utilisateur?->getAvatarUrl(),
                        'callback' => $callback,]);
                    return '/assets'.$utilisateur?->getAvatarUrl() ?? '/assets/avatar/personne.png';
                })
            ->setFormTypeOption('disabled',  in_array($pageName, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT], true))
            ->hideOnForm();

        yield TextField::new('personne')
            ->setFormTypeOption('disabled',  in_array($pageName, [Crud::PAGE_EDIT], true))
            ->hideOnForm();

        yield TextField::new('prenom')
            ->hideOnIndex();

        yield TextField::new('nom')
            ->hideOnIndex();

        yield EmailField::new('courriel')
            ->setFormTypeOption('disabled', $pageName === Crud::PAGE_EDIT);

        yield BooleanField::new('actif')->renderAsSwitch(false);

        // tous les rôles possibles
        $allRoles = [
            'Aucun accès' => 'ROLE_NONE',
            'Utilisateur' => 'ROLE_UTILISATEUR',
            'Collecte' => 'ROLE_COLLECTE',
            'Suivi' => 'ROLE_SUIVI',
            'Activité' => 'ROLE_ACTIVITY',
            'Batch' => 'ROLE_BATCH',
            'Actuator' => 'ROLE_ACTUATOR',
            'Gestionnaire' => 'ROLE_GESTIONNAIRE',
            'Interne' => 'ROLE_INTERNAL',
        ];

        // rôle de l’éditeur
        if ($this->isGranted('ROLE_INTERNAL')) {
            $assignableRoles = $allRoles;
        } elseif ($this->isGranted('ROLE_GESTIONNAIRE')) {
            $assignableRoles = [
                'Aucun accès' => 'ROLE_NONE',
                'Utilisateur' => 'ROLE_UTILISATEUR',
                'Collecte' => 'ROLE_COLLECTE',
                'Suivi' => 'ROLE_SUIVI',
            ];
        } else {
            $assignableRoles = ['Aucun accès' => 'ROLE_NONE'];
        }

        $entityInstance = null;
        $context = $this->getContext();
        if ($context) {
            $entityInstance = $context->getEntity()->getInstance();
        }

        // rôle de l’utilisateur édité
        $currentRoles = $entityInstance ? $entityInstance->getRoles() : [];

        // masquer ROLE_UTILISATEUR si déjà implicite
        if (count(array_intersect(['ROLE_COLLECTE','ROLE_BATCH','ROLE_ACTUATOR','ROLE_GESTIONNAIRE','ROLE_INTERNAL'], $currentRoles)) > 0) {
            unset($assignableRoles['Utilisateur']);
        }

        // si utilisateur n’a que ROLE_NONE, on doit pouvoir le promouvoir
        if ($currentRoles === ['ROLE_NONE']) {
            $assignableRoles['Utilisateur'] = 'ROLE_UTILISATEUR'; // réactiver explicitement
        }

        $badges = [
                'ROLE_NONE' => 'secondary',
                'ROLE_UTILISATEUR' => 'primary',
                'ROLE_COLLECTE' => 'success',
                'ROLE_SUIVI' => 'warning',
                'ROLE_BATCH' => 'warning',
                'ROLE_ACTUATOR' => 'info',
                'ROLE_ACTIVITY' => 'info',
                'ROLE_GESTIONNAIRE' => 'danger',
                'ROLE_INTERNAL' => 'dark',
            ];

        yield FormField::addColumn(6);
        yield ChoiceField::new('roles')
            ->onlyOnIndex()
            ->setSortable(false)
            ->setChoices($assignableRoles)
            ->renderAsBadges($badges);

        yield ChoiceField::new('roles')
            ->onlyOnForms()
            ->setChoices($assignableRoles)
            ->allowMultipleChoices()
            ->renderExpanded();

        /** On récupère la liste des groupes fonctionnels */
        $sql = "SELECT groupe_utilisateur, description FROM groupe_utilisateur ORDER BY groupe_utilisateur ASC";
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

        yield ChoiceField::new('groupeUtilisateur')
            ->setChoices(array_combine($key, $val))
            ->setHelp('Sélectionne le groupe utilisateur.');

        yield TextField::new('groupeId')
        ->hideOnForm();

        yield DateTimeField::new('dateModification')
            ->setTimezone(static::$europeParis)
            ->hideOnForm();
        yield DateTimeField::new('dateEnregistrement')
            ->setTimezone(static::$europeParis)
            ->hideOnForm();
    }

    /**
     * [Description for persistEntity]
     *
     * @param EntityManagerInterface $em
     * @param mixed $entityInstance
     *
     * @return void
     *
     * Created at: 05/04/2026 16:13:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Utilisateur) {
            return;
        }

        $entityInstance->setAvatar('personne.png');
        $entityInstance->setResetPassword(false);
        $entityInstance->setPassword(
            $this->passwordHasher->hashPassword($entityInstance, bin2hex(random_bytes(32)))
        );

        /** On récupère le groupe_id à partir du groupe_utilisateur */
        $groupe_utilisateur = $entityInstance->getGroupeUtilisateur();
        $sql = "SELECT groupe_id FROM groupe_utilisateur WHERE groupe_utilisateur = '$groupe_utilisateur' limit 1";
        $conn = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $result = $conn->fetchOne();
        $entityInstance->setGroupeId($result);

        // Si l'utilisateur n'a pas le rôle ROLE_GESTIONNAIRE, il ne peut pas attribuer de rôles sensibles
        if (!$this->isGranted('ROLE_GESTIONNAIRE')) {
            $entityInstance->setRoles(['ROLE_UTILISATEUR']);
        }

        // Si l'utilisateur a le rôle ROLE_INTERNAL, il ne peut pas avoir d'autres rôles
        $roles = $entityInstance->getRoles();
        if (in_array('ROLE_INTERNAL', $roles, true) && count($roles) > 1) {
            $entityInstance->setRoles(['ROLE_INTERNAL']);
        }

        // Si l'utilisateur a le rôle ROLE_NONE, il ne peut pas avoir d'autres rôles
        if (in_array('ROLE_NONE', $roles, true)) {
            $entityInstance->setRoles(['ROLE_NONE']);
        }

        $entityInstance->setDateModification(new \DateTime('now', new \DateTimeZone(static::$europeParis)));

        parent::persistEntity($em, $entityInstance);
    }

    /**
     * [Description for updateEntity]
     *
     * @param EntityManagerInterface $em
     * @param mixed $entityInstance
     *
     * @return void
     *
     * Created at: 02/01/2023, 18:37:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Utilisateur) {
            return;
        }

        // Si l'utilisateur n'a pas le rôle ROLE_GESTIONNAIRE, il ne peut pas attribuer de rôles sensibles
        if (!$this->isGranted('ROLE_GESTIONNAIRE')) {
            $entityInstance->setRoles(['ROLE_UTILISATEUR']);
        }

        // Si l'utilisateur a le rôle ROLE_INTERNAL, il ne peut pas avoir d'autres rôles
        $roles = $entityInstance->getRoles();
        if (in_array('ROLE_INTERNAL', $roles, true) && count($roles) > 1) {
            $entityInstance->setRoles(['ROLE_INTERNAL']);
        }
        // Si l'utilisateur a le rôle ROLE_NONE, il ne peut pas avoir d'autres rôles
        if (in_array('ROLE_NONE', $roles, true)) {
            $entityInstance->setRoles(['ROLE_NONE']);
        }

        /** On récupère le groupe_id à partir du groupe_utilisateur */
        $groupe_utilisateur = $entityInstance->getGroupeUtilisateur();
        $sql = "SELECT groupe_id FROM groupe_utilisateur WHERE groupe_utilisateur = '$groupe_utilisateur' limit 1";
        $conn = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $result = $conn->fetchOne();
        $entityInstance->setGroupeId($result);

        // Si le groupe utilisateur est vide, on le définit à "Aucun"
        if  (empty($entityInstance->getGroupeUtilisateur())) {
            $entityInstance->setGroupeUtilisateur('Aucun');
        }

        $entityInstance->setDateModification(new \DateTime('now', new \DateTimeZone(static::$europeParis)));

        parent::updateEntity($em, $entityInstance);
    }

}
