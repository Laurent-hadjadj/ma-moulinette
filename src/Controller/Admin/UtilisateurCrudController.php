<?php

namespace App\Controller\Admin;

use App\Entity\Main\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;


class UtilisateurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
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
            $roles = ['ROLE_UTILISATEUR', 'ROLE_GESTIONNAIRE'];
        yield ChoiceField::new('roles')
            ->setChoices(array_combine($roles, $roles))
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges(['ROLE_UTILISATEUR' => 'success', 'ROLE_GESTIONNAIRE' => 'danger']);
        yield BooleanField::new('actif')->renderAsSwitch(false);
        yield DateTimeField::new('dateEnregistrement')->hideOnForm();
        yield DateTimeField::new('dateModification')
            ->setTimezone('Europe/Paris')
            ->hideOnForm();
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Utilisateur) { return; }
        $entityInstance->setdateModification(new \DateTimeImmutable);
        parent::updateEntity($em, $entityInstance);
    }

}
