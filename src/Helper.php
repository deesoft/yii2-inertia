<?php

namespace dee\inertia;

use ReflectionClass;
use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\CompositeUrlRule;
use yii\web\JsExpression;
use yii\web\UrlRule;

/**
 * Description of Helper
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Helper
{

    /**
     *
     * @return array
     */
    public static function getUrlRules()
    {
        $result = [];
        foreach (Yii::$app->urlManager->rules as $rule) {
            if ($rule instanceof UrlRule) {
                $result[] = self::getRuleInfo($rule);
            } elseif ($rule instanceof CompositeUrlRule) {
                self::getRuleRecursive($rule, $result);
            }
        }
        return $result;
    }

    /**
     *
     * @param UrlRule $rule
     * @return array
     */
    protected static function getRuleInfo($rule)
    {
        $ref = new ReflectionClass($rule);
        $props = ['placeholders', '_template', '_routeRule', '_paramRules', '_routeParams'];
        $row = [
            //'pattern' => $rule->pattern,
            'route' => $rule->route,
            'verb' => $rule->verb,
            'suffix' => $rule->suffix,
            'encodeParams' => $rule->encodeParams,
            'host' => $rule->host,
            'defaults' => $rule->defaults,
        ];
        foreach ($props as $name) {
            $prop = $ref->getProperty($name);
            $prop->setAccessible(true);
            $row[ltrim($name, '_')] = $prop->getValue($rule);
        }
        if ($row['routeRule']) {
            $regex = Html::escapeJsRegularExpression($row['routeRule']);
            $row['routeRule'] = new JsExpression(str_replace('?P<', '?<', $regex));
        }
        if ($row['paramRules']) {
            foreach ($row['paramRules'] as $key => $value) {
                if ($value) {
                    $row['paramRules'][$key] = new JsExpression(Html::escapeJsRegularExpression($value));
                }
            }
        }

        foreach (['paramRules', 'defaults', 'routeParams', 'placeholders'] as $prop) {
            if(empty($row[$prop])){
                $row[$prop] = (object)[];
            }
        }
        return $row;
    }

    /**
     *
     * @param CompositeUrlRule $rule
     * @param array $result
     */
    protected static function getRuleRecursive($rule, &$result)
    {
        $ref = new ReflectionClass($rule);
        $prop = $ref->getProperty('rules');
        $prop->setAccessible(true);
        $rules = $prop->getValue($rule);
        foreach ($rules as $child) {
            if ($child instanceof UrlRule) {
                $result[] = self::getRuleInfo($rule);
            } elseif ($child instanceof CompositeUrlRule) {
                self::getRuleRecursive($child, $result);
            }
        }
    }

    public static function getUrlManagerVar()
    {
        $manager = Yii::$app->urlManager;
        return [
            'rules' => static::getUrlRules(),
            'suffix' => $manager->suffix,
            'baseUrl' => $manager->showScriptName ? $manager->getScriptUrl() : $manager->getBaseUrl(),
            'home' => $manager->showScriptName ? $manager->getScriptUrl() : $manager->getBaseUrl() . '/',
        ];
    }

    /**
     *
     * @return JsExpression
     */
    public static function jsUrlTo()
    {
        $manager = Yii::$app->urlManager;
        $suffix = Json::htmlEncode($manager->suffix);
        $baseUrl = Json::htmlEncode($manager->showScriptName ? $manager->getScriptUrl() : $manager->getBaseUrl());
        $rules = Json::htmlEncode(static::getUrlRules());
        $js = <<<JS
(() => {
    const suffix = $suffix;
    const baseUrl = $baseUrl;
    const rules = $rules;
    const caches = {};

    function _stringify(obj, prefix = "") {
        return Object.keys(obj)
            .map(key => {
                const value = obj[key];
                const prefixedKey = prefix ? `\${prefix}[\${key}]` : key;
                if (typeof value === "object") {
                    return _stringify(value, prefixedKey);
                } else {
                    return encodeURIComponent(prefixedKey) + "=" + encodeURIComponent(value);
                }
            })
            .join("&");
    }

    const stringify = window.stringify || _stringify;

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
                if (typeof rule.placeholders[name] != 'undefined' && `\${value}` == '') {
                    _params[name] = '';
                } else {
                    return false;
                }
            }
            if (`\${_params[name]}` == `\${value}`) {
                delete _params[name];
                if (typeof rule.paramRules[name] != 'undefined') {
                    tr[`<\${name}>`] = '';
                }
            } else if (typeof rule.paramRules[name] == 'undefined') {
                return false;
            }
        }

        for (const [name, regex] of Object.entries(rule.paramRules)) {
            if (typeof _params[name] != 'undefined' && !Array.isArray(_params[name]) && (!regex || regex.test(_params[name]))) {
                tr[`<\${name}>`] = rule.encodeParams ? encodeURIComponent(_params[name]) : _params[name];
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

    const toUrl = (path, params, method) => {
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
                result = `\${baseUrl}/\${url}`;
            }
        } else {
            url = path + (suffix || '');
            let query = params ? stringify(params) : '';
            if (query) {
                url += '?' + query;
            }
            result = `\${baseUrl}/\${url}`;
        }
        caches[keyCache] = result;
        return result;
    };

    const methods = ['get', 'head', 'post', 'put', 'patch', 'delete'];
    toUrl.base = baseUrl;
    for(const method of methods){
        toUrl[method] = (path, params) => toUrl(path, params, method);
    }
    toUrl.back = () => window.history.back();
    return toUrl;
})()
JS;
        return new JsExpression($js);
    }
}
