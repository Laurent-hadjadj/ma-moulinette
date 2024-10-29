describe('Vérification des liens', () => {
  it('doit avoir des href valides', () => {
    cy.visit('/home');
    cy.get('a').each(($el) => {
      const href = $el.prop('href');
      cy.request(href).its('status').should('eq', 200);
    });
  });
});
