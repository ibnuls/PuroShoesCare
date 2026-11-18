<?php

namespace App\Models;

class Inspection extends Record
{
    protected $table = 'inspections';

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'consented_at' => 'datetime'];
    }
}
