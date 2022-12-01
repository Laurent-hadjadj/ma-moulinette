<?php

namespace App\Controller\Admin;

use App\Entity\Main\Portefeuille;
use App\Entity\Main\ListeProjet;
use App\Entity\Main\Equipe;

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

class PortefeuilleCrudController extends AbstractCrudController
{
    public function __construct(private EntityManagerInterface $emm)
    {
    $this->emm = $emm;
    }

    public static function getEntityFqcn(): string
    {
        return Portefeuille::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('nom');
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom')
        ->setHelp('Donne un nom à ta liste.');

        // On récupère la liste des équipes
        $sql="SELECT nom, description FROM equipe ORDER BY nom ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $resultat = $l->fetchAllAssociative();
        $i=0;
        /** si la table est vide */
        if (empty($resultat)) {
            $resultat=[["nom" => "Aucune", "description" => "Aucune équipe."]];
        }
        foreach($resultat as $value) {
            $key1[$i]=$value['nom']." - ".$value['description'];
            $val1[$i]=$value['nom'];
            $i++;
        }

        yield ChoiceField::new('equipe')
            ->setChoices(array_combine($key1, $val1))
            ->renderExpanded()
            ->setHelp('Choisi l\'équipe pour laquelle tu veux ajouter les projets.');

        // On récupère la liste des projets
        $sql="SELECT name, maven_key FROM liste_projet ORDER BY name ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $resultat = $l->fetchAllAssociative();
        /**
         * Si la liste des projets vide on renvoi un tableau vide
         */
        $i=0;
        if (empty($resultat)) {
            $key2=['Aucun Projet'];
            $val2=[''];
        } else {
            foreach($resultat as $value) {
                $key2[$i]=$value['name'];
                $val2[$i]=$value['maven_key'];
                $i++;
            }
        }

        yield ChoiceField::new('liste')
            ->setChoices(array_combine($key2, $val2))
            ->allowMultipleChoices()
            ->setHelp('Choisi les projets que tu souhaites ajouter à la liste.');

        yield DateTimeField::new('dateModification')
            ->setTimezone('Europe/Paris')
            ->hideOnForm();
        yield DateTimeField::new('dateEnregistrement')
            ->setTimezone('Europe/Paris')
            ->hideOnForm();

    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Portefeuille) {
            return;
        }
        $entityInstance->setdateModification(new \DateTimeImmutable);
        parent::updateEntity($em, $entityInstance);
    }

}
