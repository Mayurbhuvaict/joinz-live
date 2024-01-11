import template from './sw-product-detail-imprint.html.twig';
import './sw-product-detail-imprint.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sw-product-detail-imprint', {
    template,

    metaInfo() {
        return {
            title: 'Imprint'
        };
    },

    inject: ['repositoryFactory', 'acl'],

    props: {
        allowEdit: {
            type: Boolean,
            required: false,
            default: true
        }
    },

    data() {
        return {
            crossSelling: null
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product'
        ]),

        ...mapGetters('swProductDetail', [
            'isLoading'
        ])
    },

    watch: {
        product(product) {
            product.crossSellings.forEach((item) => {
                console.log('=========================');
                console.log(item);
                console.log('=========================');
                if (item.assignedProducts.length > 0) {
                    return;
                }

                this.loadAssignedProducts(item);
            });
        }
    },

    methods: {
        loadAssignedProducts(crossSelling) {
            const repository = this.repositoryFactory.create(
                crossSelling.assignedProducts.entity,
                crossSelling.assignedProducts.source
            );

            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('crossSellingId', crossSelling.id))
                .addSorting(Criteria.sort('position', 'ASC'))
                .addAssociation('product');

            repository.search(
                criteria,
                { ...Shopware.Context.api, inheritance: true }
            ).then((assignedProducts) => {
                crossSelling.assignedProducts = assignedProducts;
            });

            return crossSelling;
        },


    }
});
