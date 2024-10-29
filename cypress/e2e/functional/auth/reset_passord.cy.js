describe('Page de réinitialisation du mot de passe', () => {

  beforeEach(() => {
    cy.visit('/reset-password'); // Remplacez par l'URL correcte de la page de réinitialisation
  });

  it('devrait afficher les champs de l\'ancien et du nouveau mot de passe', () => {
    cy.get('input[name="ancienMotDePasse"]').should('be.visible');
    cy.get('input[name="plainPassword[first]"]').should('be.visible');
    cy.get('input[name="plainPassword[second]"]').should('be.visible');
  });

  it('devrait afficher des erreurs si les champs sont laissés vides', () => {
    cy.get('button[type="submit"]').click(); // Tente de soumettre le formulaire

    // Vérifiez que les erreurs de validation s'affichent
    cy.contains('Merci de saisir votre mot de passe actuel.').should('be.visible');
    cy.contains('Merci de saisir votre mot de passe.').should('be.visible');
  });

  it('devrait afficher une erreur si le nouveau mot de passe est trop court', () => {
    // Saisir un ancien mot de passe valide mais un nouveau mot de passe trop court
    cy.get('input[name="ancienMotDePasse"]').type('ancienMotDePasse123');
    cy.get('input[name="plainPassword[first]"]').type('short');
    cy.get('input[name="plainPassword[second]"]').type('short');

    cy.get('button[type="submit"]').click();

    // Vérifiez l'affichage des messages d'erreur de longueur de mot de passe
    cy.contains('Votre mot de passe doit comporter au moins 8 caractères.').should('be.visible');
  });

  it('devrait afficher une erreur si les nouveaux mots de passe ne correspondent pas', () => {
    cy.get('input[name="ancienMotDePasse"]').type('ancienMotDePasse123');
    cy.get('input[name="plainPassword[first]"]').type('nouveauMotDePasse123');
    cy.get('input[name="plainPassword[second]"]').type('nouveauMotDePasse456');

    cy.get('button[type="submit"]').click();

    // Vérifiez que le message d'erreur de non-correspondance s'affiche
    cy.contains('invalid.message.motdepasse').should('be.visible');
  });

  it('devrait permettre de réinitialiser le mot de passe avec des données valides', () => {
    // Remplir tous les champs avec des données valides
    cy.get('input[name="ancienMotDePasse"]').type('ancienMotDePasse123');
    cy.get('input[name="plainPassword[first]"]').type('nouveauMotDePasse123');
    cy.get('input[name="plainPassword[second]"]').type('nouveauMotDePasse123');

    cy.get('button[type="submit"]').click();

    // Vérifier la redirection ou un message de succès
    cy.url().should('include', '/success'); // Changez l'URL en fonction de la redirection post-réinitialisation
    cy.contains('Votre mot de passe a été réinitialisé avec succès').should('be.visible');
  });
});
