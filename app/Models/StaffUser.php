<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class StaffUser extends Authenticatable
{
    use HasFactory;

    protected $table = 'staff_users';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return ['password_hash' => 'hashed', 'active' => 'boolean'];
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getNameAttribute()
    {
        return $this->full_name;
    }

    public function isRole(string $role): bool
    {
        return $this->active && $this->role?->code === $role;
    }

    public function allows(string $permission): bool
    {
        if (! $this->active) {
            return false;
        }
        if ($this->isRole('superadmin')) {
            return in_array($permission, ['catalog', 'branches', 'qris', 'accounts', 'permissions', 'promos', 'reports', 'knowledge']);
        }

        return $this->role->permissions()->where('code', $permission)->exists();
    }
}
