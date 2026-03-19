<?php

namespace dee\inertia;

use yii\base\Event;
use yii\base\InvalidArgumentException;

/**
 * Description of ResolveDataEvent
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ResolveDataEvent extends Event
{
    /**
     * @var string
     */
    public $component;
    /**
     * @var array|mixed
     */
    public $params;
    /**
     * @var array|mixed
     */
    public $data;
}
