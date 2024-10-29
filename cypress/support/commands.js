Cypress.Commands.add('login', (email, password) => {
  cy.visit('/login');
  cy.get('input[name="mel"]').type(email);
  cy.get('input[name="password"]').type(password);
  cy.get('button[type="submit"]').click();
});
