<?php

namespace App\Models;

class OrderReceipt extends Record
{
    protected $table = 'order_receipts';

    public $incrementing = false;

    protected $primaryKey = 'order_id';

    public function proof()
    {
        return $this->belongsTo(MediaAsset::class, 'proof_media_id');
    }

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }
}
