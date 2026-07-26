import { test, expect } from '../helpers/fixtures';
import { login, logout } from '../helpers/auth';
import { gotoCrudIndex, gotoCrudNew } from '../helpers/admin';
import { resetE2EData } from '../helpers/db';
import { USERS, ALL_GROUPS } from '../helpers/users';

/**
 * Spec 02 — Bootstrap des groupes utilisateur.
 *
 * Acteur : interne (ROLE_INTERNAL → hérite ROLE_GESTIONNAIRE → accès /admin)
 *
 * Crée 5 groupes utilisateur via l'UI EasyAdmin :
 *   ADMIN, CONSULTATION, COLLECTE, GESTIONNAIRE METIER, GESTIONNAIRE APPLICATIF
 *
 * Note : `GroupeUtilisateurCrudController::normalize()` met en minuscules
 *   et remplace les caractères non-alphanumeric. "GESTIONNAIRE METIER"
 *   devient "gestionnaire metier" en DB.
 *
 * Pré-requis : DB rebuilt avec fixtures-e2e.sql (interne actif).
 *              Les 2 groupes par défaut "Aucun" + "En attente" préexistent
 *              (chargés par fixtures.sql).
 */
test.describe('02 — Bootstrap groupes utilisateur', () => {
  // Reset rapide AVANT le test : permet de re-jouer le spec en boucle sans
  // rebuild complet de la DB (équivalent reset entre tests d'intégration Symfony).
  test.beforeAll(() => {
    resetE2EData();
  });

  test('interne crée les 5 groupes utilisateur', async ({ page }) => {
    await login(page, USERS.interne);

    for (const groupe of ALL_GROUPS) {
      await gotoCrudNew(page, 'groupeUtilisateur');
      // Sanity : on est bien sur le formulaire NEW (sinon la suite plante en silence)
      await expect(page).toHaveURL(/\/admin\/groupe-utilisateur\/new/);

      // Sélecteurs basés sur les name= Symfony Form (stables quel que soit le
      // prefix EasyAdmin : `GroupeUtilisateur[...]`, `ea[newForm][...]`, etc.)
      await page.locator('input[name$="[groupeUtilisateur]"]').fill(groupe);
      await page.locator('input[name$="[description]"]').fill(`Groupe E2E ${groupe}`);

      // Boutons "Créer" et "Créer et ajouter un nouvel élément" sont HORS
      // du <form> (toolbar header EasyAdmin liée via attr form="..."). On
      // cible le bouton "Créer" exact pour ne pas matcher l'autre.
      await page.getByRole('button', { name: 'Créer', exact: true }).click();

      // Après création, EasyAdmin redirige vers la liste (ou edit selon config).
      await expect(page).toHaveURL(/\/admin\/groupe-utilisateur(\/|$|\?)/);
    }

    // Vérification finale : les 5 groupes sont visibles dans la liste.
    // Le contrôleur normalise en minuscules → on cherche en lowercase.
    // exact:true évite que "admin" matche aussi la description "Groupe E2E ADMIN".
    await gotoCrudIndex(page, 'groupeUtilisateur');
    for (const groupe of ALL_GROUPS) {
      await expect(page.getByText(groupe.toLowerCase(), { exact: true })).toBeVisible();
    }

    await logout(page);
  });
});
