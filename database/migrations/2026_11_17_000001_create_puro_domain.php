<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            throw new RuntimeException('PURO membutuhkan PostgreSQL. Periksa DB_CONNECTION di .env.');
        }
        DB::unprepared(file_get_contents(database_path('schema/puro.sql')));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS verified_sales, quote_totals CASCADE');
        foreach (array_reverse(json_decode(file_get_contents(database_path('schema/domain-tables.json')), true)) as $table) {
            DB::statement('DROP TABLE IF EXISTS '.$table.' CASCADE');
        }
    }
};
