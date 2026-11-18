<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders ADD CONSTRAINT both_needs_distance CHECK (fulfilment_mode<>'BOTH' OR distance_km IS NOT NULL)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders DROP CONSTRAINT both_needs_distance');
    }
};
