<?php

namespace dee\inertia;

/**
 * Description of ErrorHandler
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ErrorHandler extends \yii\web\ErrorHandler
{

    /**
     * {@inheritDoc}
     * @return bool
     */
    protected function shouldRenderSimpleHtml()
    {
        return YII_ENV_TEST || (\Yii::$app->request->getIsAjax() && !\Yii::$app->request->headers->has(Header::INERTIA));
    }
}

