<?php

namespace dee\inertia;

use Yii;
use Closure;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\base\BaseObject;
use dee\clientUrl\Helper;
use yii\helpers\ArrayHelper;
use yii\web\HeaderCollection;

/**
 * @property Serializer $serializer
 */
class ResponseFactory extends BaseObject implements \JsonSerializable
{
    const CONTENT_TYPE_JSON = 'application/json; charset=UTF-8';
    /**
     * @var string
     */
    protected $component;
    /**
     * @var array|mixed
     */
    protected $params;

    /**
     * @var string|null
     */
    protected $location;
    /**
     * @var bool
     */
    protected $encryptHistory = false;

    /**
     * @var Serializer
     */
    private $_serializer;

    /**
     * @param string $component
     * @param array|mixed $params
     * @return static
     */
    public function render($component, $params = [])
    {
        $this->component = $component;
        $this->params = $params;
        return $this;
    }

    /**
     * @param string|array $key
     * @param mixed $value
     * @return static
     */
    public function flash($key, $value = null)
    {
        $session = Yii::$app->session;
        $flash = $session->getFlash('inertia.flash', []);
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $flash[$k] = $v;
            }
        } else {
            $flash[$key] = $value;
        }
        $session->setFlash('inertia.flash', $flash);
        return $this;
    }

    /**
     * @param string|array $key
     * @param mixed $value
     * @return static
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->params = array_merge($this->params, $key);
        } else {
            $this->params[$key] = $value;
        }
        return $this;
    }

    /**
     * Encrypt history
     * @param bool $value
     * @return static
     */
    public function encryptHistory($value = true)
    {
        $this->encryptHistory = $value;
        return $this;
    }

    /**
     * Clear history
     * @return static
     */
    public function clearHistory()
    {
        Yii::$app->session->setFlash('inertia_clear_history', true);
        return $this;
    }

    /**
     * @return bool
     */
    public function isInertia()
    {
        return (bool) Yii::$app->request->headers->get(Header::INERTIA);
    }

    /**
     *
     * @param string $component
     * @param array|mixed $params
     * @return array
     */
    protected function resolveProps($component, $params = [])
    {
        $request = Yii::$app->getRequest();
        list($isInertia, $partial, $only, $except, $reset, $exceptOnce) = $this->resolvePartial($component);

        $props = $params;
        $deferredProps = [];
        $mergeProps = [];
        $prependProps = [];
        $deepMergeProps = [];
        $matchPropsOn = [];
        $onceProps = [];
        $scrollProps = [];

        $props = $this->resolvePartialProps($props, $partial, $only, $except);
        if ($isInertia && !$partial && $exceptOnce) {
            $props = $this->resolveOnceProps($props, $exceptOnce);
        }

        foreach ($props as $key => $value) {
            $allowPartial = (!$except || !in_array($key, $except)) && (!$only || in_array($key, $only));
            if ($value instanceof ScrollProp && $allowPartial) {
                $value->configureMergeIntent($request);
            }
            // Mergeable
            if ($value instanceof Mergeable && $value->shouldMerge() && !in_array($key, $reset) && $allowPartial) {
                if ($value->shouldDeepMerge()) {
                    $deepMergeProps[] = $key;
                } elseif ($value->appendsAtRoot()) {
                    $mergeProps[] = $key;
                } elseif ($value->prependsAtRoot()) {
                    $prependProps[] = $key;
                } elseif (count($value->appendsAtPaths())) {
                    foreach ($value->appendsAtPaths() as $path) {
                        $mergeProps[] = "$key.$path";
                    }
                } elseif (count($value->prependsAtPaths())) {
                    foreach ($value->prependsAtPaths() as $path) {
                        $prependProps[] = "$key.$path";
                    }
                }

                if (count($value->matchesOn())) {
                    foreach ($value->matchesOn() as $path) {
                        $matchPropsOn[] = "$key.$path";
                    }
                }
            }

            // DeferProp
            if (!$partial && $value instanceof DeferProp) {
                if (!$value->shouldResolveOnce() || $value->shouldBeRefreshed() || !in_array($value->getKey() ?? $key, $exceptOnce)) {
                    $deferredProps[$value->group()][] = $key;
                }
            }

            // Onceable
            if ($value instanceof Onceable && $value->shouldResolveOnce() && $allowPartial) {
                $onceProps[$value->getKey() ?? $key] = [
                    'prop' => $key,
                    'expireAt' => $value->expiresAt()
                ];
            }

            if ($value instanceof Closure || is_callable($value) || $value instanceof BaseProp) {
                $props[$key] = call_user_func($value);
            }

            // ScrollProp
            if ($value instanceof ScrollProp && $allowPartial) {
                $scrollProps[$key] = $value->getMeta();
                if ($scrollProps[$key] && in_array($key, $reset)) {
                    $scrollProps[$key]['reset'] = true;
                }
            }
        }

        $props = $this->getSerializer()->serialize($props);
        $errors = [];
        foreach (Inertia::$errors as $key => $value) {
            $errors[$key] = $value;
        }
        Inertia::$errors = [];
        if ($errors) {
            if ($request->headers->has(Header::ERROR_BAG)) {
                $props['errors'][$request->headers->get(Header::ERROR_BAG)] = $errors;
            } else {
                $props['errors'] = $errors;
            }
        }
        $props['$r'] = [Yii::$app->controller->route, $request->getQueryParams() ?: (object) []];

        return array_filter([
            'props' => $props,
            'deferredProps' => $deferredProps,
            'mergeProps' => array_unique($mergeProps),
            'prependProps' => array_unique($prependProps),
            'deepMergeProps' => $deepMergeProps,
            'matchPropsOn' => $matchPropsOn,
            'onceProps' => $onceProps,
            'scrollProps' => $scrollProps,
        ], function ($val) {
            return !empty($val);
        });
    }

    /**
     * @param array $props
     * @param bool $partial
     * @param array $only
     * @param array $except
     * @return array
     */
    protected function resolvePartialProps($props, $partial, $only, $except)
    {
        if ($partial) {
            return array_filter($props, function ($prop, $key) use ($only, $except) {
                return ($prop instanceof AlwaysProp) || ((!$except || !in_array($key, $except)) && (!$only || in_array($key, $only)));
            }, ARRAY_FILTER_USE_BOTH);
        } else {
            return array_filter($props, function ($prop) {
                return !($prop instanceof IgnoreFirstLoad);
            });
        }
    }

    /**
     * @param array $props
     * @param array $exceptOnce
     * @return array
     */
    protected function resolveOnceProps($props, $exceptOnce)
    {
        return array_filter($props, function ($prop, $key) use ($exceptOnce) {
            return !($prop instanceof Onceable) ||
                !$prop->shouldResolveOnce() ||
                $prop->shouldBeRefreshed() ||
                !in_array($key, $exceptOnce);
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @return array
     */
    public function data()
    {
        $request = Yii::$app->getRequest();
        $params = array_merge(Inertia::config('shared'), Inertia::getShared(), $this->params);
        $data = $this->resolveProps($this->component, $params);
        $session = Yii::$app->session;
        $data = array_merge([
            'component' => $this->component,
            'url' => $request->getUrl(),
            'version' => Inertia::getVersion(),
            'encryptHistory' => $this->encryptHistory || Inertia::config('encrypt_history'),
            'clearHistory' => $session->getFlash('inertia_clear_history'),
        ], $data);
        $flash = $session->getFlash('inertia.flash', []);
        if (!empty($flash)) {
            $data['flash'] = $flash;
        }
        return $data;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->data();
    }

    /**
     * @param array $data
     * @return string
     */
    protected function toContent($data)
    {
        $tag = Inertia::config('tag');
        $id = Inertia::config('id');
        $content = Html::tag($tag, '', ['id' => $id, 'data' => ['page' => $data]]);

        $view = Yii::$app->view;
        if (Inertia::config('register_yii_url_asset')) {
            Helper::registerJs($view);
        }
        if (Inertia::config('register_vite_asset')) {
            ViteAsset::register($view);
        }

        $viewFile = Inertia::config('view_file');
        return $view->render($viewFile, ['content' => $content]);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $response = Yii::$app->getResponse();
        if ($this->location) {
            $response->setStatusCode(409);
            $response->headers->set(Header::LOCATION, $this->location);
            return '';
        } elseif ($this->isInertia()) {
            $response->getHeaders()->set('Content-Type', self::CONTENT_TYPE_JSON);
            $response->headers->set(Header::INERTIA, true);
            return Json::encode($this->data());
        } else {
            return $this->toContent($this->data());
        }
    }

    /**
     * @param HeaderCollection $headers
     * @param string $key
     * @return array|null
     */
    protected function parsePropsFromHeader($headers, $key)
    {
        return array_filter(explode(',', $headers->get($key, ''))) ?: [];
    }

    /**
     * @param string $component
     * @return array
     */
    protected function resolvePartial($component)
    {
        $headers = Yii::$app->getRequest()->getHeaders();
        return [
            (bool) $headers->get(Header::INERTIA),
            $headers->get(Header::PARTIAL_COMPONENT) == $component,
            $this->parsePropsFromHeader($headers, Header::PARTIAL_ONLY),
            $this->parsePropsFromHeader($headers, Header::PARTIAL_EXCEPT),
            $this->parsePropsFromHeader($headers, Header::RESET),
            $this->parsePropsFromHeader($headers, Header::EXCEPT_ONCE_PROPS),
        ];
    }

    /**
     * Force redirect
     * @param string|array $url
     * @return static
     */
    public function location($url)
    {
        $this->location = $url ? Url::to($url) : null;
        return $this;
    }

    /**
     * @return Serializer
     */
    public function getSerializer()
    {
        if ($this->_serializer === null) {
            $config = Inertia::config('serializer');
            if (is_string($config)) {
                $config = ['class' => $config];
            }
            if (is_array($config) && !isset($config['class'])) {
                $config['class'] = Serializer::class;
            }
            $this->_serializer = Yii::createObject($config);
        }
        return $this->_serializer;
    }
}
