<?php

namespace App\Models;

class Promotion extends Record
{
    protected $table = 'promotions';

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function versions()
    {
        return $this->hasMany(PromotionVersion::class);
    }

    public function latestVersion()
    {
        return $this->hasOne(PromotionVersion::class)->ofMany('version', 'max');
    }

    public function cover()
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_id');
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'media_hidden' => 'boolean', 'archived_at' => 'datetime'];
    }
}
