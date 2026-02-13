<?php

namespace dee\inertia;

use Yii;
use Closure;
use yii\base\Model;
use yii\helpers\Url;
use yii\web\Request;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\Response;
use yii\base\Arrayable;
use yii\base\BaseObject;
use dee\clientUrl\Helper;
use yii\helpers\ArrayHelper;
use yii\web\HeaderCollection;
use yii\data\DataProviderInterface;

/**
 * 
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ResponseFactory extends BaseObject implements \JsonSerializable
{
    const CONTENT_TYPE_JSON = 'application/json; charset=UTF-8';
    const CONTENT_TYPE_HTML = 'text/html';
    /**
     * @var string the name of the query parameter containing the information about which fields should be returned
     * for a [[Model]] object. If the parameter is not provided or empty, the default set of fields as defined
     * by [[Model::fields()]] will be returned.
     */
    public $fieldsParam = 'fields';
    /**
     * @var string the name of the query parameter containing the information about which fields should be returned
     * in addition to those listed in [[fieldsParam]] for a resource object.
     */
    public $expandParam = 'expand';
    /**
     * @var string|null the name of the envelope (e.g. `items`) for returning the resource objects in a collection.
     * This is used when serving a resource collection. When this is set and pagination is enabled, the serializer
     * will return a collection in the following format:
     *
     * ```php
     * [
     *     'items' => [...],  // assuming collectionEnvelope is "items"
     *     'links' => [  // pagination links
     *         {'label' => 'First', 'href' => ...},
     *         {...},
     *     ],
     *     'meta' => {  // meta information as returned by Pagination::toArray()
     *         'totalCount' => 100,
     *         'pageCount' => 5,
     *         'currentPage' => 1,
     *         'perPage' => 20,
     *     },
     * ]
     * ```
     *
     * If this property is not set, the resource arrays will be directly returned without using envelope.
     * The pagination information as shown in `_links` and `_meta` can be accessed from the response HTTP headers.
     */
    public $collectionEnvelope = 'items';
    /**
     * @var string the name of the envelope (e.g. `_links`) for returning the links objects.
     * It takes effect only, if `collectionEnvelope` is set.
     */
    public $linksEnvelope = 'links';
    /**
     * @var string the name of the envelope (e.g. `_meta`) for returning the pagination object.
     * It takes effect only, if `collectionEnvelope` is set.
     */
    public $metaEnvelope = 'meta';

    /**
     * @var int
     */
    public $maxPageButton = 5;
    /**
     * @var Request|null the current request. If not set, the `request` application component will be used.
     */
    public $request;
    /**
     * @var Response|null the response to be sent. If not set, the `response` application component will be used.
     */
    public $response;
    /**
     * @var bool whether to preserve array keys when serializing collection data.
     * Set this to `true` to allow serialization of a collection as a JSON object where array keys are
     * used to index the model objects. The default is to serialize all collections as array, regardless
     * of how the array is indexed.
     * @see serializeDataProvider()
     */
    public $preserveKeys = false;
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
     * @var array<string, string>
     */
    protected $errors = [];

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

    /**
     *
     * @param string $component
     * @param array|mixed $params
     * @return array
     */
    protected function resolveProps($component, $params = [])
    {
        $request = $this->request;
        list($isInertia, $partial, $only, $except, $reset, $exceptOnce) = $this->resolvePartial($component);

        $props = [];
        $deferredProps = [];
        $mergeProps = [];
        $prependProps = [];
        $deepMergeProps = [];
        $matchPropsOn = [];
        $onceProps = [];
        $scrollProps = [];

        foreach ($params as $key => $prop) {
            $allowPartial = (!$except || !in_array($key, $except)) && (!$only || in_array($key, $only));
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
                    $deepMergeProps[] = $key;
                } elseif ($prop->appendsAtRoot()) {
                    $mergeProps[] = $key;
                } elseif ($prop->prependsAtRoot()) {
                    $prependProps[] = $key;
                } elseif (count($prop->appendsAtPaths())) {
                    foreach ($prop->appendsAtPaths() as $path) {
                        $mergeProps[] = "$key.$path";
                    }
                } elseif (count($prop->prependsAtPaths())) {
                    foreach ($prop->prependsAtPaths() as $path) {
                        $prependProps[] = "$key.$path";
                    }
                }

                if (count($prop->matchesOn())) {
                    foreach ($prop->matchesOn() as $path) {
                        $matchPropsOn[] = "$key.$path";
                    }
                }
            }

            // DeferProp
            if ($partial && $prop instanceof DeferProp && !$isOnce) {
                $deferredProps[$prop->group()][] = $key;
            }

            // Onceable
            if ($prop instanceof Onceable && $prop->shouldResolveOnce() && $allowPartial) {
                $onceProps[$prop->getKey() ?? $key] = [
                    'prop' => $key,
                    'expireAt' => $prop->expiresAt()
                ];
            }

            if ($allow) {
                if ($prop instanceof Closure || is_callable($prop) || $prop instanceof BaseProp) {
                    $props[$key] = call_user_func($prop);
                } else {
                    $props[$key] = $prop;
                }
                // ScrollProp
                if ($prop instanceof ScrollProp) {
                    $scrollProps[$key] = $prop->getMeta();
                    if ($scrollProps[$key] && in_array($key, $reset)) {
                        $scrollProps[$key]['reset'] = true;
                    }
                }
            }
        }

        $props = $this->serialize($props);
        if ($this->errors) {
            if ($request->headers->has(Header::ERROR_BAG)) {
                $props['errors'][$request->headers->get(Header::ERROR_BAG)] = $this->errors;
            } else {
                $props['errors'] = $this->errors;
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
     * @return array
     */
    public function data()
    {
        $request = $this->request;
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

    protected function serialize($data)
    {
        if ($data instanceof Arrayable) {
            return $this->serializeModel($data);
        } elseif ($data instanceof \JsonSerializable) {
            return $data->jsonSerialize();
        } elseif ($data instanceof DataProviderInterface) {
            return $this->serializeDataProvider($data);
        } elseif (is_array($data)) {
            $serializedArray = [];
            foreach ($data as $key => $value) {
                $serializedArray[$key] = $this->serialize($value);
            }
            return $serializedArray;
        }

        return $data;
    }

    /**
     * @return array the names of the requested fields. The first element is an array
     * representing the list of default fields requested, while the second element is
     * an array of the extra fields requested in addition to the default fields.
     * @see Model::fields()
     * @see Model::extraFields()
     */
    protected function getRequestedFields()
    {
        $fields = $this->request->get($this->fieldsParam);
        $expand = $this->request->get($this->expandParam);

        return [
            is_string($fields) ? preg_split('/\s*,\s*/', $fields, -1, PREG_SPLIT_NO_EMPTY) : [],
            is_string($expand) ? preg_split('/\s*,\s*/', $expand, -1, PREG_SPLIT_NO_EMPTY) : [],
        ];
    }

    /**
     * Serializes a data provider.
     * @param DataProviderInterface $dataProvider
     * @return array the array representation of the data provider.
     */
    protected function serializeDataProvider($dataProvider)
    {
        if ($this->preserveKeys) {
            $models = $dataProvider->getModels();
        } else {
            $models = array_values($dataProvider->getModels());
        }
        $models = $this->serializeModels($models);

        $result = [
            $this->collectionEnvelope => $models,
        ];
        if (($pagination = $dataProvider->getPagination()) !== false) {
            return array_merge($result, $this->serializePagination($pagination));
        }

        return $result;
    }

    /**
     * Serializes a pagination into an array.
     * @param Pagination $pagination
     * @return array the array representation of the pagination
     */
    protected function serializePagination($pagination)
    {
        $currentPage = $pagination->getPage();
        $pageCount = $pagination->getPageCount();
        $result = [
            $this->metaEnvelope => [
                'totalCount' => $pagination->totalCount,
                'pageCount' => $pageCount,
                'currentPage' => $currentPage + 1,
                'perPage' => $pagination->getPageSize(),
            ],
        ];
        if ($this->linksEnvelope) {
            $maxPageButton = $this->maxPageButton;
            $links = [];
            if ($pageCount > 0) {
                $links[] = ['label' => 'first', 'href' => $pagination->createUrl(0, null, true), 'active' => $currentPage == 0];
                if ($currentPage > 0) {
                    $links[] = ['label' => 'prev', 'href' => $pagination->createUrl($currentPage - 1, null, true)];
                }
                $beginPage = max(0, $currentPage - (int) ($maxPageButton / 2));
                if (($endPage = $beginPage + $maxPageButton - 1) >= $pageCount) {
                    $endPage = $pageCount - 1;
                    $beginPage = max(0, $endPage - $maxPageButton + 1);
                }
                for ($i = $beginPage; $i <= $endPage; $i++) {
                    $links[] = ['label' => $i + 1, 'href' => $pagination->createUrl($i, null, true), 'active' => $currentPage == $i];
                }
                if ($currentPage < $pageCount - 1) {
                    $links[] = ['label' => 'next', 'href' => $pagination->createUrl($currentPage + 1, null, true)];
                }
                $links[] = ['label' => 'last', 'href' => $pagination->createUrl($pageCount - 1, null, true), 'active' => $currentPage == $pageCount - 1];
            }
            $result[$this->linksEnvelope] = $links;
        }
        return $result;
    }

    /**
     * Serializes a model object.
     * @param Arrayable $model
     * @return array the array representation of the model
     */
    protected function serializeModel($model)
    {
        if ($model instanceof Model && $model->hasErrors()) {
            foreach ($model->firstErrors as $name => $message) {
                $this->errors[$name] = $message;
            }
        }
        list($fields, $expand) = $this->getRequestedFields();
        return $model->toArray($fields, $expand);
    }

    /**
     * Serializes a set of models.
     * @param array $models
     * @return array the array representation of the models
     */
    protected function serializeModels(array $models)
    {
        foreach ($models as $i => $model) {
            if ($model instanceof Arrayable) {
                $models[$i] = $this->serializeModel($model);
            } elseif (is_array($model)) {
                $models[$i] = ArrayHelper::toArray($model);
            }
        }

        return $models;
    }
}
