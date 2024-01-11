const { Module } = Shopware;
import './page/logo-upload-crud-list'; // Component registered for module which will used for showing data


Module.register('logo-upload-crud', {
    type: 'plugin',
    title: 'Logo Upload Crud',
    description: 'Listing of logo uplaods',

    routes: {
        'list': {
            component: 'logo-upload-crud-list',
            path: 'list',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },

    navigation: [{
            label: 'Logo Upload List',
            color: '#ff68b4',
            path: 'logo.upload.crud.list',
            icon: 'default-shopping-paper-bag-product',
            parent: 'sw-content',
            position: 100
    }]
});
