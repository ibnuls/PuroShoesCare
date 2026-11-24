<?php

namespace App\Models;

class ServiceCategory extends Record
{
    protected $table = 'service_categories';

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id')->orderBy('display_order');
    }
}
