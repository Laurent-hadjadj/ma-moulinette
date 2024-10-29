it('devrait permettre de réinitialiser le mot de passe avec des données valides', () => {
  cy.fixture('users').then((users) => {
    cy.get('input[name="ancienMotDePasse"]').type(users.user.ancienMotDePasse);
    cy.get('input[name="plainPassword[first]"]').type(users.user.nouveauMotDePasse);
    cy.get('input[name="plainPassword[second]"]').type(users.user.nouveauMotDePasse);
  });

  cy.get('button[type="submit"]').click();
  cy.url().should('include', '/success');
});
