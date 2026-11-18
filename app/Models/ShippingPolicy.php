<?php

namespace App\Models;

class ShippingPolicy extends Record
{
    protected $table = 'shipping_policy_versions';

    protected function casts(): array
    {
        return ['free_km' => 'float', 'rate_per_km' => 'integer', 'created_at' => 'datetime'];
    }
}
