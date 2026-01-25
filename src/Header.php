<?php

namespace dee\inertia;

class Header
{
    public const AXIOS_CSRF_PARAM = 'XSRF-TOKEN';
    public const AXIOS_CSRF_HEADER = 'X-XSRF-TOKEN';
    public const INERTIA = 'X-Inertia';
    public const ERROR_BAG = 'X-Inertia-Error-Bag';
    public const LOCATION = 'X-Inertia-Location';
    public const VERSION = 'X-Inertia-Version';
    public const PARTIAL_COMPONENT = 'X-Inertia-Partial-Component';
    public const PARTIAL_ONLY = 'X-Inertia-Partial-Data';
    public const PARTIAL_EXCEPT = 'X-Inertia-Partial-Except';
    public const RESET = 'X-Inertia-Reset';
    public const REDIRECT = 'X-Redirect';
    public const INFINITE_SCROLL_MERGE_INTENT = 'X-Inertia-Infinite-Scroll-Merge-Intent';
    public const EXCEPT_ONCE_PROPS = 'X-Inertia-Except-Once-Props';
}
