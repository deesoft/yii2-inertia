function initYiiUrl(urlManager) {
    const { suffix, baseUrl, rules, base, home } = {
        rules: [],
        suffix: null,
        baseUrl: '',
        base: '',
        home: '/',
        ...(urlManager || {}),
    };
    const caches = {};

    function _stringify(obj, prefix = "") {
        return Object.keys(obj)
            .map(key => {
                const value = obj[key];
                const prefixedKey = prefix ? `${prefix}[${key}]` : key;
                if (typeof value === "object") {
                    return _stringify(value, prefixedKey);
                } else {
                    return encodeURIComponent(prefixedKey) + "=" + encodeURIComponent(value);
                }
            })
            .join("&");
    }
    function stringify(obj){
        return window.stringify ? window.stringify(obj) : _stringify(obj);
    }

    function trimSlashes(url) {
        if (/^\/\//.test(url)) {
            return '//' + url.replace(/^\/+/, '').replace(/\/+$/, '');
        }
        return url.replace(/^\/+/, '').replace(/\/+$/, '');
    }
    function createUrl(rule, route, params, method) {
        if (rule.verb && rule.verb.indexOf(method) < 0) {
            return false;
        }
        const _params = { ...(params || {}) };
        const tr = {};
        if (route != rule.route) {
            let match = rule.routeRule ? route.match(rule.routeRule) : null, matches = {};
            if (match) {
                Object.entries(match.groups).forEach(([key, value]) => {
                    if (rule.placeholders[key]) {
                        matches[rule.placeholders[key]] = value;
                    } else {
                        matches[key] = value;
                    }
                });
                Object.entries(rule.routeParams).forEach(([key, value]) => {
                    if (typeof rule.defaults[key] !== 'undefined' && rule.defaults[key] == matches[key]) {
                        tr[value] = '';
                    } else {
                        tr[value] = matches[key];
                    }
                });
            } else {
                return false;
            }
        }

        for (const [name, value] of Object.entries(rule.defaults)) {
            if (typeof rule.routeParams[name] != 'undefined') {
                continue;
            }
            if (_params[name] === undefined || _params[name] === null) {
                if (typeof rule.placeholders[name] != 'undefined' && `${value}` == '') {
                    _params[name] = '';
                } else {
                    return false;
                }
            }
            if (`${_params[name]}` == `${value}`) {
                delete _params[name];
                if (typeof rule.paramRules[name] != 'undefined') {
                    tr[`<${name}>`] = '';
                }
            } else if (typeof rule.paramRules[name] == 'undefined') {
                return false;
            }
        }

        for (const [name, regex] of Object.entries(rule.paramRules)) {
            if (typeof _params[name] != 'undefined' && !Array.isArray(_params[name]) && (!regex || regex.test(_params[name]))) {
                tr[`<${name}>`] = rule.encodeParams ? encodeURIComponent(_params[name]) : _params[name];
                delete _params[name];
            } else {
                return false;
            }
        }

        let url = rule.template;
        Object.entries(tr).forEach(([name, value]) => {
            url = url.replace(name, value);
        });
        url = trimSlashes(url);
        if (rule.host) {
            let p = url.indexOf('/', 8);
            if (p > -1) {
                url = url.substring(0, p) + url.substring(p).replace(/\/+/, '/');
            }
        } else if (url.indexOf('//') > -1) {
            url = url.replace(/\/+/, '/');
        }
        if (url !== '' && (suffix || rule.suffix)) {
            url += (suffix || rule.suffix);
        }
        const query = stringify(_params);
        if (query) {
            url += '?' + query;
        }
        return url;
    }

    const yiiUrl = (path, params, method) => {
        path = path.replace(/^\/+/, '').replace(/\/+$/, '');
        method = method ? method.toUpperCase() : 'GET';
        const keyCache = method + ':' + path + '?' + stringify(params || {});
        if (typeof caches[keyCache] !== 'undefined') {
            return caches[keyCache];
        }
        let url = false;
        for (const rule of rules) {
            if ((url = createUrl(rule, path, params, method)) !== false) {
                break;
            }
        }
        let result;
        if (url !== false) {
            if (url.includes("://")) {
                const pos = url.indexOf("/", 8); // Find first '/' after 'https://'
                if (baseUrl !== "" && pos !== -1) {
                    result = url.slice(0, pos) + baseUrl + url.slice(pos);
                } else {
                    result = url + baseUrl;
                }
            } else if (url.startsWith("//")) {
                const pos = url.indexOf("/", 2); // Find first '/' after '//'
                if (baseUrl !== "" && pos !== -1) {
                    result = url.slice(0, pos) + baseUrl + url.slice(pos);
                } else {
                    result = url + baseUrl;
                }
            } else {
                url = url.replace(/^\/+/, ""); // Remove leading slashes
                result = `${baseUrl}/${url}`;
            }
        } else {
            url = path + (suffix || '');
            let query = params ? stringify(params) : '';
            if (query) {
                url += '?' + query;
            }
            result = `${baseUrl}/${url}`;
        }
        caches[keyCache] = result;
        return result;
    };

    const methods = ['get', 'head', 'post', 'put', 'patch', 'delete'];
    yiiUrl.base = base;
    yiiUrl.home = home;
    for (const method of methods) {
        yiiUrl[method] = (path, params) => yiiUrl(path, params, method);
    }
    yiiUrl.back = () => window.history.back();
    yiiUrl.public = (asset) => `${base}/` + (asset || '').replace(/^\/+/, '');
    return yiiUrl;
};