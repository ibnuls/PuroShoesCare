<?php

namespace App\Models;

class QrisAccount extends Record
{
    protected $table = 'qris_accounts';

    public function versions()
    {
        return $this->hasMany(QrisAccountVersion::class);
    }

    public function latestVersion()
    {
        return $this->hasOne(QrisAccountVersion::class)->ofMany('version', 'max');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
