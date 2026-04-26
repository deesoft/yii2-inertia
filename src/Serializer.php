<?php
namespace dee\inertia;

use Yii;
use yii\base\Arrayable;
use yii\base\Model;
use yii\data\DataProviderInterface;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class Serializer
{
    /**
     * @var array<string, string|int|mixed>
     */
    protected static $configs = [];

    protected static $errors = [];

    /**
     * @param string $name
     * @return string|int|mixed
     */
    protected static function getConfig($name)
    {
        $defaults = [
            'fieldsParam' => 'fields',
            'expandParam' => 'expand',
            'collectionEnvelope' => 'items',
            'linksEnvelope' => 'links',
            'metaEnvelope' => 'meta',
            'maxPageButton' => 5,
            'preserveKeys' => false,
            'allError' => false,
        ];
        return static::$configs[$name] ?? ($defaults[$name] ?? null);
    }

    /**
     * @param array|mixed $data
     * @param array $configs
     * @return Response
     */
    public static function json($data, $configs = [])
    {
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->data = static::serialize($data, $configs);
        return $response;
    }
    
    /**
     * @param array|mixed $data
     * @param array $configs
     * @return array|mixed
     */
    public static function serialize($data, $configs = [])
    {
        static::$configs = $configs ?? [];
        $result = static::serializeRecursive($data);
        if(static::$errors){
            $result['errors'] = array_merge(static::$errors, $result['errors'] ?? []);
        }
        return $result;
    }
    /**
     * @param array|mixed $data
     * @return array|mixed
     */
    protected static function serializeRecursive($data)
    {
        if ($data instanceof Arrayable) {
            return static::serializeModel($data);
        } elseif ($data instanceof \JsonSerializable) {
            return $data->jsonSerialize();
        } elseif ($data instanceof DataProviderInterface) {
            return static::serializeDataProvider($data);
        } elseif (is_array($data)) {
            $serializedArray = [];
            foreach ($data as $key => $value) {
                $serializedArray[$key] = static::serializeRecursive($value);
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
    protected static function getRequestedFields()
    {
        $request = Yii::$app->request;
        $fields = $request->get(static::getConfig('fieldsParam'), '');
        $expand = $request->get(static::getConfig('expandParam'), '');
        if (is_string($fields)) {
            $fields = preg_split('/\s*,\s*/', $fields, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (is_string($expand)) {
            $expand = preg_split('/\s*,\s*/', $expand, -1, PREG_SPLIT_NO_EMPTY);
        }
        return [$fields, $expand];
    }

    /**
     * Serializes a data provider.
     * @param DataProviderInterface $dataProvider
     * @return array the array representation of the data provider.
     */
    protected static function serializeDataProvider($dataProvider)
    {
        if (static::getConfig('preserveKeys')) {
            $models = $dataProvider->getModels();
        } else {
            $models = array_values($dataProvider->getModels());
        }
        $models = static::serializeModels($models);
        if(!static::getConfig('collectionEnvelope')){
            return $models;
        }
        $result = [
            static::getConfig('collectionEnvelope') => $models,
        ];
        if (($pagination = $dataProvider->getPagination()) !== false) {
            return array_merge($result, static::serializePagination($pagination));
        }

        return $result;
    }

    /**
     * Serializes a pagination into an array.
     * @param Pagination $pagination
     * @return array the array representation of the pagination
     */
    protected static function serializePagination($pagination)
    {
        $currentPage = $pagination->getPage();
        $pageCount = $pagination->getPageCount();
        $result = [
            static::getConfig('metaEnvelope') => [
                'totalCount' => $pagination->totalCount,
                'pageCount' => $pageCount,
                'currentPage' => $currentPage + 1,
                'perPage' => $pagination->getPageSize(),
            ],
        ];
        if (static::getConfig('linksEnvelope')) {
            $maxPageButton = static::getConfig('maxPageButton');
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
            $result[static::getConfig('linksEnvelope')] = $links;
        }
        return $result;
    }

    /**
     * Serializes a model object.
     * @param Arrayable $model
     * @return array the array representation of the model
     */
    protected static function serializeModel($model)
    {
        if($model instanceof Model && $model->hasErrors()){
            if(static::getConfig('allError')){
                static::$errors = array_merge(static::$errors, $model->getErrors());
            } else {
                foreach($model->firstErrors as $f => $err){
                    static::$errors[$f] = $err;
                }
            }
        }
        list($fields, $expand) = static::getRequestedFields();
        return $model->toArray($fields, $expand);
    }

    /**
     * Serializes a set of models.
     * @param array $models
     * @return array the array representation of the models
     */
    protected static function serializeModels(array $models)
    {
        foreach ($models as $i => $model) {
            if ($model instanceof Arrayable) {
                $models[$i] = static::serializeModel($model);
            } elseif (is_array($model)) {
                $models[$i] = ArrayHelper::toArray($model);
            }
        }

        return $models;
    }
}