<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Portefeuille;

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
     * [Description for configureCrud]
     * Ajout d'un custom filtre pour sélectionner/désélectionner des éléments dans une liste.
     * @param Crud $crud
     *
     * @return Crud
     *
     * Created at: 26/10/2025 13:15:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setFormThemes([
                '@EasyAdmin/crud/form_theme.html.twig',
                'admin/form/custom_choice_widget.html.twig',
            ]);
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
        $stmt = $this->emm->getConnection()->prepare($sql);
        $exec = $stmt->executeQuery();
        $result = $exec->fetchAllAssociative();

        /** si la table est vide */
        if (empty($result)) {
            $result = [
                [
                    "titre" => "Aucune",
                    "description" =>
                    "Aucune équipe."
                ]
            ];
        }

        $key1 = $val1 = [];
        foreach ($result as $i => $value) {
            $key1[$i] = $value['titre'] . " - " . $value['description'];
            $val1[$i] = $value['titre'];
        }

        yield ChoiceField::new('equipe')
            ->setChoices(array_combine($key1, $val1))
            //->allowMultipleChoices()
            //->autocomplete()
            ->renderExpanded()
            ->setHelp("Nom de l'équipe en charge des projets.");

         // --- Filtrage selon équipe sélectionnée ---
        $request = $this->getContext()->getRequest();
        $selectedEquipe = $request->query->get('equipe');

        // --- Liste des projets ---
        $params = [];
        $sql = "SELECT name, maven_key FROM liste_projet";

        if (!empty($selectedEquipe)) {
            $equipes = array_map('trim', explode(',', $selectedEquipe));

            // Normalisation : tout en minuscules pour correspondre aux tags JSON
            $equipes = array_map('mb_strtolower', $equipes);

            if (count($equipes) === 1) {
                // Utilise jsonb_exists pour éviter l'opérateur '?' qui gêne PDO/DBAL
                $sql .= " WHERE jsonb_exists(tags::jsonb, :eq0)";
                $params['eq0'] = $equipes[0];
            } else {
                // Utilise jsonb_exists_any pour vérifier si AU MOINS UNE des valeurs est présente
                // On fournit un littéral PostgreSQL text[] comme chaîne : '{"A","B"}'
                $arrayLiteral = '{' . implode(',', array_map(
                    fn($v) => '"' . str_replace('"', '\"', $v) . '"',
                    $equipes
                )) . '}';

                $sql .= " WHERE jsonb_exists_any(tags::jsonb, :eqArray)";
                $params['eqArray'] = $arrayLiteral;
            }
        }

        $sql .= " ORDER BY name ASC";

        $stmt = $this->emm->getConnection()->prepare($sql);
        foreach ($params as $name => $value) {
            // bindValue simple convient ici
            $stmt->bindValue($name, $value);
        }

        $projects = $stmt->executeQuery()->fetchAllAssociative();

        if (empty($projects)) {
            $key2 = ['Aucun Projet'];
            $val2 = [''];
        } else {
            foreach ($projects as $i => $value) {
                $key2[$i] = $value['name'];
                $val2[$i] = $value['maven_key'];
            }
        }

        // compter les projets remontés
        $count = count($projects);

        yield ChoiceField::new('liste')
            ->setChoices(array_combine($key2, $val2))
            ->allowMultipleChoices()
            ->autocomplete()
            ->setHelp(sprintf('Liste des projets du portefeuille — %d projet(s) trouvés. Tape pour filtrer.', $count));

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
        /** On récupère le titre du portefeuille */
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

        /** On ajoute la date de modification  */
        $entityInstance->setDateModification(new \DateTime());

        parent::persistEntity($em, $entityInstance);
    }

    #[Route('/admin/portefeuille/list-projets', name: 'admin_list_projets')]
    public function listProjets(Request $request): JsonResponse
    {
        $selectedEquipe = $request->query->get('equipe');
        $params = [];
        $sql = "SELECT name, maven_key FROM liste_projet";

        if (!empty($selectedEquipe)) {
            $equipes = array_map('trim', explode(',', $selectedEquipe));
            $equipes = array_map('mb_strtolower', $equipes);

            if (count($equipes) === 1) {
                $sql .= " WHERE jsonb_exists(tags::jsonb, :eq0)";
                $params['eq0'] = $equipes[0];
            } else {
                $arrayLiteral = '{' . implode(',', array_map(
                    fn($v) => '"' . addslashes($v) . '"',
                    $equipes
                )) . '}';
                $sql .= " WHERE jsonb_exists_any(tags::jsonb, :eqArray)";
                $params['eqArray'] = $arrayLiteral;
            }
        }

        $sql .= " ORDER BY name ASC";
        $stmt = $this->emm->getConnection()->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $projects = $stmt->executeQuery()->fetchAllAssociative();

        return new JsonResponse($projects);
    }
}
