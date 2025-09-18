import vuetify from './plugins/vuetify';
import mainPlugin from './plugins/main';
import layouts from './plugins/layouts';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue'

import 'vuetify/lib/styles/main.sass';
import './assets/css/app.css';

function applyLayout(page) {
    if(page.default){
        var layout = page.default.layout;
        if(layout === undefined){
            page.default.layout = layouts.default;
        } else if(typeof layout === 'string'){
            page.default.layout = layouts[layout];
        } else if(layout === false){
            page.default.layout = null;
        }
    }
    return page;
}

const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
function resolvePageComponent(name) {
    const path = `./pages/${name}.vue`;
    const page = pages[path];
    if (typeof page === 'undefined') {
        throw new Error(`Page not found: ${path}`);
    }
    if (typeof page === 'function') {
        return page().then(page => applyLayout(page));
    } else {
        return applyLayout(page);
    }
}
createInertiaApp({
    id:'app',
    resolve: resolvePageComponent,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(mainPlugin)
            .use(vuetify)
            .mount(el)
    },
});