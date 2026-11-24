<?php

namespace App\Models;

class AuditLog extends Record
{
    protected $table = 'audit_logs';

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
