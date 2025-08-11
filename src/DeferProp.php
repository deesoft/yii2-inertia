<?php

namespace dee\inertia;

/**
 * Description of DeferProp
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DeferProp extends BaseProp
{
    public $group;
    public $shouldMerge = false;

    public function __construct($value, $group = '', $merge = false)
    {
        $this->group = $group;
        $this->shouldMerge = $merge;
        parent::__construct($value);
    }

    public function merge($value = true)
    {
        $this->shouldMerge = $value;
        return $this;
    }

    public function group($group)
    {
        $this->group = $group;
        return $this;
    }
}
