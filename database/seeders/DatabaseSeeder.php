<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\MediaAsset;
use App\Models\Permission;
use App\Models\Promotion;
use App\Models\QrisAccount;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ShippingPolicy;
use App\Models\StaffUser;
use App\Support\Puro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            DB::select('SELECT pg_advisory_xact_lock(3000)');
            $permissions = ['orders.view' => 'Lihat pesanan', 'orders.manage' => 'Terbitkan penawaran', 'payments.review' => 'Periksa pembayaran', 'orders.cancel' => 'Batalkan pesanan', 'inspections.manage' => 'Minta pemeriksaan', 'receipts.manage' => 'Terima/serahkan barang', 'couriers.assign' => 'Tugaskan kurir', 'technicians.assign' => 'Tugaskan teknisi', 'treatment.work' => 'Kerjakan treatment', 'qc.review' => 'Putuskan QC', 'courier.work' => 'Kerjakan tugas kurir', 'catalog' => 'Kelola layanan', 'branches' => 'Kelola cabang', 'qris' => 'Kelola QRIS', 'accounts' => 'Kelola akun', 'permissions' => 'Kelola izin', 'promos' => 'Kelola promo', 'reports' => 'Laporan', 'knowledge' => 'Dokumen chatbot'];
            foreach ($permissions as $code => $name) {
                Permission::firstOrCreate(compact('code'), compact('name'));
            }
            $grants = ['superadmin' => ['catalog', 'branches', 'qris', 'accounts', 'permissions', 'promos', 'reports', 'knowledge'], 'admin' => ['orders.view', 'orders.manage', 'payments.review', 'orders.cancel', 'inspections.manage', 'receipts.manage', 'couriers.assign', 'technicians.assign', 'accounts', 'promos', 'reports'], 'tech' => ['orders.view', 'treatment.work'], 'qc' => ['orders.view', 'qc.review'], 'courier' => ['orders.view', 'courier.work']];
            foreach (Puro::ROLES as $code => $name) {
                $role = Role::firstOrCreate(compact('code'), compact('name'));
                if ($role->wasRecentlyCreated) {
                    foreach ($grants[$code] as $p) {
                        DB::table('role_permissions')->insertOrIgnore(['role_id' => $role->id, 'permission_id' => Permission::where('code', $p)->value('id')]);
                    }
                }
            }
            $system = StaffUser::firstOrCreate(['username' => '__system'], ['role_id' => Role::where('code', 'superadmin')->value('id'), 'password_hash' => Hash::make(bin2hex(random_bytes(32))), 'full_name' => 'System catalogue publisher', 'active' => false]);
            $a = QrisAccount::firstOrCreate(['group_code' => 'PAIR12'], ['group_name' => 'Grup A · Cabang 01 & 02']);
            $b = QrisAccount::firstOrCreate(['group_code' => 'PAIR34'], ['group_name' => 'Grup B · Cabang 03 & 04']);
            if (! AuditLog::where('entity_type', 'seed')->where('entity_key', 'catalogue')->exists()) {
                $branches = [['Jakarta Pusat', 'Area Monas, Jakarta Pusat (alamat demo)', -6.1754, 106.8272], ['Jakarta Selatan', 'Area Senayan, Jakarta Selatan (alamat demo)', -6.2250, 106.7995], ['Jakarta Utara', 'Area Kelapa Gading, Jakarta Utara (alamat demo)', -6.1590, 106.9045], ['Jakarta Timur', 'Area Rawamangun, Jakarta Timur (alamat demo)', -6.1930, 106.8917]];
                foreach ($branches as $i => $v) {
                    Branch::firstOrCreate(['name' => 'Cabang 0'.($i + 1)], ['area' => $v[0], 'address' => $v[1], 'latitude' => $v[2], 'longitude' => $v[3], 'qris_account_id' => $i < 2 ? $a->id : $b->id]);
                }
                $data = json_decode(file_get_contents(database_path('seeders/catalogue.json')), true);
                foreach ($data['categories'] as $i => $c) {
                    ServiceCategory::firstOrCreate(['code' => $c['id']], ['name' => $c['name'], 'display_order' => $i]);
                }
                foreach ($data['services'] as $i => $s) {
                    $service = Service::firstOrCreate(['code' => $s['id']], ['category_id' => ServiceCategory::where('code', $s['category'])->value('id'), 'display_order' => $i]);
                    if ($service->versions()->exists()) {
                        continue;
                    }
                    $service->versions()->create(['version' => 1, 'display_name' => $s['name'], 'description' => $s['details'] ?? $s['desc'], 'pricing_mode' => strtoupper($s['pricingMode']), 'min_price' => $s['minPrice'], 'max_price' => $s['maxPrice'], 'unit' => $s['unit'], 'duration_text' => $s['duration'], 'express_available' => $s['express'], 'express_price' => $s['express'] ? 25000 : null, 'customer_note' => $s['note'], 'poster_source' => 'AUTO', 'published_by' => $system->id]);
                }
                AuditLog::create(['actor_id' => $system->id, 'entity_type' => 'seed', 'entity_key' => 'catalogue', 'action' => 'INITIAL_SEED', 'safe_summary' => 'Katalog awal dimuat satu kali.']);
            }
            if (! ShippingPolicy::exists()) {
                ShippingPolicy::create(['code' => 'PICKUP', 'version' => 1, 'free_km' => 4, 'rate_per_km' => 5000, 'return_fee' => 0]);
            }
            if (config('puro.demo') && app()->environment(['local', 'testing']) && ! AuditLog::where('entity_type', 'seed')->where('entity_key', 'demo')->exists()) {
                $root = StaffUser::firstOrCreate(['username' => 'superadmin'], ['role_id' => Role::where('code', 'superadmin')->value('id'), 'password_hash' => Hash::make('Demo123!'), 'full_name' => 'Super Admin Demo', 'active' => true]);
                foreach (Branch::all() as $i => $branch) {
                    foreach (['admin', 'tech', 'qc', 'courier'] as $r) {
                        $prefix = ['admin' => 'admin', 'tech' => 'teknisi', 'qc' => 'qc', 'courier' => 'kurir'][$r];
                        StaffUser::firstOrCreate(['username' => $prefix.sprintf('%02d', $i + 1)], ['role_id' => Role::where('code', $r)->value('id'), 'branch_id' => $branch->id, 'password_hash' => Hash::make('Demo123!'), 'full_name' => Puro::ROLES[$r].' '.$branch->name, 'active' => true]);
                    }
                }
                foreach ([$a, $b] as $account) {
                    if (! $account->versions()->exists()) {
                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="450" viewBox="0 0 400 450"><rect width="400" height="450" fill="white"/><rect x="20" y="20" width="360" height="410" rx="20" fill="#E8FF00"/><text x="200" y="150" text-anchor="middle" font-size="36" font-family="sans-serif" font-weight="bold">QRIS DEMO</text><text x="200" y="215" text-anchor="middle" font-size="19" font-family="sans-serif">JANGAN TRANSFER</text><text x="200" y="270" text-anchor="middle" font-size="17" font-family="sans-serif">Uji bukti pembayaran saja</text><text x="200" y="335" text-anchor="middle" font-size="21" font-family="sans-serif">'.$account->group_code.'</text></svg>';
                        $path = 'seed/qris-'.$account->group_code.'.svg';
                        Storage::disk('public')->put($path, $svg);
                        $m = MediaAsset::firstOrCreate(['storage_key' => $path], ['mime_type' => 'image/svg+xml', 'byte_size' => strlen($svg), 'visibility' => 'PUBLIC', 'uploaded_by' => $root->id]);
                        $account->versions()->create(['version' => 1, 'media_asset_id' => $m->id, 'created_by' => $root->id]);
                    }
                }
                foreach (['FRESH10' => [10, 15000], 'NEWDAY15' => [15, 20000]] as $code => $v) {
                    $p = Promotion::firstOrCreate(['code' => $code, 'branch_id' => null]);
                    if (! $p->versions()->exists()) {
                        $p->versions()->create(['version' => 1, 'percent' => $v[0], 'cap_amount' => $v[1], 'created_by' => $root->id]);
                    }
                }
                foreach (['Cabang 01' => ['PUROJKT15', 15, 20000], 'Cabang 03' => ['PUROUTR20', 20, 25000]] as $branchName => [$code, $percent, $cap]) {
                    $branch = Branch::where('name', $branchName)->first();
                    if ($branch && ! Promotion::where('code', $code)->exists()) {
                        $promo = Promotion::create(['code' => $code, 'branch_id' => $branch->id]);
                        $promo->versions()->create(['version' => 1, 'percent' => $percent, 'cap_amount' => $cap, 'created_by' => $root->id]);
                    }
                }
                AuditLog::create(['actor_id' => $system->id, 'entity_type' => 'seed', 'entity_key' => 'demo', 'action' => 'INITIAL_SEED', 'safe_summary' => 'Akun dan aset demo dimuat satu kali.']);
            }
        });
    }
}
