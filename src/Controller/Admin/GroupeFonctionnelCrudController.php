<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  https://www.gnu.org/licenses/agpl-3.0.html
*/

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Assets, Crud, Filters,};
use EasyCorp\Bundle\EasyAdminBundle\Field\{FormField, TextField, ChoiceField, DateTimeField};
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\GroupeFonctionnel;

/**
 * @extends AbstractCrudController<GroupeFonctionnel>
 */
/* MODIF 2026-07-20 : restriction serveur manquante, voir UtilisateurCrudController. */
#[IsGranted('ROLE_GESTIONNAIRE', message: 'Vous ne disposez pas des droits suffisants pour accéder à cette page.', statusCode: 403)]
class GroupeFonctionnelCrudController extends AbstractCrudController
{
    /**
     * [Description for __construct]
     * emm = EntityManagerInterface
     *
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function __construct(
        private EntityManagerInterface $emm
    ) {}

    /**
     * [Description for getEntityFqcn]
     *
     * @return string
     *
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public static function getEntityFqcn(): string
    {
        return GroupeFonctionnel::class;
    }

    /**
     * [Description for configureActions]
     *
     * @param Actions $actions
     *
     * @return Actions
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function configureActions(Actions $actions): Actions
    {
        // On désactive depuis la page INDEX, les pages de modification et de consultation
        return $actions
            //->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * [Description for configureFilters]
     * Ajoute un filtre de recherche
     * @param Filters $filters
     *
     * @return Filters
     *
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('groupeFonctionnel');
    }

    /**
     * [Description for configureCrud]
     *
     * @param Crud $crud
     *
     * @return Crud
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des groupes fonctionnels')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un groupe fonctionnel')
            ->setPageTitle(Crud::PAGE_EDIT, function ($entity) {
                return sprintf('Modifier le groupe "%s"', $entity->getGroupeFonctionnel());
            })
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du groupe fonctionnel')
            ->setFormThemes([
                'admin/form/custom_info_widget.html.twig',
                '@EasyAdmin/crud/form_theme.html.twig',
            ]);
    }

    /**
     * [Description for configureAssets]
     *
     * @param Assets $assets
     *
     * @return Assets
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function configureAssets(Assets $assets): Assets
    {
        return $assets->addJsFile('js/easy-admin/groupe-fonctionnel.js');
    }

    /**
     * [Description for normalize]
     *
     * @param string $value
     *
     * @return string
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9\-_]+/', '_', $value);
        return trim($value, ' -_');
    }

    /**
     * [Description for listeTags]
     *
     * @return array<int|string, mixed>
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function listeTags(): array
    {
        // Modifier la requête SQL pour exclure les tags indésirables
        $sql = "SELECT DISTINCT tag
                    FROM ma_moulinette.liste_projet
                    CROSS JOIN LATERAL json_array_elements_text(tags) AS tag
                    WHERE tag NOT IN ('@AUCUN', 'aucun', 'test')
                    ORDER BY tag ASC";

        // Utiliser Doctrine pour exécuter la requête
        $conn = $this->emm->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();

        // Récupérer les résultats
        $results = $result->fetchAllAssociative();

        // Extraire les tags des résultats
        return array_map(fn($result) => $result['tag'], $results);
    }

    /**
     * [Description for configureFields]
     *
     * @param string $pageName
     *
     * @return iterable<FieldInterface>
     *
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
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
        yield ChoiceField::new('tags')
            ->setLabel('Tags disponibles')
            ->setChoices(array_combine($this->listeTags(), $this->listeTags()))
            ->onlyOnForms()
            ->setFormTypeOption('choice_translation_domain', false)
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('attr', [
                'mapped',
                false,
                'class' => 'js-tags',
                'disabled' => $pageName === Crud::PAGE_EDIT
            ])
            ->setHelp('Sélectionnez le tag associé à ce groupe fonctionnel. Les tags sont utilisés pour regrouper les projets par thème ou technologie.');

        yield FormField::addRow();
        yield FormField::addColumn(4);
        yield TextField::new('groupeFonctionnel')
            ->setLabel('Groupe fonctionnel')
            ->setFormTypeOption('attr', [
                'placeholder' => 'java-c-cool',
                'class' => 'js-groupe',
                'readonly' => $pageName === Crud::PAGE_EDIT
            ])
            ->setHelp('Nom UNIQUE du groupe. Les caractères autorisés sont [a-zA-Z0-9_-].');

        yield FormField::addColumn(12);
        yield TextField::new('description')
            ->addCssClass('mb-4')
            ->setFormTypeOption('attr', ['placeholder' => 'Application - JAVA'])
            ->setHelp('Description du groupe. Par exemple, Application - ?[language]');

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
     *
     * @return void
     *
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        /* MODIF 2026-05-07 : guard instanceof. */
        // @phpstan-ignore-next-line instanceof.alwaysTrue (garde défensive EasyAdmin)
        if (!$entityInstance instanceof GroupeFonctionnel) {
            return;
        }

        // Normalisation
        $clean = $this->normalize($entityInstance->getGroupeFonctionnel());
        $entityInstance->setGroupeFonctionnel($clean);

        $entityInstance->setDateEnregistrement(new \DateTimeImmutable());

        // Vérification unicité (logique métier)
        $existing = $em->getRepository(GroupeFonctionnel::class)
            ->findOneBy(['groupeFonctionnel' => $clean,]);

        if ($existing) {
            $this->addFlash('danger', 'Ce groupe existe déjà.');
            return;
        }

        parent::persistEntity($em, $entityInstance);
    }
}
