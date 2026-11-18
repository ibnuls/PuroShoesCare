<?php

namespace App\Models;

class QcCheck extends Record
{
    protected $table = 'qc_checks';

    public $incrementing = false;

    protected $primaryKey = 'treatment_id';

    public function reviewer()
    {
        return $this->belongsTo(StaffUser::class, 'qc_user_id');
    }

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }
}
