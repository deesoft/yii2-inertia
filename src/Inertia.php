<?php

namespace dee\inertia;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Response;
use yii\web\View;

class Inertia
{
    /**
     * @var Serializer
     */
    public static $serializer;
    /**
     *
     * @var string[]
     */
    public static $errors = [];
    protected static $shared = [];
    protected static $encriptHistory = false;

    /**
     * Encript history
     * @param bool $value
     */
    public static function encriptHistory($value = true)
    {
        self::$encriptHistory = $value;
    }

    /**
     * Clear history
     */
    public static function clearHistory()
    {
        Yii::$app->session->setFlash('inertia_clear_history', true);
    }

    /**
     * @param string|array $key
     * @param mixed $value
     */
    public static function share($key, $value = null)
    {
        if (is_array($key)) {
            static::$shared = array_merge(static::$shared, $key);
        } elseif (is_scalar($key)) {
            static::$shared[$key] = $value;
        }
    }

    /**
     * @param string $component
     * @param array $params
     * @return Response
     */
    public static function render($component, $params = [])
    {
        $request = Yii::$app->request;
        $response = Yii::$app->response;
        $appParams = Yii::$app->params;

        $shared = ArrayHelper::getValue($appParams, 'inertia.shared', []);
        $params = array_merge($shared, static::$shared, $params);

        list($props, $deferredProps, $mergeProps) = static::resolvePage($component, $params);
        static::$errors = [];
        $props = static::serializer()->serialize($props);
        $errors = Yii::$app->session->getFlash('errors', []);
        foreach (static::$errors as $key => $value) {
            $errors[$key] = $value;
        }
        if ($errors) {
            if ($request->headers->has(Header::ERROR_BAG)) {
                $props['errors'][$request->headers->get(Header::ERROR_BAG)] = (array) $errors;
            } else {
                $props['errors'] = (array) $errors;
            }
        }

        $data = [
            'component' => $component,
            'props' => $props,
            'url' => $request->getUrl(),
            'version' => static::getVersion(),
            'deferredProps' => $deferredProps,
            'mergeProps' => $mergeProps,
            'encryptHistory' => static::$encriptHistory || ArrayHelper::getValue($appParams, 'inertia.encript_history', false),
            'clearHistory' => Yii::$app->session->getFlash('inertia_clear_history'),
        ];

        if ($request->headers->has(Header::INERTIA)) {
            $response->headers->set(Header::INERTIA, 'true');
            $response->format = 'json';
            $response->data = $data;
            return $response;
        }

        $tag = ArrayHelper::getColumn($appParams, 'inertia.tag', 'div');
        $id = ArrayHelper::getColumn($appParams, 'inertia.id', 'app');
        $content = Html::tag($tag, '', ['id' => $id, 'data' => ['page' => $data]]);
        $view = Yii::$app->view;
        $view->on(View::EVENT_BEGIN_PAGE, function ()use ($view) {
            $view->registerJsVar('toUrl', Helper::jsUrlTo());
            Asset::register($view);
        });
        $viewFile = ArrayHelper::getColumn($appParams, 'inertia.viewFile', '@dee/inertia/views/app.php');
        $response->data = $view->render($viewFile, ['content' => $content]);
        $response->format = 'html';
        return $response;
    }

    /**
     *
     * @param string $component
     * @param array|mixed $params
     * @return array
     */
    protected static function resolvePage($component, $params = [])
    {
        list($partial, $only, $except, $reset) = static::resolvePartial($component);
        $props = [];
        $deferredProps = [];
        $mergeProps = [];
        if ($partial) {
            foreach ($params as $key => $value) {
                if ($value instanceof DeferProp && in_array($key, $only)) {
                    $props[$key] = call_user_func($value);
                    if ($value->shouldMerge && !in_array($key, $reset)) {
                        $mergeProps[] = $key;
                    }
                } elseif ($value instanceof AlwaysProp) {
                    $props[$key] = call_user_func($value);
                } elseif ($value instanceof LazyProp) {
                    if (in_array($key, $only)) {
                        $props[$key] = call_user_func($value);
                    }
                } elseif ((!$only || in_array($key, $only)) && (!$except || !in_array($key, $except))) {
                    if ($value instanceof MergeProp) {
                        $props[$key] = call_user_func($value);
                        if (!in_array($key, $reset)) {
                            $mergeProps[] = $key;
                        }
                    } elseif ($value instanceof \Closure || is_callable($value)) {
                        $props[$key] = call_user_func($value);
                    } else {
                        $props[$key] = $value;
                    }
                }
            }
        } else {
            foreach ($params as $key => $value) {
                if ($value instanceof DeferProp) {
                    $deferredProps[$value->group][] = $key;
                } elseif ($value instanceof LazyProp) {
                    continue;
                } elseif ($value instanceof BaseProp || $value instanceof \Closure || is_callable($value)) {
                    $props[$key] = call_user_func($value);
                } else {
                    $props[$key] = $value;
                }
            }
        }
        return [$props, $deferredProps, $mergeProps];
    }

    /**
     * @return Serializer
     */
    public static function serializer()
    {
        if (static::$serializer === null) {
            $config = ArrayHelper::getValue(Yii::$app->params, 'inertia.serializer', []);
            if (is_string($config)) {
                $config = ['class' => $config];
            }
            if(is_array($config) && !isset($config['class'])){
                $config['class'] = Serializer::class;
            }
            static::$serializer = Yii::createObject($config);
        }
        return static::$serializer;
    }

    /**
     *
     * @param \Closure $callback
     * @param string $group
     * @param bool $merge 
     * @return DeferProp
     */
    public static function defer($callback, $group = '', $merge = false)
    {
        return new DeferProp($callback, $group, $merge);
    }

    /**
     *
     * @param mixed|\Closure $value
     * @return MergeProp
     */
    public static function merge($value)
    {
        return new MergeProp($value);
    }

    /**
     *
     * @param mixed|\Closure $value
     * @return AlwaysProp
     */
    public static function always($value)
    {
        return new AlwaysProp($value);
    }

    /**
     *
     * @param \Closure $value
     * @return LazyProp
     */
    public static function lazy($value)
    {
        return new LazyProp($value);
    }

    protected static function resolvePartial($component)
    {
        $request = Yii::$app->request;
        if ($request->headers->get(Header::PARTIAL_COMPONENT) == $component) {
            $only = $request->headers->get(Header::PARTIAL_ONLY);
            $only = $only ? preg_split('/\s*,\s*/', $only, -1, PREG_SPLIT_NO_EMPTY) : null;
            $except = $request->headers->get(Header::PARTIAL_EXCEPT);
            $except = $only ? preg_split('/\s*,\s*/', $except, -1, PREG_SPLIT_NO_EMPTY) : null;
            $reset = $request->headers->get(Header::RESET);
            $reset = $only ? preg_split('/\s*,\s*/', $reset, -1, PREG_SPLIT_NO_EMPTY) : null;
            return [true, $only, $except, $reset];
        }
        return [false, null, null, null];
    }

    /**
     *
     * @return string
     */
    public static function getVersion()
    {
        $manifest = Yii::getAlias('@webroot/dist/.vite/manifest.json');
        if (file_exists($manifest)) {
            return md5(filemtime($manifest));
        }
        return md5(date('Y-m-d') . __FILE__);
    }

    /**
     * Force redirect
     * @param string|array $url
     * @return Response
     */
    public static function location($url)
    {
        $url = Url::to($url);
        $response = Yii::$app->getResponse();
        $response->setStatusCode(409);
        $response->headers->set('X-Inertia-Location', $url);
        return $response;
    }
}
