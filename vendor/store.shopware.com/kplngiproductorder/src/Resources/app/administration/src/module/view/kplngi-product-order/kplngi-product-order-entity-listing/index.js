const { Criteria } = Shopware.Data;

Shopware.Component.extend('kplngi-product-order-entity-listing', 'sw-entity-listing', {
    methods: {
        doSearch() {
            this.loading = true;

            this.items.criteria.addSorting(
                Criteria.sort('product.name')
            );

            return this.repository.search(this.items.criteria, this.items.context).then(this.applyResult);
        },
    }
})
