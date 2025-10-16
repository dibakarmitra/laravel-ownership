<?php

namespace Dibakar\Ownership\Policies;

use Dibakar\Ownership\Contracts\OwnershipContract;
use Dibakar\Ownership\Facades\Ownership;
use Illuminate\Contracts\Auth\Authenticatable;

class OwnablePolicy
{
    protected function passes(?Authenticatable $user, OwnershipContract $model): bool
    {
        if (Ownership::bypass($user)) {
            return true;
        }

        return $model->isOwnedBy($user);
    }

    public function view($user, OwnershipContract $model): bool
    {
        return $this->passes($user, $model);
    }

    public function update($user, OwnershipContract $model): bool
    {
        return $this->passes($user, $model);
    }

    public function delete($user, OwnershipContract $model): bool
    {
        return $this->passes($user, $model);
    }
}
