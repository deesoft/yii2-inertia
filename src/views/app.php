<?php

use dee\inertia\ViteAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

/** @var View $this */
/** @var string $content */
ViteAsset::register($this);
$title = ArrayHelper(Yii::$app->params, 'inertia.app_title', 'My Application');
?>
<?php $this->beginPage(); ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= $title ?></title>
        <?= Html::csrfMetaTags() ?>
        <?php $this->head(); ?>
    </head>
    <body>
        <?php $this->beginBody(); ?>
        <?= $content ?> 
        <?php $this->endBody(); ?>
    </body>
</html>
<?php $this->endPage(); ?>