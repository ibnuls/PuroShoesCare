<?php

namespace App\Models;

class Branch extends Record
{
    protected $table = 'branches';

    public function qrisAccount()
    {
        return $this->belongsTo(QrisAccount::class);
    }

    public function cover()
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_id');
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];
    }
}
