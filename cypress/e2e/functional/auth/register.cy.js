describe('Page d\'enregistrement d\'un compte', () => {

  beforeEach(() => {
    cy.visit('/register'); // Remplacez par le chemin correct de votre page d'enregistrement
  });

  it('devrait afficher tous les champs du formulaire d\'enregistrement', () => {
    cy.get('input[name="nom"]').should('be.visible');
    cy.get('input[name="prenom"]').should('be.visible');
    cy.get('input[name="courriel"]').should('be.visible');
    cy.get('input[name="plainPassword_first"]').should('be.visible');
    cy.get('input[name="plainPassword_second"]').should('be.visible');
  });

  it('devrait afficher des erreurs de validation si les champs sont laissés vides', () => {
    cy.get('button[type="submit"]').click(); // Tente de soumettre le formulaire

    cy.contains('Merci de saisir votre nom.').should('be.visible');
    cy.contains('Merci de saisir votre prénom.').should('be.visible');
    cy.contains('Merci de saisir votre mot de passe.').should('be.visible');
  });

  it('devrait afficher des erreurs si les champs ne respectent pas les contraintes', () => {
    // Nom trop court
    cy.get('input[name="nom"]').type('A');
    cy.get('input[name="prenom"]').type('B');
    cy.get('input[name="courriel"]').type('test@test.com');
    cy.get('input[name="plainPassword_first"]').type('12345');
    cy.get('input[name="plainPassword_second"]').type('12345');

    cy.get('button[type="submit"]').click();

    // Vérifier que les erreurs de longueur s'affichent
    cy.contains('Le nom doit comporter au moins 2 caractères.').should('be.visible');
    cy.contains('Le prénom doit comporter au moins 2 caractères.').should('be.visible');
    cy.contains('Votre mot de passe doit comporter au moins 8 caractères.').should('be.visible');
  });

  it('devrait permettre l\'enregistrement avec des informations valides', () => {
    // Remplir le formulaire avec des données valides
    cy.get('input[name="nom"]').type('Dupont');
    cy.get('input[name="prenom"]').type('Jean');
    cy.get('input[name="courriel"]').type('jean.dupont@test.com');
    cy.get('input[name="plainPassword_first"]').type('motdepasse123');
    cy.get('input[name="plainPassword_second"]').type('motdepasse123');

    cy.get('button[type="submit"]').click();

    // Vérifier la redirection ou un message de succès
    cy.url().should('include', '/dashboard'); // Redirige vers le tableau de bord ou la page post-inscription
    // Ou vérifier un message de confirmation
    cy.contains('Votre compte a été créé avec succès').should('be.visible');
  });

  it('devrait afficher une erreur si les mots de passe ne correspondent pas', () => {
    cy.get('input[name="plainPassword_first"]').type('motdepasse123');
    cy.get('input[name="plainPassword_second"]').type('motdepasse456');

    cy.get('button[type="submit"]').click();

    // Vérifier le message d'erreur sur la correspondance des mots de passe
    cy.contains('Les mots de passe doivent correspondre.').should('be.visible');
  });
});
