<?php

namespace App\Models;

class Service extends Record
{
    protected $table = 'services';

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function versions()
    {
        return $this->hasMany(TariffVersion::class, 'service_id');
    }

    public function latestTariff()
    {
        return $this->hasOne(TariffVersion::class, 'service_id')->ofMany('version', 'max');
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
