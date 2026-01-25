<?php

namespace dee\inertia;

/**
 * Description of OnceProp
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class OnceProp extends BaseProp implements Onceable
{
    use ResolvesOnce;

    /**
     *
     * @param mixed|\Closure $value
     */
    public function __construct($value)
    {
        $this->once = true;
        parent::__construct($value);
    }
}
