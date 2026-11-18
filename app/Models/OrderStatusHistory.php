<?php

namespace App\Models;

class OrderStatusHistory extends Record
{
    protected $table = 'order_status_history';

    public function actor()
    {
        return $this->belongsTo(StaffUser::class, 'actor_id');
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
