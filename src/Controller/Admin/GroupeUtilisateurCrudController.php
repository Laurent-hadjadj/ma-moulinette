<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud, Filters};
use EasyCorp\Bundle\EasyAdminBundle\Field\{FormField, TextField, DateTimeField};
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\{Ulid};
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\GroupeUtilisateur;

/**
 * [Description GroupeUtilisateurCrudController]
 */
class GroupeUtilisateurCrudController extends AbstractCrudController
{
    /**
     * [Description for __construct]
     * emm = EntityManagerInterface
     *
     * Created at: 12/02/2023, 10:08:05 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $emm,
        private RequestStack $requestStack,
    ) {
        $this->emm = $emm;
        $this->requestStack = $requestStack;
    }

    /**
     * [Description for getEntityFqcn]
     *
     * @return string
     *
     * Created at: 02/01/2023, 18:35:35 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getEntityFqcn(): string
    {
        return GroupeUtilisateur::class;
    }

    /**
     * [Description for configureActions]
     *
     * @param Actions $actions
     *
     * @return Actions
     *
     * Created at: 09/11/2025 15:10:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureActions(Actions $actions): Actions
    {
        // On désactive depuis la page INDEX, les pages de modification et de consultation
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * [Description for configureFilters]
     * Ajoute un filtre de recherche
     * @param Filters $filters
     *
     * @return Filters
     *
     * Created at: 11/02/2023, 20:49:10 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('groupeUtilisateur');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des groupes utilisateurs')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un groupe utilisateur')
            ->setPageTitle(Crud::PAGE_EDIT, function ($entity) {
                            return sprintf('Modifier le groupe "%s"', $entity->getGroupeUtilisateur());
            })
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du groupe utilisateur')
            ->setFormThemes([
                'admin/form/custom_info_widget.html.twig',
                '@EasyAdmin/crud/form_theme.html.twig',
            ]);
    }

    /**
     * [Description for normalize]
     *
     * @param string $value
     *
     * @return string
     *
     * Created at: 06/04/2026 18:24:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9\-_ ]+/', '_', $value);
        return trim($value, ' -_');
    }

    /**
     * [Description for configureFields]
     *
     * @param string $pageName
     *
     * @return iterable
     *
     * Created at: 02/01/2023, 18:35:41 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFields(string $pageName): iterable
    {
        yield FormField::addColumn(12);
        yield TextField::new('information')
            ->setLabel(false)
            ->setFormTypeOption('block_name', 'information')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false);

        yield FormField::addColumn(4);
        yield TextField::new('groupeUtilisateur')
            ->setLabel('Groupe utilisateur')
            ->setFormTypeOption('attr', ['placeholder' => 'Service-SI'])
            ->setHelp('Le nom du groupe doit être un nom UNIQUE.')
            ->hideWhenUpdating();

        yield FormField::addColumn(8);
        yield TextField::new('description')
            ->setFormTypeOption('attr', ['placeholder' => "Service des Systèmes d'information"])
            ->setHelp("Description du groupe d'utilisateurs. Par exemple, Service des Systèmes d'information.");

        yield TextField::new('groupeId')
            ->setLabel('Identifiant du groupe')
            ->setHelp("Identifiant unique du groupe d'utilisateur.")
            ->setFormTypeOption('disabled', true)
            ->hideOnForm(false);

        yield DateTimeField::new('dateModification')
            ->setTimezone('Europe/Paris')
            ->hideOnForm();

        yield DateTimeField::new('dateEnregistrement')
            ->setTimezone('Europe/Paris')
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
     * Created at: 02/01/2023, 18:35:44 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof GroupeUtilisateur) {
            return;
        }

        // Normalisation
        $clean = $this->normalize($entityInstance->getGroupeUtilisateur());
        $entityInstance->setGroupeUtilisateur($clean);

        // Génération du groupeId ULID Base32 (26 caractères)
        if (empty($entityInstance->getGroupeId())) {
            $entityInstance->setGroupeId((string) new Ulid());
        }

        $entityInstance->setDateEnregistrement(new \DateTimeImmutable());

        // Vérification unicité (logique métier)
        $existing = $em->getRepository(GroupeUtilisateur::class)->findOneBy([
            'groupeUtilisateur' => $clean,
        ]);

        if ($existing) {
            $this->addFlash('danger', 'Ce groupe existe déjà.');
        }

        parent::persistEntity($em, $entityInstance);
    }

}
