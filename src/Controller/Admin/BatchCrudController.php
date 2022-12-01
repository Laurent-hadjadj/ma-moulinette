<?php

namespace App\Controller\Admin;

use App\Entity\Main\Portefeuille;
use App\Entity\Main\ListeProjet;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormTypeInterface;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;


use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class BatchCrudController extends AbstractCrudController
{
    /**
     * [Description for __construct]
     *
     * @param  private
     *
     */
    public function __construct(private EntityManagerInterface $emm)
    {
    $this->emm = $emm;
    }

    /**
     * [Description for getEntityFqcn]
     *
     * @return string
     *
     */
    public static function getEntityFqcn(): string
    {
        return Batch::class;
    }

    /**
     * [Description for configureFilters]
     * On ajoute un filtre de recherche
     * @param Filters $filters
     *
     * @return Filters
     *
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('nom')
            ->add('statut');
    }

    /**
     * [Description for configureActions]
     *
     * @param Actions $actions
     *
     * @return Actions
     *
     */
    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions);
    }

    /**
     * [Description for configureFields]
     * Configuration et propriétés des champs
     * @param string $pageName
     *
     * @return iterable
     *
     */
    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('statut')
            ->setChoices()
            ->renderExpanded()
            ->setHelp('Statut du traitement (Activé/Désactivé).');

        yield TextField::new('nom')
        ->setHelp('Nom du traitement de données.');

        yield TextField::new('description')
        ->setHelp('Nom du traitement de données.');

        yield TextField::new('utilisateur')
        ->setHelp('Responsable du traitement.');

        yield DateTimeField::new('dateModification')
            ->setTimezone('Europe/Paris')
            ->hideOnForm();
        yield DateTimeField::new('dateEnregistrement')
            ->setTimezone('Europe/Paris')
            ->hideOnForm();

    }

    /**
     * [Description for persistEntity]
     * On enregistre les données lors de la création
     *
     * @param EntityManagerInterface $em
     * @param mixed $entityInstance
     *
     * @return void
     *
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Batch) {
            return;
        }
        /** On récèpere le nom du batch */
        $nom=$entityInstance->getNom();
        /** On enregistre le données que l'on veut modifier */
        $entityInstance->setNom(strtoupper($nom));
        $entityInstance->setDateEnregistrement(new \DateTimeImmutable());
        parent::persistEntity($em, $entityInstance);
    }

    /**
     * [Description for updateEntity]
     * Mise à jour des données du formulaire
     *
     * @param EntityManagerInterface $em
     * @param mixed $entityInstance
     *
     * @return void
     *
     */
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Batch) {
            return;
        }
        /** On ajoute la date de modification  */
        $entityInstance->setdateModification(new \DateTimeImmutable);
        parent::updateEntity($em, $entityInstance);
    }
}
