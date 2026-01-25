<?php

namespace dee\inertia;

/**
 * Description of DeferProp
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class DeferProp extends BaseProp implements Mergeable, Onceable, IgnoreFirstLoad
{
    use ResolvesOnce, MergesProps;

    protected $group;

    public function __construct($value, $group = '')
    {
        $this->group = $group;
        parent::__construct($value);
    }

    public function group()
    {
        return $this->group;
    }
}
