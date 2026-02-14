<?php

namespace dee\inertia;

use Yii;
use yii\web\ErrorHandler as BaseErrorHandler;

/**
 * Description of ErrorHandler
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ErrorHandler extends BaseErrorHandler
{

    /**
     * {@inheritDoc}
     * @return bool
     */
    protected function shouldRenderSimpleHtml()
    {
        return YII_ENV_TEST || (Yii::$app->request->getIsAjax() && !Yii::$app->request->headers->has(Header::INERTIA));
    }
}

