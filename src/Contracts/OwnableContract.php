<?php

namespace Dibakar\Ownership\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;

interface OwnableContract
{
    public function owner(): MorphTo;

    public function isOwnedBy($owner): bool;
}
