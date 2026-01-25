<?php

namespace dee\inertia;

use yii\base\InvalidArgumentException;

/**
 * Description of BaseProp
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class BaseProp
{
    /**
     *
     * @var mixed|\Closure
     */
    public $value;
    /**
     *
     * @var bool
     */
    protected $mustClosure = true;

    /**
     *
     * @param mixed|\Closure $value
     * @throws InvalidArgumentException
     */
    public function __construct($value)
    {
        if($this->mustClosure && !($value instanceof \Closure || is_callable($value))){
            throw new InvalidArgumentException('Value must a callback');
        }
        $this->value = $value;
    }

    public function __invoke()
    {
        if($this->mustClosure || $this->value instanceof \Closure || is_callable($this->value)){
            return call_user_func($this->value);
        }
        return $this->value;
    }
}
