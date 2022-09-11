<?php

namespace App\Controller\Admin;

use App\Entity\Main\Utilisateur;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;


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
        yield idField::new('id')->onlyOnIndex();
        yield AvatarField::new('avatar')
            // If we have a User, then call getAvatarUrl() and return that string. But if we don't have a user, skip calling the method and just return null.
            ->formatValue(static function ($value, ?Utilisateur $utilisateur) {
                return $utilisateur?->getAvatarUrl();
            })
            ->setFormTypeOption(
                'disabled',
                $pageName !== Crud::PAGE_DETAIL
            )
            ->hideOnForm();
        yield EmailField::new('courriel');
        yield TextField::new('personne')
            ->hideOnForm();
            $roles = ['ROLE_ADMIN', 'ROLE_USER'];
        yield ChoiceField::new('roles')
            ->setChoices(array_combine($roles, $roles))
            ->allowMultipleChoices()
            ->renderExpanded()
            ->renderAsBadges();
        yield BooleanField::new('actif')->renderAsSwitch(false);
        yield DateTimeField::new('dateEnregistrement')->hideOnForm();
        yield DateTimeField::new('dateModification')
            ->setTimezone('Europe/Paris')
            ->hideOnForm();
    }
}
