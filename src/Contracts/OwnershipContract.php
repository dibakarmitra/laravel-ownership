<?php

namespace Dibakar\Ownership\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;

interface OwnershipContract
{
    public function owner(): MorphTo;

    public function isOwnedBy($owner): bool;
}
