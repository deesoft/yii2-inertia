<?php

namespace dee\inertia;

use Yii;
use Closure;
use yii\helpers\Url;
use yii\web\Request;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\Response;
use dee\clientUrl\Helper;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\HeaderCollection;

/**
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ResponseFactory extends Component implements \JsonSerializable
{
    const CONTENT_TYPE_JSON = 'application/json; charset=UTF-8';
    const CONTENT_TYPE_HTML = 'text/html';
    const EVENT_RESOLVE_DATA = 'resolveData';
    const EVENT_RESOLVED_DATA = 'resolvedData';
    
    /**
     * @var Request|null the current request. If not set, the `request` application component will be used.
     */
    public $request;
    /**
     * @var Response|null the response to be sent. If not set, the `response` application component will be used.
     */
    public $response;
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
     * {@inheritdoc}
     */
    public function init()
    {
        if ($this->request === null) {
            $this->request = Yii::$app->getRequest();
        }
        if ($this->response === null) {
            $this->response = Yii::$app->getResponse();
        }
    }

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

    
    protected $deferredProps = [];
    protected $mergeProps = [];
    protected $prependProps = [];
    protected $deepMergeProps = [];
    protected $matchPropsOn = [];
    protected $onceProps = [];
    protected $scrollProps = [];
    protected $sharedProps = [];

    /**
     * @param string $key
     * @param string[]|null $only
     * @param string[]|null $except
     * @return bool
     */
    protected function isAllowsPartial($key, $only, $except)
    {
        if($except && in_array($key, $except)){
            return false;
        }
        if(!$only || in_array($key, $only)){
            return true;
        }
        foreach($only as $f){
            if(strpos($f, "$key.") === 0 || strpos($key, "$f.")){
                return true;
            }
        }
        return false;
    }

    protected function resolvePropsRecursive($params, $partials, $prefix = null)
    {
        $request = $this->request;
        list($isInertia, $partial, $only, $except, $reset, $exceptOnce) = $partials;

        $props = [];
        foreach ($params as $_key => $prop) {
            $key = $prefix ? "$prefix.$_key" : $_key;
            $allowPartial = $this->isAllowsPartial($key, $only, $except);
            $isOnce = $prop instanceof Onceable && $prop->shouldResolveOnce() && !$prop->shouldBeRefreshed() && in_array($prop->getKey() ?? $key, $exceptOnce);
            $allow = ($prop instanceof AlwaysProp) ||
                ($partial && $allowPartial) ||
                (!$partial && !($prop instanceof IgnoreFirstLoad) && !($isInertia && $exceptOnce && $isOnce));

            // Mergeable
            if ($prop instanceof Mergeable && $prop->shouldMerge() && !in_array($key, $reset) && $allowPartial) {
                if ($prop instanceof ScrollProp) {
                    $prop->configureMergeIntent($request);
                }
                if ($prop->shouldDeepMerge()) {
                    $this->deepMergeProps[] = $key;
                } elseif ($prop->appendsAtRoot()) {
                    $this->mergeProps[] = $key;
                } elseif ($prop->prependsAtRoot()) {
                    $this->prependProps[] = $key;
                } elseif (count($prop->appendsAtPaths())) {
                    foreach ($prop->appendsAtPaths() as $path) {
                        $this->mergeProps[] = "$key.$path";
                    }
                } elseif (count($prop->prependsAtPaths())) {
                    foreach ($prop->prependsAtPaths() as $path) {
                        $this->prependProps[] = "$key.$path";
                    }
                }

                if (count($prop->matchesOn())) {
                    foreach ($prop->matchesOn() as $path) {
                        $this->matchPropsOn[] = "$key.$path";
                    }
                }
            }

            // DeferProp
            if (!$partial && $prop instanceof DeferProp && !$isOnce) {
                $this->deferredProps[$prop->group()][] = $key;
            }

            // Onceable
            if ($prop instanceof Onceable && $prop->shouldResolveOnce() && $allowPartial) {
                $this->onceProps[$prop->getKey() ?? $key] = [
                    'prop' => $key,
                    'expireAt' => $prop->expiresAt()
                ];
            }

            if ($allow) {
                if ($prop instanceof BaseProp) {
                    $props[$_key] = call_user_func($prop);
                    // ScrollProp
                    if ($prop instanceof ScrollProp) {
                        $this->scrollProps[$key] = $prop->getMeta();
                        if ($this->scrollProps[$key] && in_array($key, $reset)) {
                            $this->scrollProps[$key]['reset'] = true;
                        }
                    }
                } elseif($prop instanceof \Closure || is_callable($prop)){
                    $props[$_key] = call_user_func($prop);
                    if(is_array($props[$_key])){
                        $props[$_key] = $this->resolvePropsRecursive($props[$_key], $partials, $_key);
                    }
                } elseif(is_array($prop)){
                    $props[$_key] = $this->resolvePropsRecursive($prop, $partials, $_key);
                } else {
                    $props[$_key] = $prop;
                }
            }
        }
        return $props;
    }

    /**
     *
     * @param string $component
     * @param array|mixed $params
     * @return array
     */
    protected function resolveProps($component, $params = [])
    {
        $request = $this->request;
        $this->deferredProps = [];
        $this->mergeProps = [];
        $this->prependProps = [];
        $this->deepMergeProps = [];
        $this->matchPropsOn = [];
        $this->onceProps = [];
        $this->scrollProps = [];

        $props = $this->resolvePropsRecursive($params, $this->resolvePartial($component));

        $props = Serializer::serialize($props, Inertia::config('serializer'));
        if($errors = ArrayHelper::remove($props, 'errors')){
            if ($request->headers->has(Header::ERROR_BAG)) {
                $props['errors'] = [$request->headers->get(Header::ERROR_BAG) => $errors];
            } else {
                $props['errors'] = $errors;
            }
        }
        $props['$r'] = [Yii::$app->controller->route, $request->getQueryParams() ?: (object) []];

        return array_filter([
            'props' => $props,
            'deferredProps' => $this->deferredProps,
            'mergeProps' => array_unique($this->mergeProps),
            'prependProps' => array_unique($this->prependProps),
            'deepMergeProps' => $this->deepMergeProps,
            'matchPropsOn' => $this->matchPropsOn,
            'onceProps' => $this->onceProps,
            'scrollProps' => $this->scrollProps,
        ], fn ($val) => !empty($val));
    }

    /**
     * @return array
     */
    public function data()
    {
        $request = $this->request;
        $this->sharedProps = [];
        
        $shared = Inertia::getShared();
        foreach(array_keys($shared) as $key){
            if(($p = strpos($key, '.')) !== false){
                $this->sharedProps[] = substr($key, 0, $p);
            } else {
                $this->sharedProps[] = $key;
            }
        }
        $params = array_merge($shared, $this->params);
        $event = new ResolveDataEvent([
            'component' => $this->component,
            'params' => $params,
        ]);
        $this->trigger(self::EVENT_RESOLVE_DATA, $event);
        $params = $event->params;
        $component = $event->component;
        $data = $this->resolveProps($component, $params);
        $session = Yii::$app->session;
        $data = array_merge([
            'component' => $component,
            'url' => $request->getUrl(),
            'version' => Inertia::getVersion(),
            'encryptHistory' => $this->encryptHistory || Inertia::config('encrypt_history'),
            'clearHistory' => $session->getFlash('inertia_clear_history'),
        ], $data);
        $flash = $session->getFlash('inertia.flash', []);
        if (!empty($flash)) {
            $data['flash'] = $flash;
        }

        $event = new ResolveDataEvent([
            'component' => $component,
            'params' => $params,
            'data' => $data,
        ]);
        $this->trigger(self::EVENT_RESOLVED_DATA, $event);        
        return $event->data;
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
        $script = Html::script(Json::encode($data), ['type' => 'application/json', 'data-page' => $id]);
        $content = Html::tag($tag, '', ['id' => $id]);

        $view = Yii::$app->view;
        if (Inertia::config('register_yii_url_asset')) {
            Helper::registerJs($view);
        }
        if (Inertia::config('register_vite_asset')) {
            ViteAsset::register($view);
        }

        $viewFile = Inertia::config('view_file');
        return $view->render($viewFile, ['content' => $script . $content]);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $response = $this->response;
        if ($this->location) {
            $response->setStatusCode(409);
            $response->headers->set(Header::LOCATION, $this->location);
            return '';
        } elseif ($this->request->headers->get(Header::INERTIA)) {
            $response->getHeaders()->set('Content-Type', self::CONTENT_TYPE_JSON);
            $response->headers->set(Header::INERTIA, true);
            return Json::encode($this->data());
        } else {
            $contentType = self::CONTENT_TYPE_HTML . "; charset=" . $response->charset;
            $response->getHeaders()->set('Content-Type', $contentType);
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
        $headers = $this->request->getHeaders();
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
}
