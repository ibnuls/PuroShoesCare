<?php

namespace App\Models;

class OrderItem extends Record
{
    protected $table = 'order_items';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function photos()
    {
        return $this->hasMany(ItemPhoto::class, 'order_item_id')->with('media');
    }
}
