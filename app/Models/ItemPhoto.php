<?php

namespace App\Models;

class ItemPhoto extends Record
{
    protected $table = 'item_photos';

    public $incrementing = false;

    protected $primaryKey = null;

    public function media()
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
