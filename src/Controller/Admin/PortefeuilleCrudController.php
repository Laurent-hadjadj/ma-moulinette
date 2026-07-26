<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Assets, Crud, Filters};
use EasyCorp\Bundle\EasyAdminBundle\Field\{FormField, ChoiceField, TextField, DateTimeField};
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Portefeuille, Batch, BatchTraitement};

/**
 * @extends AbstractCrudController<Portefeuille>
 */
/* MODIF 2026-07-20 : restriction serveur manquante — seule la carte du
 * dashboard (home.html.twig, is_granted('ROLE_BATCH')) filtrait l'accès. */
#[IsGranted('ROLE_BATCH', message: 'Vous ne disposez pas des droits suffisants pour accéder à cette page.', statusCode: 403)]
class PortefeuilleCrudController extends AbstractCrudController
{
    private static string $europeParis = 'Europe/Paris';

    /**
     * [Description for __construct]
     *
     * Created at: 02/01/2023, 18:35:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(private EntityManagerInterface $emm)
    {
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
     * @param Assets $assets
     *
     * @return Assets
     *
     * Created at: 26/10/2025 13:15:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureAssets(Assets $assets): Assets
    {
        /**
         * MODIF 2026-06-03 : script de filtrage projets injecte une seule
         * fois via EasyAdmin (evite les doublons du form theme rendus par ChoiceField).
         */
        return $assets->addHtmlContentToBody(<<<'JS'
<script>
document.addEventListener("DOMContentLoaded", async function () {
    // Retourner l'URL trouvée dans la balise meta ou, par défaut, l'origine de la location
    const server = location.origin;
    const response = await fetch(window.location.pathname);
    const url = new URL(response.url);
    const pathParts = url.pathname.split('/').filter(part => part !== '');
    let prefix='';

    // L'URL commence directement par "/admin" (pas de préfixe)
    if (pathParts[0] === 'admin') {
        prefix = '/';
    }
    // L'URL contient un préfixe (ex: "/xxx/admin/...")
    else if (pathParts.length >= 2 && pathParts[1] === 'admin') {
        prefix = '/' + pathParts[0];
    } else { console.warn("Format d'URL inattendu :", url.pathname); }

    const g = document.querySelector('[data-ea-field-name*="groupe"]');
    const s = document.querySelector('[data-ea-field-name*="liste"] select');

    if (!g || !s) { return; }
    g.addEventListener("change", function (e) {
        let ts = s.tomselect;
        if (!ts) { return; }
        let p = e.target.tagName === "SELECT"
            ? (e.target.value || "")
            : Array.from(g.querySelectorAll("input:checked")).map(function (el) { return el.value; }).join(",");
        ts.disable();
        fetch(server + prefix + "/api/secure/admin/portefeuille/list-projets?groupe=" + encodeURIComponent(p),{
            headers: {
                            'Content-Type': 'application/json',
                            'X-Internal-Front': 'front-app'
                    }

            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                let cv = ts.getValue();
                ts.clearOptions();
                ts.clear(true);
                data.forEach(function (i) { ts.addOption({ value: i.maven_key, text: i.name }); });
                ts.refreshOptions(false);
                if (cv && data.some(function (i) { return i.maven_key === cv; })) { ts.setValue(cv); }
                let hw = s.closest(".form-group") || s.closest(".form-widget");
                let h = hw ? hw.querySelector(".form-text") : null;
                if (h) { h.textContent = data.length + " projet(s) trouves."; }
            })
            .catch(function (err) { console.error("[Portefeuille]", err); })
            .finally(function () { ts.enable(); });
    });
});
</script>
JS);
    }

    /**
     * [Description for configureCrud]
     *
     * @param Crud $crud
     *
     * @return Crud
     *
     * Created at: 04/06/2026 09:19:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des portefeuilles')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un portefeuille')
            ->setPageTitle(Crud::PAGE_EDIT, function ($entity) {
                            return sprintf('Modifier le portefeuille "%s"', $entity->getPortefeuille());
            })
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du portefeuille')
            ->setFormThemes([
                'admin/form/custom_info_widget.html.twig',
                '@EasyAdmin/crud/form_theme.html.twig',
                'admin/form/custom_choice_widget.html.twig'
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
            ->add('portefeuille')
            ->add('groupeFonctionnel');
    }

    /**
     * [Description for configureFields]
     * Configuration et propriétés des champs
     *
     * @param string $pageName
     *
     * @return iterable<\EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface>
     *
     * Created at: 02/01/2023, 18:36:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureFields(string $pageName): iterable
    {
        // MODIF 2026-06-03 : ordre groupe → projets → nom.
        yield FormField::addColumn(12);
        yield TextField::new('information')
            ->setLabel(false)
            ->setFormTypeOption('block_name', 'information')
            ->onlyOnForms()
            ->setFormTypeOption('mapped', false);

        // Étape 1 — Groupe fonctionnel (filtre le périmètre de projets)
        yield FormField::addColumn(4);
        $sql = "SELECT groupe_fonctionnel, description FROM ma_moulinette.groupe_fonctionnel ORDER BY groupe_fonctionnel ASC";
        $stmt = $this->emm->getConnection()->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        if (empty($result)) {
            $result = [['groupe_fonctionnel' => 'Aucun groupe fonctionnel', 'description' => 'Aucune description.']];
        }

        $key1 = $val1 = [];
        foreach ($result as $i => $value) {
            $key1[$i] = $value['groupe_fonctionnel'] . ' - ' . $value['description'];
            $val1[$i] = $value['groupe_fonctionnel'];
        }

        yield ChoiceField::new('groupeFonctionnel')
            ->setChoices(array_combine($key1, $val1))
            ->setLabel('Groupe fonctionnel')
            ->setFormTypeOption('attr', ['readonly' => $pageName === Crud::PAGE_EDIT])
            ->setFormTypeOption('choice_translation_domain', false)
            ->setHelp('Choisissez un groupe pour filtrer la liste des projets.');

        // Étape 2 — Projets (filtrés par le groupe via JS/AJAX)
        yield FormField::addColumn(8);
        $request = $this->getContext()->getRequest();
        $selectedGroupe = $request->query->get('groupe');
        /* guard PAGE_EDIT : en PAGE_NEW l'entité est vide, groupeFonctionnel non initialisé. */
        if (empty($selectedGroupe) && $pageName === Crud::PAGE_EDIT && $this->getContext()?->getEntity()?->getInstance()) {
            $selectedGroupe = $this->getContext()->getEntity()->getInstance()->getGroupeFonctionnel();
        }

        // Charge TOUJOURS tous les projets (validation Symfony indépendante du filtre JS).
        // Labels uniques : noms dupliqués → suffixe maven_key.
        $allProjects = $this->emm->getConnection()
            ->fetchAllAssociative("SELECT name, maven_key FROM ma_moulinette.liste_projet ORDER BY name ASC");

        $choices = [];
        $seenNames = [];
        foreach ($allProjects as $p) {
            $label = $p['name'];
            if (isset($seenNames[$label])) {
                $label .= ' (' . $p['maven_key'] . ')';
            }
            $seenNames[$p['name']] = true;
            $choices[$label] = $p['maven_key'];
        }

        $totalCount = count($allProjects);
        $filtered = false;
        $filteredCount = $totalCount;
        if (!empty($selectedGroupe)) {
            $tag = mb_strtolower(trim($selectedGroupe));
            $mc = (int) $this->emm->getConnection()->fetchOne(
                "SELECT COUNT(*) FROM ma_moulinette.liste_projet WHERE jsonb_exists(tags::jsonb, :tag)",
                ['tag' => $tag]
            );
            if ($mc > 0) {
                $filteredCount = $mc;
                $filtered = true;
            }
        }

        yield ChoiceField::new('liste')
            ->setChoices($choices)
            ->allowMultipleChoices()
            ->autocomplete()
            ->hideOnIndex()
            ->setFormTypeOption('choice_translation_domain', false)
            ->setHelp(
                $filtered
                    ? sprintf('%d projet(s) filtrés — groupe "%s". Tape pour filtrer.', $filteredCount, $selectedGroupe)
                    : sprintf('%d projet(s) disponibles. Sélectionne un groupe pour filtrer.', $totalCount)
            );

        // Étape 3 — Nom du portefeuille (nommez ce que vous venez de constituer)
        yield FormField::addColumn(4);
        yield TextField::new('portefeuille')
            ->setLabel('Nom du portefeuille')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Ex. lot1-2021-quotidien',
                'readonly'    => $pageName === Crud::PAGE_EDIT,
            ])
            ->setHelp('Nom unique. Convention : [groupe]-[fréquence], ex. lot1-2021-hebdo.');

        yield DateTimeField::new('dateModification')
            ->setTimezone(self::$europeParis)
            ->hideOnForm();

        yield DateTimeField::new('dateEnregistrement')
            ->setTimezone(self::$europeParis)
            ->hideOnForm();
    }

    /**
     * [Description for persistEntity]
     * On enregistre les données lors de la création
     *
     * @param EntityManagerInterface $em
     *
     * @return void
     *
     * Created at: 02/01/2023, 18:36:49 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        /* MODIF 2026-05-07 : guard instanceof. */
        // @phpstan-ignore-next-line instanceof.alwaysTrue (garde défensive EasyAdmin)
        if (!$entityInstance instanceof Portefeuille) {
            return;
        }

        /** On récupère le nom du portefeuille */
        $nom_portefeuille = $entityInstance->getPortefeuille();

        /** On enregistre le données que l'on veut modifier */
        $entityInstance->setPortefeuille(mb_strtoupper($nom_portefeuille));
        $entityInstance->setDateEnregistrement(new \DateTimeImmutable());

        /** retourne 1 ou null */
        $record = $this->emm->getRepository(Portefeuille::class)->findOneBy(['portefeuille' => mb_strtoupper($nom_portefeuille)]);

        /** Si la valeur de l'attribut 'portefeuille' n'existe pas, on enregistre.*/
        if (is_null($record)) {
            parent::persistEntity($em, $entityInstance);
            return;
        }

        /* MODIF 2026-07-19 : Portefeuille n'a pas de contrainte #[UniqueEntity] (contrairement
         * à GroupeUtilisateur/GroupeFonctionnel) — sans ce flash, un nom déjà utilisé était
         * silencieusement ignoré : aucune écriture, mais EasyAdmin affichait quand même son
         * message de succès par défaut. */
        $this->addFlash('danger', 'Ce portefeuille existe déjà.');
    }

    /**
     * [Description for deleteEntity]
     * Bloque la suppression si des traitements référencent ce portefeuille.
     *
     * Created at: 10/06/2026 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteEntity(EntityManagerInterface $em, $entityInstance): void
    {
        // MODIF 2026-06-10  — garde défensive EasyAdmin
        // @phpstan-ignore-next-line instanceof.alwaysTrue (garde défensive EasyAdmin)
        if (!$entityInstance instanceof Portefeuille) {
            return;
        }

        $traitements = $this->emm->getRepository(BatchTraitement::class)
            ->findBy(['portefeuille' => $entityInstance->getGroupeFonctionnel()]);

        if (!empty($traitements)) {
            $noms = implode(', ', array_map(
                fn(BatchTraitement $t) => '"' . ($t->getTitre() ?? '?') . '"',
                $traitements
            ));
            $this->addFlash('danger', sprintf(
                'Impossible de supprimer ce portefeuille : %d traitement(s) y sont liés (%s). Supprimez d\'abord les traitements concernés.',
                count($traitements),
                $noms
            ));
            return;
        }

        parent::deleteEntity($em, $entityInstance);
    }

    /**
     * [Description for updateEntity]
     * Mise à jour des données du formulaire
     * @param EntityManagerInterface $em
     *
     * @return void
     *
     * Created at: 02/01/2023, 18:37:02 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        /* MODIF 2026-05-07 : guard instanceof. */
        // @phpstan-ignore-next-line instanceof.alwaysTrue (garde défensive EasyAdmin)
        if (!$entityInstance instanceof Portefeuille) {
            return;
        }

        /** On récupère le nombre de projet pour ce portefeuille */
        $nombre_projet = count($entityInstance->getListe());
        /** On ajoute la date de modification  */
        $entityInstance->setDateModification(new \DateTime('now', new \DateTimeZone(self::$europeParis)));

        try {
            parent::persistEntity($em, $entityInstance);

            /* MODIF 2026-07-19 : Batch.portefeuille/BatchTraitement.portefeuille stockent en
             * réalité le slug du groupe fonctionnel, pas le nom du portefeuille (voir le
             * commentaire "$data->portefeuille = groupe_fonctionnel (slug)" dans
             * BatchAutoController::traitementListe()) — la recherche/mise à jour ci-dessous
             * comparait à tort sur le nom du portefeuille, ce qui ne trouvait jamais de
             * correspondance et laissait `nombre_projet` figé après un changement de la
             * liste de projets. */
            $groupeFonctionnel = $entityInstance->getGroupeFonctionnel();
            $isExist = $this->emm->getRepository(Batch::class)->findOneBy(['portefeuille' => $groupeFonctionnel]);

            $map = [
                'portefeuille' => $groupeFonctionnel,
                'nombre_projet' => $nombre_projet
            ];

            if ($isExist) {
                $this->emm->getRepository(Batch::class)->updatePortefeuille($map);
                $this->emm->getRepository(BatchTraitement::class)->updatePortefeuille($map);
            }
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue lors de la mise à jour du portefeuille : ' . $e->getMessage());
        }
    }

}
