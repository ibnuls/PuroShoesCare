<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;

class MediaAsset extends Record
{
    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function getUrlAttribute(): string
    {
        return $this->visibility === 'PUBLIC' ? Storage::disk('public')->url($this->storage_key) : route('media.private', $this->id);
    }
}
