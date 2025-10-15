import { readdirSync, writeFileSync } from "fs";
import { dirname, resolve, extname, sep } from "path";

function listFiles(dir, prefix = '') {
    const fullPath = resolve(dir);
    const entries = readdirSync(fullPath, { withFileTypes: true });
    const results = {};

    for (const entry of entries) {
        const res = resolve(fullPath, entry.name);
        if (entry.isDirectory()) {
            Object.assign(results, listFiles(res, prefix + entry.name + '/'));
        } else if (extname(res) == ".vue") {
            results[(prefix + entry.name).replace(/\.vue$/, '')] = res;
        }
    }
    return results;
}


export default function InertiaPages(config) {
    function toContent(app, toFile = false) {
        if (toFile && !app.output) {
            return;
        }
        const aliases = (toFile && app.output) ? resolveAlias(app.output) : null;
        function toRelative(f) {
            for (let i = 0; i < aliases.length; i++) {
                if (f.startsWith(aliases[i].path)) {
                    return aliases[i].alias + f.substring(aliases[i].length);
                }
            }
            return f;
        }
        const pages = Object.entries(listFiles(app.pages));
        let ctx = `import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';

`;
        if (app.eager) {
            pages.forEach(([, f], ix) => {
                f = aliases ? toRelative(f) : f;
                ctx += `import * as m_page_${ix} from ${JSON.stringify(f)};\n`;
            });
            ctx += '\n';
        }
        let returnPage = `return page;`;
        if (app.layouts) {
            const ls = [];
            const layouts = resolveLayout(app.layouts);
            layouts.forEach(([k, f], ix) => {
                f = aliases ? toRelative(f) : f;
                ctx += `import m_layout_${ix} from ${JSON.stringify(f)};\n`;
                ls.push(`    ${k}: m_layout_${ix}`);
            });
            ctx += `
const layouts = {
${ls.join(',\n')}
};

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

`;
            returnPage = `return (typeof page === 'function') ? page().then(applyLayout) : applyLayout(page);`;
        }

        ctx += 'const pages = {\n';
        pages.forEach(([k, f], ix) => {
            f = aliases ? toRelative(f) : f;
            if (app.eager) {
                ctx += `    '${k}': m_page_${ix},\n`;
            } else {
                ctx += `    '${k}': () => import(${JSON.stringify(f)}),\n`;
            }
        });
        ctx += `};

export function resolvePage(name){
    const page = pages[name];
    if (typeof page === 'undefined') {
        throw new Error(\`Page not found: \${name}\`);
    }
    ${returnPage}
}

export default function InitApp(config){
    config = config || {};
    createInertiaApp({
        id: config.id || 'app',
        resolve: resolvePage,
        setup({ el, App, props, plugin }) {
            const app = createApp({ render: () => h(App, props) }).use(plugin);
            config.setup && config.setup(app);
            app.mount(el);
        },
    });
}
`;
        if (toFile) {
            writeFileSync(resolve(app.output), ctx, 'utf8');
        } else {
            return ctx;
        }
    }

    function resolveLayout(layouts) {
        const result = [];
        Object.entries(layouts).forEach(([k, f]) => {
            k = /^\w+$/.test(k) ? k : JSON.stringify(k);
            result.push([k, resolve(f)]);
        });
        return result;
    }
    function resolveAlias(output) {
        const fullPath = resolve(output);
        let p = dirname(fullPath);
        const aliases = [
            { path: p + sep, alias: './', length: p.length + 1 }
        ];
        let alias = '../';
        while (true) {
            let p2 = dirname(p);
            if (p2 == p) {
                break;
            }
            p = p2;
            p2 = (p + sep).replace(/\/+/g, '/');
            aliases.push({ path: p2, alias, length: p2.length });
            alias += '../';
        }
        return result;
    }
    function resolveApps(config) {
        if (typeof config === 'string') {
            config = { pages: config };
        }
        const configs = Array.isArray(config) ? config : [config];
        const resultApps = [];
        configs.forEach(source => {
            if (typeof source === 'string') {
                source = { pages: source };
            }
            const id = 'virtual:yii2-inertia' + (source.name ? '-' + source.name : '');
            const app = {
                id,
                mId: '\0' + id,
                pages: source.pages,
                layouts: source.layouts,
                eager: source.eager,
            }
            resultApps.push(app);
        });
        return resultApps;
    }

    const state = {
        apps: resolveApps(config),
    };

    state.apps.forEach(app => {
        if (app.output) {
            toContent(app, true);
        }
    });

    const name = 'vue-yii2-inertia';
    return {
        name, // required, will show up in warnings and errors
        resolveId(id) {
            for (var i in state.apps) {
                if (state.apps[i].id === id) {
                    return state.apps[i].mId;
                }
            }
        },
        load(id) {
            for (var i in state.apps) {
                if (state.apps[i].mId === id) {
                    return toContent(state.apps[i], false);
                }
            }
        },
        handleHotUpdate({ server, modules }) {
            state.apps = resolveApps(config);
            state.apps.forEach(app => {
                if (app.output) {
                    toContent(app, true);
                }
            });
            //return [];
        }
    }
}