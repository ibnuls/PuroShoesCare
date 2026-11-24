<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_users', fn (Blueprint $t) => $t->rememberToken());
        Schema::table('branches', fn (Blueprint $t) => $t->foreignId('cover_media_id')->nullable()->constrained('media_assets')->restrictOnDelete());
        Schema::table('services', fn (Blueprint $t) => $t->integer('display_order')->default(0));
        Schema::table('service_tariff_versions', function (Blueprint $t) {
            $t->text('customer_note')->nullable();
            $t->string('poster_source', 12)->default('AUTO');
        });
        Schema::table('price_quotes', fn (Blueprint $t) => $t->text('decision_note')->nullable());
        Schema::table('promotions', fn (Blueprint $t) => $t->timestampTz('archived_at')->nullable());
        Schema::table('media_assets', fn (Blueprint $t) => $t->string('original_name')->nullable());
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
        Schema::create('knowledge_documents', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('source_path')->unique();
            $t->string('content_hash', 64);
            $t->string('status', 20)->default('READY');
            $t->boolean('active')->default(true);
            $t->timestampsTz();
        });
        Schema::create('knowledge_chunks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $t->integer('position');
            $t->string('heading');
            $t->text('body');
            $t->string('content_hash', 64);
            $t->jsonb('embedding')->nullable();
            $t->string('embedding_model')->nullable();
            $t->timestampsTz();
            $t->unique(['document_id', 'position']);
        });
        DB::statement("ALTER TABLE service_tariff_versions ADD CHECK (poster_source IN ('AUTO','CUSTOM'))");
        DB::statement("ALTER TABLE orders ADD CHECK (status IN ('WAITING_ASSESSMENT','NEEDS_INSPECTION','WAITING_APPROVAL','WAITING_PAYMENT','QUOTE_REJECTED','CANCELLED','PAYMENT_REJECTED','PENDING_PAYMENT','WAITING_PICKUP','PICKING_UP','WAITING_HANDOVER','WAITING_TECH','CLEANING','WAITING_QC','REWORK','READY_DELIVERY','OUT_FOR_DELIVERY','READY_COLLECTION','COMPLETE'))");
        DB::statement("CREATE UNIQUE INDEX uq_pending_submission ON payment_submissions(quote_id) WHERE decision='PENDING'");
        DB::statement('CREATE INDEX ix_orders_branch_status ON orders(branch_id,status,created_at)');
        DB::statement('CREATE INDEX ix_payment_submitted ON payment_submissions(quote_id,submitted_at)');
        DB::unprepared(file_get_contents(database_path('schema/invariants.sql')));
    }

    public function down(): void
    {
        foreach (['tariff_immutable' => 'service_tariff_versions', 'promo_version_immutable' => 'promotion_versions', 'shipping_immutable' => 'shipping_policy_versions', 'qris_version_immutable' => 'qris_account_versions', 'quote_lines_immutable' => 'quote_items', 'verified_immutable' => 'verified_payments', 'history_immutable' => 'order_status_history', 'qc_immutable' => 'qc_checks', 'quote_item_owner' => 'quote_items', 'submission_owner' => 'payment_submissions', 'verified_owner' => 'verified_payments'] as $trigger => $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$trigger} ON {$table}");
        }
        foreach (['puro_immutable', 'puro_quote_item_owner', 'puro_submission_owner', 'puro_verified_owner'] as $function) {
            DB::statement("DROP FUNCTION IF EXISTS {$function}()");
        }
        foreach (['uq_pending_submission', 'ix_orders_branch_status', 'ix_payment_submitted'] as $index) {
            DB::statement("DROP INDEX IF EXISTS {$index}");
        }
        DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');
        DB::statement('ALTER TABLE service_tariff_versions DROP CONSTRAINT IF EXISTS service_tariff_versions_poster_source_check');
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('sessions');
        Schema::table('branches', fn (Blueprint $t) => $t->dropConstrainedForeignId('cover_media_id'));
        foreach (['staff_users' => ['remember_token'], 'services' => ['display_order'], 'service_tariff_versions' => ['customer_note', 'poster_source'], 'price_quotes' => ['decision_note'], 'promotions' => ['archived_at'], 'media_assets' => ['original_name']] as $table => $columns) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn($columns));
        }
    }
};
