describe('Tests de la page de connexion', () => {
  beforeEach(() => {
    cy.visit('/login');
    cy.fixture('users').as('users'); // Charger les données des utilisateurs
  });

  it('devrait afficher une erreur avec des identifiants incorrects', function() {
    cy.get('input[name="mel"]').type(this.users.invalidUser.mel);
    cy.get('input[name="password"]').type(this.users.invalidUser.password);
    cy.get('button[type="submit"]').click();
    cy.contains('Identifiants incorrects').should('be.visible');
  });

  it('devrait permettre une connexion avec des identifiants corrects', function() {
    cy.get('input[name="mel"]').type(this.users.validUser.mel);
    cy.get('input[name="password"]').type(this.users.validUser.password);
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/dashboard');
  });
});
