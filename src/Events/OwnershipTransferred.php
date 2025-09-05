<?php

declare(strict_types=1);

namespace Dibakar\Ownership\Events;

use Illuminate\Database\Eloquent\Model;

class OwnershipTransferred
{
    /**
     * The ownable model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $ownable;

    /**
     * The previous owner data.
     *
     * @var array
     */
    public $previousOwner;

    /**
     * The new owner data.
     *
     * @var array
     */
    public $newOwner;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $ownable
     * @param array $previousOwner
     * @param array $newOwner
     * @return void
     */
    public function __construct(Model $ownable, Model $previousOwner, Model $newOwner)
    {
        $this->ownable = $ownable;
        $this->previousOwner = $previousOwner;
        $this->newOwner = $newOwner;
    }
}
