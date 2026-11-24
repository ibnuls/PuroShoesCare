<?php

namespace App\Models;

class PromotionVersion extends Record
{
    protected $table = 'promotion_versions';

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    protected function casts(): array
    {
        return ['percent' => 'integer', 'cap_amount' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'created_at' => 'datetime'];
    }
}
