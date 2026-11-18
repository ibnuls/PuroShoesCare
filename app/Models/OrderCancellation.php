<?php

namespace App\Models;

class OrderCancellation extends Record
{
    protected $table = 'order_cancellations';

    public $incrementing = false;

    protected $primaryKey = 'order_id';

    protected function casts(): array
    {
        return ['cancelled_at' => 'datetime', 'return_required' => 'boolean'];
    }
}
