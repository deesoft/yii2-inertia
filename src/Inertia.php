<?php

namespace dee\inertia;

use Yii;
use Closure;
use yii\db\QueryInterface;
use yii\helpers\ArrayHelper;
use yii\data\DataProviderInterface;

class Inertia
{
    /**
     * @var array<string, mixed>
     */
    protected static $shared = [];
    /**
     * @var ResponseFactory
     */
    protected static $responseFactory;

    protected static $defaultConfigs = [
        'tag' => 'div',
        'id' => 'app',
        'view_file' => '@dee/inertia/views/app.php',
        'encrypt_history' => false,
        'shared' => [],
        'register_vite_asset' => true,
        'register_yii_url_asset' => true,
        'vite_port' => '5173',
        'vite_prod' => false,
        'response_factory' => []
    ];
    /**
     * @return ResponseFactory
     */
    protected static function getResponseFactory()
    {
        if (static::$responseFactory === null) {
            $config = static::config('response_factory');
            if(is_string($config)){
                $config['class'] = $config;
            }
            if(!isset($config['class'])){
                $config['class'] = ResponseFactory::class;
            }
            static::$responseFactory = Yii::createObject($config);
        }
        return static::$responseFactory;
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
     * @param string|null $key
     * @return mixed $value
     */
    public static function getShared($key = null)
    {
        if($key){
            return ArrayHelper::getValue(array_merge(static::config('shared'), static::$shared), $key);
        }
        return array_merge(static::config('shared'), static::$shared);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed 
     */
    public static function config($key, $default = null)
    {
        if (($value = ArrayHelper::getValue(Yii::$app->params, "inertia.$key")) !== null) {
            return $value;
        }
        if($key !== 'shared' && $key !== 'response_factory'){
            $env_key = strcmp($key, 'vite_') === 0 ? strtoupper($key) : 'INERTIA_' . strtoupper($key);
            $default = $_ENV[$env_key] ?? $default;
        }
        return ArrayHelper::getValue(self::$defaultConfigs, $key, $default);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed 
     */
    public static function setConfig($key, $value)
    {
        self::$defaultConfigs[$key] = $value;
    }

    /**
     * @param string $component
     * @param array $params
     * @return ResponseFactory
     */
    public static function render($component, $params = [])
    {
        return static::getResponseFactory()->render($component, $params);
    }

    /**
     * @param string|array $key
     * @param mixed $value
     * @return ResponseFactory
     */
    public static function with($key, $value = null)
    {
        return static::getResponseFactory()->with($key, $value);
    }

    /**
     * Force redirect
     * @param string|array $url
     * @return ResponseFactory
     */
    public static function location($url)
    {
        return static::getResponseFactory()->location($url);
    }

    /**
     * 
     * @param string|array $key
     * @param mixed $value
     * @return ResponseFactory
     */
    public static function flash($key, $value = null)
    {
        return static::getResponseFactory()->flash($key, $value);
    }

    /**
     * Encrypt history
     * @param bool $value
     * @return ResponseFactory
     */
    public static function encryptHistory($value = true)
    {
        return static::getResponseFactory()->encryptHistory($value);
    }

    /**
     * Clear history
     * @return ResponseFactory
     */
    public static function clearHistory()
    {
        return static::getResponseFactory()->clearHistory();
    }

    /**
     *
     * @param Closure $callback
     * @param string $group
     * @return DeferProp
     */
    public static function defer($callback, $group = '')
    {
        return new DeferProp($callback, $group);
    }

    /**
     *
     * @param mixed|Closure $value
     * @return MergeProp
     */
    public static function merge($value)
    {
        return new MergeProp($value);
    }

    /**
     *
     * @param mixed|Closure $value
     * @return AlwaysProp
     */
    public static function always($value)
    {
        return new AlwaysProp($value);
    }

    /**
     *
     * @param Closure $value
     * @return OptionalProp
     */
    public static function optional($value)
    {
        return new OptionalProp($value);
    }

    /**
     *
     * @param Closure $value
     * @return OnceProp
     */
    public static function once($value)
    {
        return new OnceProp($value);
    }

    /**
     *
     * @param DataProviderInterface|QueryInterface $value
     * @param string $wrapper
     * @param array $options
     * @return ScrollProp
     */
    public static function scroll($value, $wrapper = 'data', $options = [])
    {
        return new ScrollProp($value, $wrapper, $options);
    }

    /**
     *
     * @return string
     */
    public static function getVersion()
    {
        $bundle = Yii::$app->assetManager->getBundle(ViteAsset::class);
        if ($bundle && $bundle instanceof ViteAsset) {
            return $bundle->getVersion();
        }
        return md5(Yii::getVersion() . Inertia::class);
    }
}
