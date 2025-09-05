<?php

namespace Dibakar\Ownership\Policies;

use Dibakar\Ownership\Contracts\OwnableContract;
use Dibakar\Ownership\Facades\Ownership;
use Illuminate\Contracts\Auth\Authenticatable;

class OwnablePolicy
{
    protected function passes(?Authenticatable $user, OwnableContract $model): bool
    {
        if (Ownership::bypass($user)) {
            return true;
        }

        return $model->isOwnedBy($user);
    }

    public function view($user, OwnableContract $model): bool
    {
        return $this->passes($user, $model);
    }

    public function update($user, OwnableContract $model): bool
    {
        return $this->passes($user, $model);
    }

    public function delete($user, OwnableContract $model): bool
    {
        return $this->passes($user, $model);
    }
}
