<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StaffUser;
use Illuminate\Auth\Access\AuthorizationException;

class OrderAccess
{
    public function scope($query, StaffUser $user)
    {
        if (! $user->active) {
            return $query->whereRaw('1=0');
        }
        if ($user->isRole('superadmin')) {
            return $query;
        }
        if (! $user->allows('orders.view')) {
            return $query->whereRaw('1=0');
        }
        $query->where('branch_id', $user->branch_id);
        if ($user->isRole('tech')) {
            $query->whereHas('treatments', fn ($q) => $q->where('technician_id', $user->id));
        } elseif ($user->isRole('courier')) {
            $query->whereHas('tasks', fn ($q) => $q->where('courier_id', $user->id));
        } elseif (! $user->isRole('admin') && ! $user->isRole('qc')) {
            $query->whereRaw('1=0');
        }

        return $query;
    }

    public function view(StaffUser $user, Order $order): void
    {
        if (! $this->scope(Order::query(), $user)->whereKey($order->id)->exists()) {
            throw new AuthorizationException('Pesanan di luar akses Anda.');
        }
    }

    public function operational(StaffUser $user, Order $order, string $role, string $permission): void
    {
        $user = $user->fresh();
        if (! $user || ! $user->isRole($role) || ! $user->allows($permission) || $user->branch_id !== $order->branch_id) {
            throw new AuthorizationException('Aksi ini tidak termasuk akses peran dan cabang Anda.');
        }
    }

    public function root(StaffUser $user, string $permission): void
    {
        if (! $user->fresh()?->isRole('superadmin') || ! $user->fresh()->allows($permission)) {
            throw new AuthorizationException('Akses Super Admin diperlukan.');
        }
    }

    public function guest(string $code, bool $lock = false): Order
    {
        $hash = hash('sha256', strtoupper(trim($code)));
        $q = Order::where('access_code_hash', $hash);
        if ($lock) {
            $q->lockForUpdate();
        }

        return $q->firstOrFail();
    }
}
