<?php

namespace Dibakar\Ownership\Scopes;

use Dibakar\Ownership\Facades\Ownership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OwnedByCurrentScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (!Ownership::shouldScope()) {
            return;
        }

        $owner = Ownership::current();
        if (!$owner) {
            return;
        }

        $name = config('ownership.morph_name', 'owner');
        $builder->where($model->qualifyColumn("{$name}_type"), $owner->getMorphClass())
                ->where($model->qualifyColumn("{$name}_id"), $owner->getKey());
    }
}
