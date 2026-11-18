<?php

namespace App\Models;

class CourierTask extends Record
{
    protected $table = 'courier_tasks';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function courier()
    {
        return $this->belongsTo(StaffUser::class, 'courier_id');
    }

    public function proof()
    {
        return $this->belongsTo(MediaAsset::class, 'proof_media_id');
    }

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
