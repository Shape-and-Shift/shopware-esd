import ProductPageObject from '../support/pages/module/sw-product-page-object';

describe('Testing the ESD plugin', function() {

    beforeEach(() => {
        cy.loginViaApi()
            .then(() => {
                return cy.createProductFixture();
            }).then(() => {
                cy.visit(`${Cypress.env('admin')}#/sw/product/index`);
                cy.contains('Product name').click();
            })
    });
    
    it('Open ESD tab', function() {
        const page = new ProductPageObject();

        cy.get('.sw-tabs-item[title="ESD"]').click()
        cy.get('.sas-product-detail-esd__generated-esd').should('be.visible')
    })
})