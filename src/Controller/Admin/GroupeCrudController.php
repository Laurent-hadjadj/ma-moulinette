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
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Groupe;

/**
 * [Description GroupeCrudController]
 */
class GroupeCrudController extends AbstractCrudController
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
        return Groupe::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT);
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
            ->add('titre');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setFormThemes([
            'admin/form/custom_info_widget.html.twig',
            '@EasyAdmin/crud/form_theme.html.twig',
        ]);
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
        yield TextField::new('titre')
            ->setLabel('Nom')
            ->setFormTypeOption('attr', ['placeholder' => 'JAVA-C-COOL-AUSSI'])
            ->setHelp('Nom du groupe. Les caractères autorisés sont [a-z0-9-] pour l\'utilisation des tags SonarQube.')
            ->hideWhenUpdating();

        yield TextField::new('description')
        ->setFormTypeOption('attr', ['placeholder' => 'Application - JAVA'])
        ->setHelp('Description du groupe. Par exemple, Application - ?[langage]');

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
        if (!$entityInstance instanceof Groupe) {
            return;
        }
        $nom = $entityInstance->getTitre();
        $cleanNom = preg_replace("/[^a-zA-Z0-9\- ]+/", "-", mb_strtoupper($nom));
        $entityInstance->setTitre($cleanNom);
        $entityInstance->setDateEnregistrement(new \DateTimeImmutable());

        /** retourne 1 ou null */
        $record = $this->emm->getRepository(Groupe::class)->findOneBy([
            'titre' => mb_strtoupper($cleanNom)
        ]);

        /** Si l'attribut 'titre' n'existe pas, on enregistre.*/
        if (!$record) {
            parent::persistEntity($em, $entityInstance);
        }
    }

}
