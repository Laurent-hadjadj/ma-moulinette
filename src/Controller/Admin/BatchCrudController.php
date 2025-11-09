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

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud, Filters};
use EasyCorp\Bundle\EasyAdminBundle\Field\{BooleanField, ChoiceField, DateTimeField, IntegerField, TextField};
use Symfony\Component\Uid\Ulid;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Batch, BatchTraitement, BatchExecution };
use App\Exception\SqlRequestException;

/**
 * [Description BatchCrudController]
 * Gère les traitements programmés ou manuels.
 *
 */
class BatchCrudController extends AbstractCrudController
{
    /**
     * [Description for __construct]
     *
     * Created at: 02/01/2023, 18:32:27 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */

    public function __construct(
        private EntityManagerInterface $emm,
        private TokenStorageInterface $token,
    ) {
    }

    /**
     * [Description for formatUsername]
     *
     * @param string $first_name
     * @param string $last_name
     *
     * @return string
     *
     * Created at: 01/06/2025 16:47:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function formatUsername(string $first_name, string $last_name): string
    {
        // Si pas de point OU compte technique
        if (strtolower($first_name) === 'admin' && (
            strtolower($last_name) === '@ma-moulinette' ||
            strtolower($last_name) === 'ma-moulinette')
            ) {
            return ' ' . strtoupper($first_name);
        }

        // Gestion de prénoms composés type "jean-pierre"
        $initialesFirstName = implode('-', array_map(
            fn($part) => strtoupper(substr($part, 0, 1)),
            explode('-', $first_name)
        ));

        return "{$initialesFirstName}. " . strtoupper($last_name);
    }

    /**
     * [Description for getEntityFqcn]
     *
     * @return string
     *
     * Created at: 02/01/2023, 18:32:36 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function getEntityFqcn(): string
    {
        return Batch::class;
    }

    /**
     * [Description for configureActions]
     *
     * @param Actions $actions
     *
     * @return Actions
     *
     * Created at: 09/11/2025 15:49:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureActions(Actions $actions): Actions
    {
        // On désactive depuis la page INDEX, la page de modification
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::DETAIL);
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('titre')
            ->add('activated')
            ->add('automatique');
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFields(string $pageName): iterable
    {
        yield BooleanField::new('activated')->renderAsSwitch(false)
            ->setLabel('Activé')
            ->setHelp('Statut du traitement (Activé/Désactivé).');

        yield BooleanField::new('automatique')->renderAsSwitch(false)
            ->setLabel('Automatique ?')
            ->setHelp('mode de collecte Automatique ou Manuel (Automatique = true).');

        yield TextField::new('titre')
            ->setLabel('Traitement')
            ->setFormTypeOption('attr', ['placeholder' => 'Application - Lot 1'])
            ->setHelp('Nom du traitement de données. Ex. Application - [groupe]');

        /** On récupère la liste des projets sans filtrage */
        /** To.do : ajouter le filtrage en fonction du portefeuille de projets */
        $sql = "SELECT titre FROM portefeuille ORDER BY titre ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $result = $l->fetchAllAssociative();
        /**
         * Si la liste des portefeuilles est vide on renvoi "Aucun"
         */
        $i = 0;

        if (empty($result)) {
            $key = ["aucun"];
            $val = ["Aucun"];
        } else {
            foreach($result as $value) {
                $key[$i] = $value['titre'];
                $val[$i] = $value['titre'];
                $i++;
            }
        }

        yield ChoiceField::new('portefeuille')
            ->setChoices(array_combine($key, $val))
            ->setHelp('Nom du portefeuille de projets.');

        yield TextField::new('description')
            ->setFormTypeOption('attr', ['placeholder' => 'Collecte de données pour les applications JAVA du Lot 1'])
            ->setHelp('Description du traitement de données. Ex. Collecte de données pour les applications [language] [groupe]');

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

        $batchTraitementRepos = $this->emm->getRepository(BatchTraitement::class);

        /** On récupère le nom du batch */
        $titre = $entityInstance->getTitre();

        /** On récupère l'objet user */
        $user = $this->token->getToken()->getUser();

        /** On récupère le nombre de projet du portefeuille */
        $portefeuille = $entityInstance->getPortefeuille();
        $sql = "SELECT liste FROM portefeuille where titre = '$portefeuille'";
        $conn = $this->emm->getConnection()->prepare($sql);
        $exec= $conn->executeQuery()->fetchAssociative();

        $nombre_projet = 0;
        if (isset($exec['liste'])){
            $nombre_projet = count(json_decode($exec['liste'], true));
        }

        /**
         * On enregistre les données que l'on veut modifier.
         * attention, les attributs dans l'entity ne doivent pas être NotNull et NotBlank.
        */
        $entityInstance->setTitre(mb_strtoupper($titre));
        $entityInstance->setResponsable($user->getPrenom() . ' ' . $user->getNom());

        $responsable_short = self::formatUsername($user->getPrenom(), $user->getNom());
        $entityInstance->setResponsableShort($responsable_short);

        /** On créé un clé unique pour lier  */
        $traitement_id = new ulid();
        $entityInstance->setTraitementId($traitement_id);
        $entityInstance->setNombreProjet($nombre_projet);
        $entityInstance->setDateEnregistrement(new \DateTimeImmutable());

        /** On prépare les données pour la table de suivi des traitements */
        $map = [
            'mode_collecte' => ($entityInstance->isAutomatique() === true) ? 'TRAITEMENT AUTOMATIQUE' : 'TRAITEMENT MANUEL',
            'activated' => $entityInstance->setActivated(true),
            'success' => null,
            'in_progress' => false,
            'pending' => null,
            'titre' => $entityInstance->getTitre(),
            'portefeuille' => $portefeuille,
            'nombre_projet' => $entityInstance->getNombreProjet(),
            'responsable' => $entityInstance->getResponsable(),
            'responsable_short' => $responsable_short,
            'traitement_id' => $traitement_id,
            'date_enregistrement' => new \DateTimeImmutable()
        ];

        parent::persistEntity($em, $entityInstance);

        /** On programme le traitement dans la table BatchTraitement  */
        $r = $batchTraitementRepos->insertBatchTraitement($map);

        if ($r['code'] !== 200){
            throw new SqlRequestException('insertBatchTraitement', (int) $r['code'], $r['erreur'], null);
        }
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Batch) {
            return;
        }

        /** On vérifie que le nombre de projet n'a pas changé */
        $portefeuille = $entityInstance->getPortefeuille();
        $sql = "SELECT liste FROM portefeuille where titre = '$portefeuille'";
        $conn = $this->emm->getConnection()->prepare($sql);
        $exec= $conn->executeQuery()->fetchAssociative();

        $nombre_projet = 0;
        if (isset($exec['liste'])){
            $nombre_projet = count(json_decode($exec['liste'], true));
        }

        if ($nombre_projet > 0 && $nombre_projet != $entityInstance->getNombreProjet()) {
            // On met à jour la table BATCH
            $sql1 = "UPDATE batch
                    SET nombre_projet = $nombre_projet
                    WHERE portefeuille = '$portefeuille'";
            $conn = $this->emm->getConnection()->prepare($sql1);
            $conn->executeStatement();

            // On met à jour la table BATCH_TRAITEMENT
            $traitement_id = $entityInstance->getTraitementId()->toRfc4122();
            $isActivated = $entityInstance->getIsActivated();
            $isAutomatique = ($entityInstance->isAutomatique() === true) ? 'TRAITEMENT AUTOMATIQUE' : 'TRAITEMENT MANUEL';
            $sql2 = "UPDATE batch_traitement
                    SET nombre_projet = $nombre_projet,
                        activated = $isActivated,
                        mode_collecte = $isAutomatique,
                    WHERE traitement_id = '$traitement_id'";
            $conn = $this->emm->getConnection()->prepare($sql2);
            $conn->executeStatement();
        }

        /** On ajoute la date de modification  */
        $entityInstance->setDateModification(new \DateTime());

        parent::updateEntity($em, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Batch) {
            return;
        }

        $batchTraitementRepos = $this->emm->getRepository(BatchTraitement::class);
        $batchExecutionRepos = $this->emm->getRepository(BatchExecution::class);

        /** On récupère le nom du batch */
        $transaction_id = $entityInstance->getTraitementId();

        /** On supprime le traitement  */
        $r1 = $batchTraitementRepos->deleteTraitement($transaction_id);
        $r2 = $batchExecutionRepos->deleteTraitement($transaction_id);

        if ($r1['code'] !== 200){
            throw new SqlRequestException('insertBatchTraitement', (int) $r1['code'], $r1['erreur'], null);
        } elseif  ($r2['code'] !== 200){
            throw new SqlRequestException('insertBatchExecution', (int) $r2['code'], $r2['erreur'], null);
        }

        // Supprime l'entité
        $em->remove($entityInstance);
        $em->flush();
    }
}
