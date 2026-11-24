<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Promotion;
use App\Models\QrisAccount;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ShippingPolicy;
use App\Models\StaffUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Masters
{
    public const PERMISSIONS = [
        'admin' => ['orders.view', 'orders.manage', 'payments.review', 'orders.cancel', 'inspections.manage', 'receipts.manage', 'couriers.assign', 'technicians.assign', 'accounts', 'promos', 'reports'],
        'tech' => ['orders.view', 'treatment.work'],
        'qc' => ['orders.view', 'qc.review'],
        'courier' => ['orders.view', 'courier.work'],
    ];

    public function __construct(private OrderAccess $access, private MediaStorage $media, private Workflow $flow) {}

    private function check(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['master' => $message]);
        }
    }

    public function createCategory(StaffUser $u, string $name): ServiceCategory
    {
        $this->access->root($u, 'catalog');
        $name = trim($name);
        $code = Str::slug($name);
        Validator::make(['categoryName' => $name], ['categoryName' => 'required|string|max:80'])->validate();
        $this->check($code !== '' && ! in_array($code, ['all', 'faq'], true), 'Gunakan nama kategori yang berbeda.');

        return DB::transaction(function () use ($u, $name, $code) {
            DB::select('SELECT pg_advisory_xact_lock(3003)');
            $this->check(! ServiceCategory::where('code', $code)->exists(), 'Kategori dengan nama ini sudah ada.');
            $category = ServiceCategory::create([
                'name' => $name,
                'code' => $code,
                'display_order' => (ServiceCategory::max('display_order') ?? 0) + 1,
            ]);
            $this->flow->audit($u, 'category', $category->id, 'CREATE', 'Kategori layanan ditambahkan.');

            return $category;
        });
    }

    public function removeService(StaffUser $u, int $id): void
    {
        $this->access->root($u, 'catalog');
        DB::transaction(function () use ($u, $id) {
            $service = Service::lockForUpdate()->findOrFail($id);
            if ($service->archived_at) {
                return;
            }
            $service->update(['active' => false, 'archived_at' => now()]);
            $this->flow->audit($u, 'service', $id, 'ARCHIVE', 'Layanan dihapus dari katalog.');
        });
    }

    public function tariff(StaffUser $u, ?int $id, array $d, $cover = null, $poster = null, ?int $expectedVersion = null): void
    {
        $this->access->root($u, 'catalog');
        Validator::make($d, ['category_id' => 'required|exists:service_categories,id', 'code' => 'required|regex:/^[a-z0-9-]{2,40}$/', 'display_name' => 'required|string|max:80', 'description' => 'required|string|max:2000', 'pricing_mode' => 'required|in:FIXED,RANGE,QUOTE', 'min_price' => 'nullable|integer|min:1|max:100000000', 'max_price' => 'nullable|integer|min:1|max:100000000', 'duration_text' => 'required|string|max:80', 'unit' => 'required|in:unit,pasang', 'express_available' => 'required|boolean', 'active' => 'required|boolean', 'poster_source' => 'required|in:AUTO,CUSTOM', 'customer_note' => 'nullable|string|max:1000'])->validate();
        $this->check($d['pricing_mode'] === 'QUOTE' || ($d['min_price'] && $d['max_price'] && ($d['pricing_mode'] === 'FIXED' ? $d['min_price'] == $d['max_price'] : $d['min_price'] < $d['max_price'])), 'Sesuaikan batas harga dengan jenis tarif.');
        $this->media->atomic(function () use ($u, $id, $d, $cover, $poster, $expectedVersion) {
            $s = $id ? Service::lockForUpdate()->findOrFail($id) : new Service;
            $this->check($s->archived_at === null, 'Layanan ini sudah dihapus dari katalog.');
            $old = $s->exists ? $s->latestTariff : null;
            $this->check($expectedVersion === null || $old?->version === $expectedVersion, 'Pengaturan layanan berubah. Buka ulang editor terlebih dahulu.');
            $this->check(! Service::where('code', $d['code'])->when($id, fn ($q) => $q->where('id', '<>', $id))->exists(), 'Kode layanan sudah digunakan.');
            $s->fill(collect($d)->only(['category_id', 'code', 'active'])->all())->save();
            $min = $d['pricing_mode'] === 'QUOTE' ? null : (int) $d['min_price'];
            $max = $d['pricing_mode'] === 'QUOTE' ? null : (int) $d['max_price'];
            $posterId = null;
            if ($d['poster_source'] === 'CUSTOM') {
                if ($poster) {
                    $posterId = $this->media->store($poster, $u, true)->id;
                } else {
                    $this->check($expectedVersion !== null && $old?->poster_media_id && $min === $old->min_price && $max === $old->max_price, 'Unggah gambar dengan harga baru atau pilih desain contoh otomatis.');
                    $posterId = $old->poster_media_id;
                }
            }
            $v = collect($d)->only(['display_name', 'description', 'pricing_mode', 'duration_text', 'unit', 'express_available', 'poster_source', 'customer_note'])->all();
            $v += [
                'version' => ($old?->version ?? 0) + 1, 'min_price' => $min, 'max_price' => $max,
                'express_price' => $d['express_available'] ? 25000 : null, 'published_by' => $u->id,
                'cover_media_id' => $cover ? $this->media->store($cover, $u, true)->id : $old?->cover_media_id,
                'poster_media_id' => $posterId,
                'media_hidden' => ($cover || $poster || $expectedVersion !== null && $d['poster_source'] === 'AUTO') ? false : ($old?->media_hidden ?? false),
            ];
            $s->versions()->create($v);
            $this->flow->audit($u, 'service', $s->id, 'PUBLISH', 'Versi tarif dan poster diterbitkan.');
        });
    }

    public function serviceMedia(StaffUser $user, int $id, bool $useDefault): void
    {
        $this->access->root($user, 'catalog');
        DB::transaction(function () use ($user, $id, $useDefault) {
            $service = Service::whereNull('archived_at')->lockForUpdate()->findOrFail($id);
            $current = $service->latestTariff;
            $this->check($current !== null, 'Layanan belum memiliki tarif.');
            if ($current->media_hidden === ! $useDefault && $current->poster_source === 'AUTO' && ! $current->cover_media_id) {
                return;
            }
            $service->versions()->create(array_replace(
                $current->replicate(['created_at'])->getAttributes(),
                [
                    'version' => $current->version + 1,
                    'cover_media_id' => null, 'poster_media_id' => null,
                    'poster_source' => 'AUTO', 'media_hidden' => ! $useDefault, 'published_by' => $user->id,
                ]
            ));
            $this->flow->audit($user, 'service', $id, $useDefault ? 'RESTORE_MEDIA' : 'HIDE_MEDIA', 'Gambar publik layanan diperbarui.');
        });
    }

    public function publicCover(StaffUser $user, string $section, int $id, string $action, $file = null): void
    {
        $this->access->root($user, 'catalog');
        abort_unless(in_array($section, ['branches', 'promos'], true) && in_array($action, ['replace', 'hide', 'default'], true), 404);
        $this->media->atomic(function () use ($user, $section, $id, $action, $file) {
            $entry = $section === 'branches'
                ? Branch::lockForUpdate()->findOrFail($id)
                : Promotion::whereNull('branch_id')->whereNull('archived_at')->lockForUpdate()->findOrFail($id);
            $entry->update([
                'cover_media_id' => $action === 'replace' ? $this->media->store($file, $user, true)->id : null,
                'media_hidden' => $action === 'hide',
            ]);
            $this->flow->audit($user, $section === 'branches' ? 'branch' : 'promotion', $id, strtoupper($action).'_MEDIA', 'Foto publik diperbarui.');
        });
    }

    public function branch(StaffUser $u, ?int $id, array $d, $cover = null, $qrisImage = null): void
    {
        $this->access->root($u, 'branches');
        Validator::make($d, ['name' => 'required|string|max:80', 'area' => 'required|string|max:100', 'address' => 'required|string|max:500', 'latitude' => 'required|numeric|between:-90,90', 'longitude' => 'required|numeric|between:-180,180', 'qris_account_id' => 'required|exists:qris_accounts,id', 'active' => 'required|boolean'])->validate();
        $this->media->atomic(function () use ($u, $id, $d, $cover, $qrisImage) {
            $b = $id ? Branch::lockForUpdate()->findOrFail($id) : new Branch;
            $group = QrisAccount::whereKey($d['qris_account_id'])->where('active', true)->lockForUpdate()->first();
            $this->check($group !== null, 'Pilih grup QRIS yang aktif.');
            $b->fill(collect($d)->only(['name', 'area', 'address', 'latitude', 'longitude', 'qris_account_id', 'active'])->all());
            if ($cover) {
                $b->cover_media_id = $this->media->store($cover, $u, true)->id;
                $b->media_hidden = false;
            }
            $b->save();
            $this->flow->audit($u, 'branch', $b->id, 'SAVE', 'Konfigurasi cabang disimpan.');
            if ($qrisImage) {
                $this->publishQris($group, $u, $qrisImage);
                $this->flow->audit($u, 'qris', $group->id, 'PUBLISH', 'QRIS grup diperbarui melalui pengaturan cabang.');
            }
        });
    }

    public function qris(StaffUser $u, int $id, $file): void
    {
        $this->access->root($u, 'qris');
        $this->media->atomic(function () use ($u, $id, $file) {
            $a = QrisAccount::where('active', true)->lockForUpdate()->findOrFail($id);
            $this->publishQris($a, $u, $file);
            $this->flow->audit($u, 'qris', $id, 'PUBLISH', 'Gambar QRIS grup diperbarui untuk booking baru.');
        });
    }

    public function createQrisGroup(StaffUser $u, string $name, $file = null): QrisAccount
    {
        $this->access->root($u, 'qris');
        $name = trim($name);
        Validator::make(['qrisGroupName' => $name], ['qrisGroupName' => 'required|string|max:100'])->validate();

        return $this->media->atomic(function () use ($u, $name, $file) {
            DB::select('SELECT pg_advisory_xact_lock(3004)');
            $this->check(! QrisAccount::whereRaw('lower(group_name) = lower(?)', [$name])->exists(), 'Nama grup QRIS sudah dipakai.');
            $account = QrisAccount::create(['group_code' => 'QRIS-'.Str::ulid(), 'group_name' => $name]);
            if ($file) {
                $this->publishQris($account, $u, $file);
            }
            $this->flow->audit($u, 'qris', $account->id, 'CREATE', 'Grup QRIS dibuat.');

            return $account;
        });
    }

    private function publishQris(QrisAccount $account, StaffUser $u, $file): void
    {
        $account->versions()->create([
            'version' => ($account->versions()->max('version') ?? 0) + 1,
            'media_asset_id' => $this->media->store($file, $u, true)->id,
            'created_by' => $u->id,
        ]);
    }

    public function shipping(StaffUser $u, int $rate): void
    {
        $this->access->root($u, 'branches');
        $this->check($rate >= 0 && $rate <= 100000, 'Tarif per km harus 0–100.000.');
        DB::transaction(function () use ($u, $rate) {
            DB::select('SELECT pg_advisory_xact_lock(3001)');
            $v = ShippingPolicy::where('code', 'PICKUP')->orderByDesc('version')->firstOrFail();
            ShippingPolicy::create(['code' => 'PICKUP', 'version' => $v->version + 1, 'free_km' => 4, 'rate_per_km' => $rate, 'return_fee' => 0]);
            $this->flow->audit($u, 'shipping', $v->id, 'PUBLISH', 'Tarif jemput versi baru diterbitkan.');
        });
    }

    public function promotion(StaffUser $u, ?int $id, array $d, $cover = null): void
    {
        $this->check(($u->fresh()?->isRole('superadmin') || $u->fresh()?->isRole('admin')) && $u->fresh()->allows('promos'), 'Tidak memiliki akses promo.');
        $d['code'] = strtoupper(trim($d['code'] ?? ''));
        Validator::make($d, ['code' => 'required|regex:/^[A-Z0-9-]{3,32}$/', 'percent' => 'required|integer|between:1,90', 'cap_amount' => 'required|integer|min:1|max:100000000', 'active' => 'required|boolean', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after:starts_at', 'branch_id' => 'nullable|exists:branches,id'])->validate();
        $scope = $u->isRole('admin') ? $u->branch_id : (isset($d['branch_id']) && $d['branch_id'] !== '' ? (int) $d['branch_id'] : null);
        $this->media->atomic(function () use ($u, $id, $d, $scope, $cover) {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['promo:'.$d['code']]);
            $p = $id ? Promotion::lockForUpdate()->findOrFail($id) : new Promotion;
            $this->check($p->archived_at === null, 'Promo ini sudah diarsipkan.');
            $this->check(! $id || ($u->isRole('superadmin') || $p->branch_id === $scope), 'Promo di luar cabang Anda.');
            $this->check(! $id || $p->branch_id === $scope, 'Cakupan promo lama tidak dapat dipindah. Buat promo baru.');
            $collision = Promotion::where('code', $d['code'])->when($id, fn ($q) => $q->where('id', '<>', $id))->when($scope, fn ($q) => $q->where(fn ($r) => $r->whereNull('branch_id')->orWhere('branch_id', $scope)))->exists();
            $this->check(! $collision, 'Kode promo berbenturan dengan promo global/cabang.');
            $p->fill(['code' => $d['code'], 'branch_id' => $scope, 'active' => $d['active']]);
            if ($cover) {
                $p->cover_media_id = $this->media->store($cover, $u, true)->id;
                $p->media_hidden = false;
            }
            $p->save();
            $p->versions()->create(['version' => ($p->versions()->max('version') ?? 0) + 1, 'percent' => $d['percent'], 'cap_amount' => $d['cap_amount'], 'starts_at' => $d['starts_at'] ?: null, 'ends_at' => $d['ends_at'] ?: null, 'created_by' => $u->id]);
            $this->flow->audit($u, 'promotion', $p->id, 'PUBLISH', 'Versi promo disimpan.');
        });
    }

    public function archivePromotion(StaffUser $u, int $id): void
    {
        DB::transaction(function () use ($u, $id) {
            $p = Promotion::lockForUpdate()->findOrFail($id);
            $this->check($u->fresh()->allows('promos') && ($u->fresh()->isRole('superadmin') || ($u->isRole('admin') && $p->branch_id === $u->branch_id)), 'Promo di luar akses Anda.');
            if ($p->archived_at === null) {
                $p->update(['active' => false, 'archived_at' => now()]);
                $this->flow->audit($u, 'promotion', $id, 'ARCHIVE', 'Promo diarsipkan dan riwayat transaksi dipertahankan.');
            }
        });
    }

    public function togglePromotion(StaffUser $user, int $id): void
    {
        $user = $user->fresh();
        abort_unless($user?->allows('promos'), 403);
        DB::transaction(function () use ($user, $id) {
            $promo = Promotion::whereNull('archived_at')->lockForUpdate()->findOrFail($id);
            abort_unless($user->isRole('superadmin') || ($user->isRole('admin') && $promo->branch_id === $user->branch_id), 403);
            $promo->update(['active' => ! $promo->active]);
            $this->flow->audit($user, 'promotion', $id, $promo->active ? 'ACTIVATE' : 'DEACTIVATE', 'Status promo diperbarui.');
        });
    }

    public function account(StaffUser $u, ?int $id, array $d): void
    {
        $u = $u->fresh();
        $this->check(($u->isRole('superadmin') || $u->isRole('admin')) && $u->allows('accounts'), 'Akses akun ditolak.');
        Validator::make($d, ['username' => 'required|regex:/^[a-z0-9._-]{3,40}$/', 'full_name' => 'required|string|max:100', 'role_id' => 'required|exists:roles,id', 'branch_id' => 'nullable|exists:branches,id', 'active' => 'required|boolean', 'password' => ($id ? 'nullable' : 'required').'|string|min:10|max:100'])->validate();
        DB::transaction(function () use ($u, $id, $d) {
            DB::select('SELECT pg_advisory_xact_lock(3002)');
            $a = $id ? StaffUser::whereNull('archived_at')->lockForUpdate()->findOrFail($id) : new StaffUser;
            $role = Role::findOrFail($d['role_id']);
            $branch = $d['branch_id'] ?: null;
            $this->check(! $id || $a->username !== '__system', 'Akun sistem tidak dapat diubah.');
            if ($u->isRole('admin')) {
                $this->check(in_array($role->code, ['tech', 'qc', 'courier']) && (! $id || ($a->branch_id === $u->branch_id && in_array($a->role->code, ['tech', 'qc', 'courier']))), 'Admin hanya mengelola staf operasional cabangnya.');
                $branch = $u->branch_id;
            }
            $this->check($role->code === 'superadmin' || $branch !== null, 'Peran operasional memerlukan cabang.');
            if ($id === $u->id) {
                $this->check($d['active'] && $role->id === $a->role_id && $branch === $a->branch_id, 'Tidak dapat menonaktifkan atau memindah peran akun sendiri.');
            }
            if ($id && $a->active && $a->role->code === 'superadmin' && (! $d['active'] || $role->code !== 'superadmin')) {
                $this->check(StaffUser::where('active', true)->where('id', '<>', $id)->whereHas('role', fn ($q) => $q->where('code', 'superadmin'))->exists(), 'Minimal satu Super Admin aktif diperlukan.');
            }
            $this->check(! StaffUser::where('username', $d['username'])->when($id, fn ($q) => $q->where('id', '<>', $id))->exists(), 'Username sudah digunakan.');
            $a->fill(['username' => $d['username'], 'full_name' => $d['full_name'], 'role_id' => $role->id, 'branch_id' => $role->code === 'superadmin' ? null : $branch, 'active' => $d['active']]);
            if ($d['password'] ?? '') {
                $a->password_hash = Hash::make($d['password']);
            }
            $a->save();
            $this->flow->audit($u, 'staff', $a->id, 'SAVE', 'Akun staf diperbarui.');
        });
    }

    public function accountAccess(StaffUser $user, int $id, bool $archive): void
    {
        $user = $user->fresh();
        abort_unless($user?->allows('accounts'), 403);
        DB::transaction(function () use ($user, $id, $archive) {
            DB::select('SELECT pg_advisory_xact_lock(3002)');
            $account = StaffUser::whereNull('archived_at')->lockForUpdate()->findOrFail($id);
            $operational = in_array($account->role->code, ['tech', 'qc', 'courier'], true);
            abort_unless($account->username !== '__system' && ($user->isRole('superadmin')
                || ($user->isRole('admin') && $account->branch_id === $user->branch_id && $operational)), 403);
            $this->check($account->id !== $user->id, 'Akun sendiri tidak dapat dinonaktifkan.');
            $active = ! $archive && ! $account->active;
            if (! $active && $account->role->code === 'superadmin') {
                $this->check(StaffUser::where('active', true)->where('id', '<>', $id)->whereHas('role', fn ($q) => $q->where('code', 'superadmin'))->exists(), 'Minimal satu Super Admin aktif diperlukan.');
            }
            $account->update(['active' => $active, 'archived_at' => $archive ? now() : null]);
            $this->flow->audit($user, 'staff', $id, $archive ? 'ARCHIVE' : ($active ? 'ACTIVATE' : 'DEACTIVATE'), 'Akses akun diperbarui.');
        });
    }

    public function permissions(StaffUser $u, array $grants): void
    {
        $this->access->root($u, 'permissions');
        DB::transaction(function () use ($u, $grants) {
            $roles = Role::whereIn('code', array_keys(self::PERMISSIONS))->orderBy('id')->lockForUpdate()->get();
            foreach ($roles as $role) {
                $codes = $grants[$role->code] ?? [];
                $this->check(count(array_diff($codes, self::PERMISSIONS[$role->code])) === 0, 'Izin tidak sesuai batas peran.');
                $role->permissions()->sync(Permission::whereIn('code', $codes)->pluck('id'));
                $this->flow->audit($u, 'role', $role->id, 'PERMISSIONS', 'Izin peran diperbarui.');
            }
        });
    }
}
