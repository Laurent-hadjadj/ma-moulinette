<?php

namespace App\Controller\Admin;

use App\Entity\Main\Batch;
use App\Entity\Main\ListeProjet;
use App\Entity\Main\Portefeuille;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormTypeInterface;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * [Description BatchCrudController]
 */
class BatchCrudController extends AbstractCrudController
{
    /**
     * [Description for __construct]
     *
     * @param  private
     * @param  private
     *
     * Created at: 02/01/2023, 18:32:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * Created at: 02/01/2023, 18:32:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getEntityFqcn(): string
    {
        return Batch::class;
    }

    /**
     * [Description for configureFilters]
     * On ajoute un filtre de recherche
     *
     * @param Filters $filters
     *
     * @return Filters
     *
     * Created at: 02/01/2023, 18:32:42 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('titre')
            ->add('statut');
    }

    /**
     * [Description for configureActions]
     *
     * @param Actions $actions
     *
     * @return Actions
     *
     * Created at: 02/01/2023, 18:32:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
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
     * Created at: 02/01/2023, 18:33:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFields(string $pageName): iterable
    {
        yield BooleanField::new('statut')->renderAsSwitch(false)
            ->setHelp('Statut du traitement (Activé/Désactivé).');

        yield TextField::new('titre')
        ->setHelp('Nom du traitement de données.');

        /** On récupère la liste des projets */
        $sql="SELECT titre FROM portefeuille ORDER BY titre ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $resultat = $l->fetchAllAssociative();
        /**
         * Si la liste des portefeuille est vide on renvoi"Aucun"
         */
        $i=0;

        if (empty($resultat)) {
            $key=["aucun"];
            $val=["Aucun"];
        } else {
            foreach($resultat as $value) {
                $key[$i]=$value['titre'];
                $val[$i]=$value['titre'];
                $i++;
            }
        }

        yield ChoiceField::new('portefeuille')
            ->setChoices(array_combine($key, $val))
            ->setHelp('Nom du portefeuille de projets.');

        yield TextField::new('description')
        ->setHelp('Nom du traitement de données.');

        yield IntegerField::new('nombre_projet')
        ->hideOnForm()
        ->setHelp('Nombre de projet dans le portefeuille.');

        yield TextField::new('responsable')
        ->hideOnForm()
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
     * Created at: 02/01/2023, 18:33:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Batch) {
            return;
        }
        /** On récèpere le nom du batch */
        $titre=$entityInstance->getTitre();

        /** On récupère l'objet user */
        $user = $this->token->getToken()->getUser();

        //** On récupère le nombre de projet du portefeuille */
        $sql="SELECT liste FROM portefeuille ORDER BY titre ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $r = $l->fetchAssociative();
        $nombreProjet=count(json_decode($r['liste']));

        /** On enregistre le données que l'on veut modifier */
        $entityInstance->setTitre(mb_strtoupper($titre));
        $entityInstance->setResponsable($user->getPrenom().' '.$user->getNom());
        $entityInstance->setNombreProjet($nombreProjet);
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
     * Created at: 02/01/2023, 18:33:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
