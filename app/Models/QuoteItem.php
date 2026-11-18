<?php

namespace App\Models;

class QuoteItem extends Record
{
    protected $table = 'quote_items';

    public $incrementing = false;

    protected $primaryKey = null;

    public function item()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'express_fee' => 'integer'];
    }
}
