describe('Tests de la page de connexion', () => {
  it('devrait permettre une connexion avec des identifiants corrects', function() {
    cy.fixture('users').then((users) => {
      cy.login(users.validUser.mel, users.validUser.password);
    });
    cy.url().should('include', '/dashboard');
  });
});
