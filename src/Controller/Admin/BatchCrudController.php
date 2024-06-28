<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Batch;
use App\Entity\BatchTraitement;

use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use App\Service\RabbitMQService;

/**
 * [Description BatchCrudController]
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
    private $rabbitMQService;
    private $emm;
    private $token;

    public function __construct(
        EntityManagerInterface $emm,
        TokenStorageInterface $token,
        RabbitMQService $rabbitMQService
    ) {
        $this->emm = $emm;
        $this->token = $token;
        $this->rabbitMQService = $rabbitMQService;
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
            ->add('statut');
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
        yield BooleanField::new('statut')->renderAsSwitch(false)
            ->setHelp('Statut du traitement (Activé/Désactivé).');

        yield TextField::new('titre')
        ->setHelp('Nom du traitement de données.');

        /** On récupère la liste des projets sans filtrage */
        /** To.do : ajouter le filtrage en fonction du portefeuille de projets */
        $sql = "SELECT titre FROM portefeuille ORDER BY titre ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $resultat = $l->fetchAllAssociative();
        /**
         * Si la liste des portefeuilles est vide on renvoi "Aucun"
         */
        $i = 0;

        if (empty($resultat)) {
            $key = ["aucun"];
            $val = ["Aucun"];
        } else {
            foreach($resultat as $value) {
                $key[$i] = $value['titre'];
                $val[$i] = $value['titre'];
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

        $batchTraitementRepository = $this->emm->getRepository(BatchTraitement::class);

        /** On récupère le nom du batch */
        $titre = $entityInstance->getTitre();

        /** On récupère l'objet user */
        $user = $this->token->getToken()->getUser();

        /** On récupère le nombre de projet du portefeuille */
        $sql = "SELECT liste FROM portefeuille ORDER BY titre ASC";
        $l = $this->emm->getConnection()->prepare($sql)->executeQuery();
        $r = $l->fetchAssociative();
        $nombreProjet=0;
        if ($r){
            $nombreProjet = count(json_decode($r['liste']));
        }

        /**
         * On enregistre les données que l'on veut modifier.
         * attention, les attributs dans l'entity ne doivent pas être NotNull et NotBlank.
        */
        $entityInstance->setTitre(mb_strtoupper($titre));
        $entityInstance->setResponsable($user->getPrenom().' '.$user->getNom());
        $entityInstance->setNombreProjet($nombreProjet);
        $entityInstance->setDateEnregistrement(new \DateTimeImmutable());

        /** On prépare les données pour la table de suivi des traitements */
        $map=[
            'demarrage' => ($entityInstance->isStatut() === true)? 'Auto' : 'Manuel',
            'resultat' => 1,
            'titre' => $entityInstance->getTitre(),
            'portefeuille' => $entityInstance->getPortefeuille(),
            'nombre_projet' => $entityInstance->getNombreProjet(),
            'responsable' => $entityInstance->getResponsable(),
            'date_enregistrement' => new \DateTimeImmutable()
        ];

        parent::persistEntity($em, $entityInstance);

        /** On programme le traitement dans la table BatchTraitement  */
        $r=$batchTraitementRepository->insertBatchTraitement($map);
        if ($r['code']!=200){
            throw new \RuntimeException('Erreur : '.$r['code'].' '.$r['erreur']);
        }

        /** On créé les queue RabbitMQ si elle n'existe pas encore */
        try {
            $queues = ['traitement_manuel_queue', 'traitement_automatique_queue'];

            foreach ($queues as $queue) {
                $this->rabbitMQService->createQueueIfNotExists($queue);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Déclaration de la queue en erreur : ' . $queue, 0, $e);
        } finally {
            $this->rabbitMQService->close();
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
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Batch) {
            return;
        }
        /** On ajoute la date de modification  */
        $entityInstance->setDateModification(new \DateTime());

        parent::updateEntity($em, $entityInstance);
    }

}
