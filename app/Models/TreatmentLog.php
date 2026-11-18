<?php

namespace App\Models;

class TreatmentLog extends Record
{
    protected $table = 'treatment_logs';

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function technician()
    {
        return $this->belongsTo(StaffUser::class, 'technician_id');
    }

    public function photos()
    {
        return $this->hasMany(TreatmentPhoto::class, 'treatment_id')->with('media');
    }

    public function qc()
    {
        return $this->hasOne(QcCheck::class, 'treatment_id');
    }

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'started_at' => 'datetime', 'submitted_at' => 'datetime'];
    }
}
