<?php

declare(strict_types=1);

namespace Dibakar\Ownership\Events;

use Illuminate\Database\Eloquent\Model;

class OwnershipDeleted
{
    /**
     * The ownable model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $ownable;

    /**
     * The ownership data.
     *
     * @var array
     */
    public $ownershipData;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $ownable
     * @param array                               $ownershipData
     *
     * @return void
     */
    public function __construct(Model $ownable, array $ownershipData)
    {
        $this->ownable = $ownable;
        $this->ownershipData = $ownershipData;
    }
}
