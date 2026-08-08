<?php

namespace App\Domains\Column\Actions;

use App\Models\Column;

class ReorderColumnAction
{
    public function execute(Column $column, float $newPosition): void
    {
        $column->position = $newPosition;
        $column->save();
    }
}