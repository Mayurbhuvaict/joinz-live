const {Component, Mixin} = Shopware;
const { Criteria } = Shopware.Data;
import template from './list.html.twig';

Component.register('logo-upload-crud-list', {
    template,

    inject: [
        'repositoryFactory',
        'filterFactory',
        'acl',
        'feature'
    ],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('placeholder')
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            isLoading: false,
            filterLoading: false,
            showDeleteModal: false,
            total: 0,
            page: 1,
            limit: 30,
            logoUpload: null,
            storeKey: 'grid.filter.logo_uploads',
            defaultFilters: [
                'logo-upload-email-filter'
            ],
            activeFilterNumber: 0,
            filterCriteria: [],
            availableEmails: [],
            availableEmailsFilter: [],
        };
    },


    computed: {
        logoUploadRepository() {
            return this.repositoryFactory.create('logo_uploads');
        },

        logoUploadColumns() {
            return this.getLogoUploadColumns();
        },

        listFilters() {
            return this.filterFactory.create('logo_uploads', {
                'logo-upload-email-filter': {
                    property: 'email',
                    type: 'multi-select-filter',
                    label: 'Email',
                    placeholder: 'Email',
                    valueProperty: 'key',
                    labelProperty: 'key',
                    options: this.availableEmails,
                }
            });
        },

        filterSelectCriteria() {
            const criteria11 = new Criteria(1, 1);
            criteria11.addFilter(Criteria.not(
                'AND',
                [Criteria.equals('email', null)],
            ));
            criteria11.addAggregation(Criteria.terms('email', 'email', null, null, null));
            return criteria11;

        },

        defaultCriteria() {
            const defaultCriteria = new Criteria(this.page, this.limit);
            // eslint-disable-next-line vue/no-side-effects-in-computed-properties


            defaultCriteria.setTerm(this.term);
            defaultCriteria.addSorting(Criteria.sort('createdAt', 'DESC'));


            this.filterCriteria.forEach(filter => {
                defaultCriteria.addFilter(filter);
            });

            return defaultCriteria;
        },
    },

    watch: {
        defaultCriteria: {
            handler() {
                this.getList();
            },
            deep: true,
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            return this.loadFilterValues();
        },

        async getList() {
            this.isLoading = true;

            const criteria = await Shopware.Service('filterService')
                .mergeWithStoredFilters(this.storeKey, this.defaultCriteria);

            this.activeFilterNumber = criteria.filters.length;

            try {
                const items = await this.logoUploadRepository.search(this.defaultCriteria);

                this.total = items.total;
                this.logoUpload = items;
                this.isLoading = false;
                this.selection = {};
            } catch {
                this.isLoading = false;
            }
        },

        loadFilterValues() {
            this.filterLoading = true;

            return this.logoUploadRepository.search(this.filterSelectCriteria)
                .then(({ aggregations }) => {
                    this.availableEmails = aggregations.email.buckets;
                    this.filterLoading = false;

                    return aggregations;
                }).catch(() => {
                    this.filterLoading = false;
                });
        },

        onChangeAvailableEmailsFilter(value) {
            this.availableEmailsFilter = value;
            this.getList();
        },

        updateCriteria(criteria) {
            this.page = 1;
            this.filterCriteria = criteria;
        },

        onDelete(id) {
            this.showDeleteModal = id;
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        onConfirmDelete(id) {
            this.showDeleteModal = false;

            return this.logoUploadRepository.delete(id, Shopware.Context.api).then(() => {
                this.getList();
            });
        },

        getLogoUploadColumns() {
            return[{
                property: 'mediaId',  // column property
                dataIndex: 'mediaId',
                label: 'Media ID', // column label (snippets used for labels)
                allowResize: true,
                sortable: false,
            },
                {
                    property: 'firstName',
                    dataIndex: 'firstName',
                    label: 'First Name',
                    allowResize: true,
                    sortable: false,
                },
                {
                    property: 'lastName',
                    dataIndex: 'lastName',
                    label: 'Last Name',
                    allowResize: true,
                    sortable: false,
                },
                {
                    property: 'email',
                    dataIndex: 'email',
                    label: 'Email',
                    allowResize: true,
                    sortable: false,
                },
                {
                    property: 'additionalInfo',
                    dataIndex: 'additionalInfo',
                    label: 'Additional Info',
                    allowResize: true,
                    sortable: false,
                },]
        }
    }
});
