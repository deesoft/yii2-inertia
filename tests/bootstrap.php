<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
// ensure we get report on all possible php errors

use tests\app\WebController;
use yii\web\Application;

error_reporting(-1);

define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', true);
$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

if (is_dir(__DIR__ . '/../vendor/')) {
    $vendorRoot = __DIR__ . '/../vendor'; //this extension has its own vendor folder
} else {
    $vendorRoot = __DIR__ . '/../../..'; //this extension is part of a project vendor folder
}
require_once($vendorRoot . '/autoload.php');
require_once($vendorRoot . '/yiisoft/yii2/Yii.php');

\Yii::setAlias('@tests', __DIR__);
\Yii::setAlias('@dee/inertia', dirname(__DIR__) . '/src');
\Yii::setAlias('@dee/inertia/gii', dirname(__DIR__) . '/gii');
\Yii::setAlias('@client/dist', __DIR__ . '/dist');

$configs = [
    'id' => 'yii2-queue-app',
    'basePath' => __DIR__,
    'vendorPath' => dirname(__DIR__) . '/vendor',
    'runtimePath' => __DIR__ . '/runtime',
    'components' => [
        'request' => [
            'cookieValidationKey' => 'testme',
            'scriptFile' => __DIR__ . '/index.php',
            'scriptUrl' => '/index.php',
            'url' => '/index.php/web'
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
        ]
    ],
    'controllerMap' => [
        'web' => WebController::class,
    ],
    'catchAll' => 'web/index'
];
new Application($configs);
