it('devrait permettre l\'enregistrement avec des informations valides', () => {
  cy.fixture('users').then((users) => {
    cy.get('input[name="nom"]').type(users.newUser.nom);
    cy.get('input[name="prenom"]').type(users.newUser.prenom);
    cy.get('input[name="courriel"]').type(users.newUser.courriel);
    cy.get('input[name="plainPassword_first"]').type(users.newUser.password);
    cy.get('input[name="plainPassword_second"]').type(users.newUser.password);
  });

  cy.get('button[type="submit"]').click();
  cy.url().should('include', '/dashboard');
});
