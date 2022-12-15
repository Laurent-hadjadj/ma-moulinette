<?php

namespace App\Controller\Admin;

use App\Entity\Main\Utilisateur;
Use App\Entity\Main\Equipe;

use Doctrine\ORM\EntityManagerInterface;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;

//use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;


class UtilisateurCrudController extends AbstractCrudController
{
    public function __construct(private EntityManagerInterface $emm)
    {
    $this->emm = $emm;
    }

    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('courriel')
            ->add('equipe');
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)
            ->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AvatarField::new('avatar')
            ->formatValue(static function ($value, ?Utilisateur $utilisateur) {
                return $utilisateur?->getAvatarUrl();
            })
            ->setFormTypeOption(
                'disabled',
                $pageName !== Crud::PAGE_DETAIL
            )
            ->hideOnForm();
        yield TextField::new('personne')
            ->hideOnForm();
        yield EmailField::new('courriel');

        $key1=['Utilisateur', 'batch', 'Gestionnaire'];
        $value1 = ['ROLE_UTILISATEUR', 'ROLE_BATCH', 'ROLE_GESTIONNAIRE'];
        yield ChoiceField::new('roles')
            ->setChoices(array_combine($key1, $value1))
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges(['ROLE_UTILISATEUR' => 'success', 'ROLE_GESTIONNAIRE' => 'danger'])
            ->setHelp('Sélectionne le ou les rôles.');

        /** On récupère la liste des équipes */
        $sql="SELECT titre, description FROM equipe ORDER BY titre ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $resultat = $l->fetchAllAssociative();
        /** si la table est vide */
        if (empty($resultat)) {
            $resultat=[["titre" => "Aucune", "description" => "Aucune équipe."]];
        }
        $key=[];
        $val=[];
        foreach($resultat as $value) {
            array_push($key,$value['titre']." - ".$value['description']);
            array_push($val,$value['titre']);
        }

        yield ChoiceField::new('equipe')
            ->setChoices(array_combine($key, $val))
            ->allowMultipleChoices()
            //->renderExpanded()
            ->setHelp('Sélectionne ton équipe.');

        yield BooleanField::new('actif')->renderAsSwitch(false);

        yield DateTimeField::new('dateModification')
            ->setTimezone('Europe/Paris')
            ->hideOnForm();
        yield DateTimeField::new('dateEnregistrement')
            ->setTimezone('Europe/Paris')
            ->hideOnForm();

    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Utilisateur) {
            return;
        }
        $entityInstance->setdateModification(new \DateTimeImmutable);
        parent::updateEntity($em, $entityInstance);
    }

}
