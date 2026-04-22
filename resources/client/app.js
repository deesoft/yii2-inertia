import vuetify from './plugins/vuetify';
import main from './plugins/main';
import { createInertiaApp } from '@inertiajs/vue3';

import 'vuetify/lib/styles/main.sass';
import './assets/css/app.css';

import Default from './layouts/Default.vue';

createInertiaApp({
    pages: {
        path: './pages',
    },
    layout: () => Default,
    withApp(app) {
        app.use(main)
             .use(vuetify);
    },
});