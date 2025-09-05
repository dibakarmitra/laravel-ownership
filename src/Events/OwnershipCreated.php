<?php

declare(strict_types=1);

namespace Dibakar\Ownership\Events;

use Dibakar\Ownership\Models\Ownership;
use Illuminate\Database\Eloquent\Model;

class OwnershipCreated
{
    /**
     * The ownable model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $ownable;

    /**
     * The ownership instance.
     *
     * @var \Dibakar\Ownership\Models\Ownership
     */
    public $ownership;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $ownable
     * @param \Dibakar\Ownership\Models\Ownership $ownership
     *
     * @return void
     */
    public function __construct(Model $ownable, Ownership $ownership)
    {
        $this->ownable = $ownable;
        $this->ownership = $ownership;
    }
}
