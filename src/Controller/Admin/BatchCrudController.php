<?php

namespace App\Controller\Admin;

use App\Entity\Main\Batch;
use App\Entity\Main\ListeProjet;
use App\Entity\Main\Portefeuille;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormTypeInterface;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BatchCrudController extends AbstractCrudController
{
    /**
     * [Description for __construct]
     *
     * @param  private
     *
     */
    public function __construct(
        private EntityManagerInterface $emm,
        private TokenStorageInterface $token
        )
    {
        $this->emm = $emm;
        $this->token= $token;
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
        yield BooleanField::new('statut')->renderAsSwitch(false)
            ->setHelp('Statut du traitement (Activé/Désactivé).');

        yield TextField::new('nom')
        ->setHelp('Nom du traitement de données.');

        /** On récupère la liste des projets */
        $sql="SELECT nom FROM portefeuille ORDER BY nom ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $resultat = $l->fetchAllAssociative();
        /**
         * Si la liste des portefeuille est vide on renvoi"Aucun"
         */
        if (empty($resultat)) {
            $restultat=['Aucun'];
        }

        yield ChoiceField::new('portefeuille')
        //array_combine($key2, $val2)
            ->setChoices($resultat)
            ->setHelp('Nom du portefeuille de projets.');

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
        $user = $this->token->getToken()->getUser();
        dd($user);
        /** On récèpere le nom du batch */
        $nom=$entityInstance->getNom();
        //** On récupère le nombre de projet séléctionné */
        //$nombre_projet=array_count($entityInstance->getNom());
        $user = $this->token->getToken()->getUser();
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
