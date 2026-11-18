<?php

namespace App\Models;

class Role extends Record
{
    protected $table = 'roles';

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
