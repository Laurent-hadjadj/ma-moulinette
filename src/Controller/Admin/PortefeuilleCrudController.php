<?php

namespace App\Controller\Admin;

use App\Entity\Main\Portefeuille;

use Doctrine\ORM\EntityManagerInterface;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * [Description PortefeuilleCrudController]
 */
class PortefeuilleCrudController extends AbstractCrudController
{
    /**
     * [Description for __construct]
     *
     * Created at: 02/01/2023, 18:35:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * Created at: 02/01/2023, 18:36:05 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getEntityFqcn(): string
    {
        return Portefeuille::class;
    }

    /**
     * [Description for configureFilters]
     * On ajoute un filtre de recherche
     *
     * @param Filters $filters
     *
     * @return Filters
     *
     * Created at: 02/01/2023, 18:36:11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('titre')
            ->add('equipe');
    }

    /**
     * [Description for configureActions]
     *
     * @param Actions $actions
     *
     * @return Actions
     *
     * Created at: 02/01/2023, 18:36:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions);
    }

    /**
     * [Description for configureFields]
     * Configuration et propriétés des champs
     *
     * @param string $pageName
     *
     * @return iterable
     *
     * Created at: 02/01/2023, 18:36:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('titre')
        ->setHelp('Nom de la liste des projets.');

        // On récupère la liste des équipes
        $sql = "SELECT titre, description FROM equipe ORDER BY titre ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $resultat = $l->fetchAllAssociative();
        $i = 0;
        /** si la table est vide */
        if (empty($resultat)) {
            $resultat = [["titre" => "Aucune", "description" => "Aucune équipe."]];
        }
        foreach($resultat as $value) {
            $key1[$i] = $value['titre']." - ".$value['description'];
            $val1[$i] = $value['titre'];
            $i++;
        }

        yield ChoiceField::new('equipe')
            ->setChoices(array_combine($key1, $val1))
            ->renderExpanded()
            ->setHelp('Nom de l\'équipe en charge des projets.');

        /** On récupère la liste des projets */
        $sql = "SELECT name, maven_key FROM liste_projet ORDER BY name ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $resultat = $l->fetchAllAssociative();
        /**
         * Si la liste des projets vide on renvoi un tableau vide
         */
        $i = 0;
        if (empty($resultat)) {
            $key2 = ['Aucun Projet'];
            $val2 = [''];
        } else {
            foreach($resultat as $value) {
                $key2[$i] = $value['name'];
                $val2[$i] = $value['maven_key'];
                $i++;
            }
        }

        yield ChoiceField::new('liste')
            ->setChoices(array_combine($key2, $val2))
            ->allowMultipleChoices()
            ->setHelp('Liste des projets du portefeuille.');

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
     * Created at: 02/01/2023, 18:36:49 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Portefeuille) {
            return;
        }
        /** On récèpere le titre du portefeuille */
        $titre = $entityInstance->getTitre();

        /** On enregistre le données que l'on veut modifier */
        $entityInstance->setTitre(mb_strtoupper($titre));
        $entityInstance->setDateEnregistrement(new \DateTimeImmutable());

        /** retourne 1 ou null */
        $record = $this->emm->getRepository(Portefeuille::class)->findOneBy(['titre' => mb_strtoupper($titre)]);

        /** Si la valeur de l'attribut 'titre' n'existe pas, on enregistre.*/
        if (is_null($record)) {
            parent::persistEntity($em, $entityInstance);
        }
    }

    /**
     * [Description for updateEntity]
     * Mise à jour des données du formulaire
     * @param EntityManagerInterface $em
     * @param mixed $entityInstance
     *
     * @return void
     *
     * Created at: 02/01/2023, 18:37:02 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Portefeuille) {
            return;
        }
        /** On récèpere le titre du portefeuille */
        $titre = $entityInstance->getTitre();

        /** On ajoute la date de modification  */
        $entityInstance->setdateModification(new \DateTimeImmutable());

        /** retourne 1 ou null */
        $record = $this->emm->getRepository(Portefeuille::class)->findOneBy(['titre' => mb_strtoupper($titre)]);

        /** Si la valeur de l'attribut 'titre' n'existe pas, on enregistre.*/
        if (is_null($record)) {
            parent::persistEntity($em, $entityInstance);
        } else {
            $entityInstance->setId(0);
        }
    }

}
