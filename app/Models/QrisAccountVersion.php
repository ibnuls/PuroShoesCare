<?php

namespace App\Models;

class QrisAccountVersion extends Record
{
    protected $table = 'qris_account_versions';

    public function media()
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
