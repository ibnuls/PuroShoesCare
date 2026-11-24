<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['service_tariff_versions', 'branches', 'promotions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->boolean('media_hidden')->default(false));
        }
        Schema::table('staff_users', fn (Blueprint $table) => $table->timestampTz('archived_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('staff_users', fn (Blueprint $table) => $table->dropColumn('archived_at'));
        foreach (['service_tariff_versions', 'branches', 'promotions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('media_hidden'));
        }
    }
};
