<?php

namespace App\Models;

class TariffVersion extends Record
{
    protected $table = 'service_tariff_versions';

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function cover()
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_id');
    }

    public function poster()
    {
        return $this->belongsTo(MediaAsset::class, 'poster_media_id');
    }

    public function needsAssessment(bool $express = false): bool
    {
        return $this->pricing_mode !== 'FIXED' || $express;
    }

    protected function casts(): array
    {
        return ['min_price' => 'integer', 'max_price' => 'integer', 'express_price' => 'integer', 'express_available' => 'boolean', 'created_at' => 'datetime'];
    }
}
