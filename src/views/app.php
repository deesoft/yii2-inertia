<?php

use yii\helpers\Html;
use yii\web\View;

/** @var View $this */
/** @var string $content */
$title = Yii::$app->name;
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