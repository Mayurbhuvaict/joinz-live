import template from './kplngi-product-order.html.twig';
import './kplngi-category-view.scss';

const Criteria = Shopware.Data.Criteria;

Shopware.Component.register('kplngi-product-order', {
    template,

    metaInfo() {
        return {
            title: this.$tc('kplngi-product-order.title')
        }
    },

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        Shopware.Mixin.getByName('notification')
    ],

    data() {
        return {
            orderSwitch: false,
            products: null,
            isLoading: false,
            processSuccess: false,
            switchDisabled: true,
            productCategoryPositions: null,
            syncService: null,
            httpClient: null,
            searchTerm: null
        }
    },

    computed: {
        category() {
            return Shopware.State.get('swCategoryDetail').category;
        },

        productCategoryPositionRepository() {
            return this.repositoryFactory.create('kplngi_productcategoryposition');
        },

        productRepository() {
            return this.repositoryFactory.create('product', this.category.products.source);
        },

        categoryRepository() {
            return this.repositoryFactory.create('category');
        },

        orderActiveRepository() {
            return this.repositoryFactory.create('kplngi_orderactive');
        },

        columns() {
            return [
                {
                    property: 'position',
                    dataIndex: 'position',
                    label: this.$tc('kplngi-product-order.table.priority'),
                    inlineEdit: 'number',
                    primary: true
                }, {
                    property: 'product.name',
                    dataIndex: 'product.name',
                    label: this.$tc('kplngi-product-order.table.product')
                }, {
                    property: 'product.productNumber',
                    dataIndex: 'product.productNumber',
                    label: this.$tc('kplngi-product-order.table.productNumber')
                }
            ]
        }
    },

    created() {
        this.syncService = Shopware.Service('syncService');
        this.httpClient = this.syncService.httpClient;

        let isEnabled = this.isCustomOrderEnabled();

        isEnabled.then((ids) => {
            if (ids.total > 0) {
                this.orderSwitch = true;
                this.loadCategoryProductPositions();
            } else {
                this.orderSwitch = false;
            }
            this.switchDisabled = false;
        });
    },

    methods: {
        onSwitch(orderSwitch) {
            if (orderSwitch) {
                this.switchDisabled = true;
                this.saveCategoryProductsOrder();
            } else {
                this.switchDisabled = true;
                this.deleteCategoryProductsPositions();
            }
        },

        saveCategoryProductsOrder(currentPage = 1, productCategoryPositions = []) {
            this.isLoading = true;
            this.httpClient.get(`/kplngi/productorder/init/${ this.category.id }`, {
                headers: this.syncService.getBasicHeaders()
            }).then(this.loadCategoryProductPositions);
        },

        isCustomOrderEnabled() {
            let criteria = new Criteria();
            criteria.addFilter(Criteria.equals('categoryId', this.category.id));

            return this.orderActiveRepository.searchIds(criteria, Shopware.Context.api)
        },

        deleteCategoryProductsPositions() {
            this.isLoading = true;
            this.httpClient.get(`/kplngi/productorder/delete/${ this.category.id }`, {
                headers: this.syncService.getBasicHeaders()
            }).then(() => {
                this.productCategoryPositions = null;
                this.isLoading = false;
                this.switchDisabled = false;
                this.searchTerm = null;
            });
        },

        loadCategoryProductPositions() {
            this.isLoading = true;
            let criteria = new Criteria();
            criteria.addFilter(Criteria.equals('categoryId', this.category.id));
            criteria.addAssociation('product');
            this.productCategoryPositionRepository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                    this.productCategoryPositions = result;
                    this.isLoading = false;
                    this.switchDisabled = false;
                });
        },

        setCustomSortedCategory() {
            let entity = this.orderActiveRepository.create(Shopware.Context.api);
            entity.categoryId = this.category.id;
            this.orderActiveRepository.save(entity, Shopware.Context.api)
        },

        removeCustomSortedCategory() {
            let criteria = new Criteria();
            criteria.addFilter(Criteria.equals('categoryId', this.category.id));

            this.searchTerm = null;

            this.orderActiveRepository.searchIds(criteria, Shopware.Context.api).then((ids) => {
                this.orderActiveRepository.delete(ids.data[0], Shopware.Context.api);
            });
        },

        noProductsInCategory() {
            this.createNotificationInfo({
                title: this.$tc('kplngi-product-order.notification'),
                message: " "
            });
        },

        refreshProducts() {
            this.isLoading = true;
            this.searchTerm = null;
            this.httpClient.get(`/kplngi/productorder/refresh/${ this.category.id }`, {
                headers: this.syncService.getBasicHeaders()
            }).then(this.loadCategoryProductPositions);
        },

        filterProducts(value) {
            this.searchTerm = value;

            if (value === '') {
                this.loadCategoryProductPositions();
                return;
            }

            this.isLoading = true;
            let criteria = new Criteria();

            criteria.addAssociation('product');

            criteria.addFilter(Criteria.multi('OR',
                [
                    Criteria.contains('product.name', value),
                    Criteria.contains('product.productNumber', value),
                    Criteria.contains('position', value)
                ]
            ));

            criteria.setTerm(value);

            criteria.addFilter(Criteria.equals('categoryId', this.category.id));
            this.productCategoryPositionRepository
                .search(criteria, Shopware.Context.api)
                .then((result) => {
                        if (this.searchTerm === result.criteria.term) {
                            this.productCategoryPositions = result;
                            this.isLoading = false;
                        }
                    }
                );
        }
    }
});
