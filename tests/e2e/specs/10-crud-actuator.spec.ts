import { test, expect } from '../helpers/fixtures';
import { login } from '../helpers/auth';
import { resetAndSeedForCrudTransverse } from '../helpers/db';
import { USERS } from '../helpers/users';

/**
 * Spec 10 — CRUD Actuator (contrôleur custom, pas EasyAdmin, ROLE_ACTUATOR).
 *
 * Acteur : Nathan (ROLE_COLLECTE natif + ROLE_ACTUATOR cumulé via
 * resetAndSeedForCrudTransverse(), cf. helpers/db.ts).
 *
 * Piège réel identifié avant d'écrire ce test : la création fait un vrai
 * PING RÉSEAU bloquant (ActuatorController::urlActuatorEstJoignable()). Un
 * ping vers le serveur e2e lui-même se bloque en auto-deadlock (un seul
 * worker PHP-CGI, occupé par la requête en cours) jusqu'au timeout (3s),
 * classé "injoignable" à tort. Une URL externe introduirait une dépendance
 * réseau réelle dans les tests. Solution retenue : `SonarFixtureClientService`
 * (déjà le double de test pour SonarQube) surcharge maintenant aussi
 * `httpActuator()` pour renvoyer un succès fixe sans appel réseau — l'URL
 * saisie ici n'a donc plus besoin d'être réellement joignable, seulement de
 * respecter le format attendu par `Assert\Url` (schéma http/https, ≥12 car.).
 *
 * Pas d'id custom sur les champs du formulaire (ActuatorFormType n'a pas de
 * getBlockPrefix() ni d'attribut id explicite) : sélecteurs par name$= plutôt
 * que par id, pour ne pas dépendre du préfixe déduit par Symfony.
 *
 * `ActuatorController::actuatorInfo()` pré-ajoute une ActuatorInfo vide à
 * l'entité avant de créer le formulaire (contrairement à une CollectionType
 * vide par défaut) : la ligne actuatorInfoCle/actuatorInfoDescription est
 * donc déjà présente au chargement de la page, pas besoin de cliquer
 * "Ajouter clé".
 */
test.describe('10 — CRUD Actuator', () => {
  test.beforeAll(() => {
    resetAndSeedForCrudTransverse();
  });

  test.setTimeout(60_000);

  test('Nathan ajoute un point d\'accès Actuator', async ({ page }) => {
    // Le footer fixe (.footer-fixed) chevauche le bouton de soumission sur la
    // hauteur de viewport par défaut de Playwright — un click({force:true})
    // finit par cliquer sur le footer (dispatch à la coordonnée du bouton,
    // mais c'est le footer qui reçoit l'événement en avant-plan). Une
    // fenêtre plus haute évite le chevauchement, sans changer la logique
    // testée.
    await page.setViewportSize({ width: 1280, height: 1400 });
    await login(page, USERS.nathan);

    await page.goto('/actuator/info');
    await expect(page).toHaveURL(/\/actuator\/info/);

    await page.locator('input[name$="[nomApplication]"]').fill('Application E2E Tetris');
    await page.locator('input[name$="[mavenKey]"]').fill('tetris:TetrisGame');
    await page.locator('input[name$="[personne]"]').fill('Nathan JONES');
    // Le ping réel est court-circuité par SonarFixtureClientService (voir note
    // en tête de fichier) : seul le format compte ici.
    await page.locator('input[name$="[url]"]').fill('http://tetris-actuator.example:8081/app');
    // actuatorUser est "optionnel" côté contrainte Symfony, mais le widget
    // rendu porte un attribut HTML5 `required` — vide, il bloque
    // silencieusement la soumission native (tooltip navigateur, pas
    // d'erreur serveur). On le remplit pour ne pas dépendre de ce détail.
    await page.locator('input[name$="[actuatorUser]"]').fill('actuator-e2e');
    await page.locator('input[name$="[actuatorPassword]"]').fill('MotDePasseE2E2026!');
    await page.locator('input[name$="[actuatorInfoCle]"]').first().fill('app.version');
    await page.locator('input[name$="[actuatorInfoDescription]"]').first().fill('Version de l\'application');

    await page.locator('#valider-formulaire-enregistrement').click();

    // Succès -> redirect /actuator + flash "ajoutée à l'inventaire".
    await expect(page).toHaveURL(/\/actuator$/);
    await expect(page.locator('.js-flash-box .callout-message')).toContainText(
      "ajoutée à l'inventaire Actuator"
    );
    // La liste n'affiche pas la clé Maven (colonnes Application/URL/Personne/Date).
    await expect(page.locator('body')).toContainText('Application E2E Tetris');
    await expect(page.locator('body')).toContainText('Nathan JONES');
  });
});
