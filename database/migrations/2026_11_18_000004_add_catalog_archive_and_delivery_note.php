<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->timestampTz('archived_at')->nullable();
        });
        Schema::table('courier_tasks', function (Blueprint $table) {
            $table->string('delivery_note', 250)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('courier_tasks', fn (Blueprint $table) => $table->dropColumn('delivery_note'));
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('archived_at'));
    }
};
