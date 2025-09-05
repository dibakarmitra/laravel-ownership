<?php

namespace Dibakar\Ownership\Facades;

use Illuminate\Support\Facades\Facade;

class Ownership extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Dibakar\Ownership\Support\OwnershipManager::class;
    }
}
