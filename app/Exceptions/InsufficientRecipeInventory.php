<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientRecipeInventory extends RuntimeException
{
    public function __construct(public readonly array $shortages)
    {
        parent::__construct('Existencia insuficiente.');
    }
}
