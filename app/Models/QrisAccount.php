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

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
