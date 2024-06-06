<?php

namespace App\Controller\Admin;

use App\Entity\Equipe;

use Doctrine\ORM\EntityManagerInterface;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

use Symfony\Component\HttpFoundation\RequestStack;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * [Description EquipeCrudController]
 */
class EquipeCrudController extends AbstractCrudController
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
        return Equipe::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud;
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
        yield TextField::new('titre')
        ->setHelp('Nom de l\'équipe. Attention, si vous voulez utiliser les tags de SonarQube, les caractères autorisés sont [a-z0-9-].');
        yield TextField::new('description')
        ->setHelp('Description de l\'équipe.');
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
        if (!$entityInstance instanceof Equipe) {
            return;
        }
        $nom = $entityInstance->getTitre();
        $cleanNom=preg_replace("/[^a-zA-Z0-9\- ]+/", "-", mb_strtoupper($nom));
        $entityInstance->setTitre($cleanNom);
        $entityInstance->setDateEnregistrement(new \DateTimeImmutable());

        /** retourne 1 ou null */
        $record = $this->emm->getRepository(Equipe::class)->findOneBy(['titre' => mb_strtoupper($cleanNom)]);
        /** Si l'attribut 'titre' n'existe pas, on enregistre.*/
        if (!$record) {
            parent::persistEntity($em, $entityInstance);
        }
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Equipe) {
            return;
        }
        $entityInstance->setdateModification(new \DateTimeImmutable());
        parent::updateEntity($em, $entityInstance);
    }
}
