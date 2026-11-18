<?php

namespace App\Models;

class TreatmentPhoto extends Record
{
    protected $table = 'treatment_photos';

    public $incrementing = false;

    protected $primaryKey = null;

    public function media()
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
