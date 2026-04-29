<?php

declare(strict_types=1);

namespace tests;

use Yii;

/**
 * Base Test Case.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function mockController()
    {
        list($ctrl, $id) = Yii::$app->createController('web/index');
        Yii::$app->controller = $ctrl;
    }
}