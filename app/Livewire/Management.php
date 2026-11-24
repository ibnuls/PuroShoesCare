<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\KnowledgeDocument;
use App\Models\Permission;
use App\Models\Promotion;
use App\Models\QrisAccount;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ShippingPolicy;
use App\Models\StaffUser;
use App\Services\Knowledge;
use App\Services\Masters;
use App\Services\Reports;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Management extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public string $tab;

    #[Locked]
    public ?int $editing = null;

    public array $d = [];

    public $cover;

    public $poster;

    #[Locked]
    public ?int $tariffVersion = null;

    public bool $autoPoster = true;

    public bool $pricePending = false;

    public bool $confirmPosterPrice = false;

    public $file;

    public int $qrisId = 0;

    public string $categoryName = '';

    #[Locked]
    public string $mediaTab = 'services';

    #[Locked]
    public bool $catalogEditing = false;

    #[Locked]
    public bool $categoryEditing = false;

    #[Locked]
    public ?int $coverEditing = null;

    public string $qrisGroupName = '';

    public $newQrisImage;

    public string $accountBranch = '';

    public string $accountRole = '';

    public string $accountStatus = '';

    public string $accountSearch = '';

    public string $accountSort = 'branch';

    public bool $promoArchive = false;

    public string $reportBranch = '';

    #[Locked]
    public string $reportPeriod = '30';

    public int $rate = 5000;

    public array $roleGrants = [];

    private const GRANT_GROUPS = [
        'admin' => [
            'Kelola pesanan & status' => ['orders.view', 'orders.manage', 'payments.review', 'orders.cancel', 'inspections.manage', 'receipts.manage', 'couriers.assign', 'technicians.assign'],
            'Kelola promo cabang' => ['promos'],
            'Kelola staf cabang' => ['accounts'],
            'Analitik penjualan' => ['reports'],
        ],
        'tech' => ['Update pengerjaan & foto' => ['orders.view', 'treatment.work']],
        'qc' => ['Persetujuan QC' => ['orders.view', 'qc.review']],
        'courier' => ['Foto bukti jemput & antar' => ['orders.view', 'courier.work']],
    ];

    public string $from = '';

    public string $to = '';

    public string $indexMessage = '';

    private function guard(): StaffUser
    {
        $u = auth()->user()?->fresh();
        abort_unless($u && $u->allows($this->tab) && ($u->isRole('superadmin') || ($u->isRole('admin') && in_array($this->tab, ['accounts', 'promos', 'reports']))), 403);

        return $u;
    }

    public function mount(string $tab)
    {
        $this->tab = $tab;
        $u = $this->guard();
        $this->accountSort = $u->isRole('superadmin') ? 'branch' : 'name';
        if ($tab === 'accounts' && $u->isRole('superadmin')) {
            $this->accountRole = (string) Role::where('code', 'admin')->value('id');
        }
        if ($tab === 'permissions') {
            $this->roleGrants = Role::whereIn('code', array_keys(self::GRANT_GROUPS))->with('permissions')->get()
                ->mapWithKeys(fn (Role $role) => [$role->code => $role->permissions->pluck('code')->all()])->all();
        }
        $today = now(config('puro.timezone'));
        $this->from = $today->copy()->subDays(29)->toDateString();
        $this->to = $today->toDateString();
        $this->rate = ShippingPolicy::orderByDesc('version')->value('rate_per_km');
        if ($tab === 'qris') {
            $this->qrisId = QrisAccount::where('active', true)->orderBy('id')->value('id') ?? 0;
        }
        $this->clear();
    }

    public function clear()
    {
        $this->guard();
        $this->editing = null;
        $this->catalogEditing = false;
        $this->categoryEditing = false;
        $this->coverEditing = null;
        $this->reset('cover', 'poster', 'file', 'tariffVersion', 'autoPoster', 'pricePending', 'confirmPosterPrice');
        $this->resetValidation();
        $this->d = match ($this->tab) {
            'catalog' => ['category_id' => ServiceCategory::value('id'), 'code' => '', 'display_name' => '', 'description' => '', 'pricing_mode' => 'FIXED', 'min_price' => '', 'max_price' => '', 'unit' => 'unit', 'duration_text' => '3–4 hari', 'express_available' => false, 'active' => true, 'poster_source' => 'AUTO', 'customer_note' => ''],
            'branches' => ['name' => '', 'area' => '', 'address' => '', 'latitude' => '', 'longitude' => '', 'qris_account_id' => QrisAccount::where('active', true)->orderBy('id')->value('id'), 'active' => true],
            'accounts' => ['username' => '', 'full_name' => '', 'role_id' => Role::where('code', auth()->user()->isRole('superadmin') ? 'admin' : 'tech')->value('id'), 'branch_id' => auth()->user()->branch_id ?? Branch::orderBy('id')->value('id'), 'password' => '', 'active' => true],
            'promos' => ['code' => '', 'branch_id' => auth()->user()->branch_id ?? '', 'percent' => 10, 'cap_amount' => 15000, 'starts_at' => '', 'ends_at' => '', 'active' => true],
            default => [],
        };
    }

    public function edit(int $id)
    {
        $u = $this->guard();
        $this->clear();
        $this->editing = $id;
        if ($this->tab === 'catalog') {
            $s = Service::whereNull('archived_at')->findOrFail($id);
            $t = $s->latestTariff;
            abort_unless($t, 404);
            $this->d = collect($t->getAttributes())->only(array_keys($this->d))->all() + ['code' => $s->code, 'category_id' => $s->category_id, 'active' => $s->active];
            $this->tariffVersion = $t->version;
            $this->autoPoster = $t->poster_source === 'AUTO';
            $this->pricePending = $t->pricing_mode === 'QUOTE';
            $this->catalogEditing = true;
        } elseif ($this->tab === 'branches') {
            $this->d = Branch::findOrFail($id)->only(array_keys($this->d));
        } elseif ($this->tab === 'accounts') {
            $s = $this->staffAccounts($u)->find($id);
            abort_unless($s && (! $u->isRole('admin') || $s->id !== $u->id), 403);
            $this->d = $s->only(array_keys($this->d));
            $this->d['password'] = '';
        } elseif ($this->tab === 'promos') {
            $p = Promotion::whereNull('archived_at')->findOrFail($id);
            abort_unless($u->isRole('superadmin') || $p->branch_id === $u->branch_id, 403);
            $v = $p->latestVersion;
            $this->d = ['code' => $p->code, 'branch_id' => $p->branch_id ?? '', 'active' => $p->active, 'percent' => $v->percent, 'cap_amount' => $v->cap_amount, 'starts_at' => $v->starts_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '', 'ends_at' => $v->ends_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? ''];
        }
    }

    public function addCategory(Masters $m)
    {
        $u = $this->guard();
        abort_unless($this->tab === 'catalog', 403);
        $category = $m->createCategory($u, $this->categoryName);
        $this->d['category_id'] = $category->id;
        $this->reset('categoryName');
        $this->categoryEditing = false;
        $this->catalogEditing = true;
        session()->flash('success', 'Kategori baru tersedia di pilihan layanan.');
    }

    public function openCategory(): void
    {
        $this->guard();
        abort_unless($this->tab === 'catalog', 403);
        $this->categoryEditing = true;
    }

    public function editCatalogue(): void
    {
        $this->guard();
        abort_unless($this->tab === 'catalog', 403);
        $this->clear();
        $this->catalogEditing = true;
    }

    public function selectMedia(string $section): void
    {
        $this->guard();
        abort_unless($this->tab === 'catalog' && in_array($section, ['services', 'branches', 'promos'], true), 403);
        $this->clear();
        $this->mediaTab = $section;
    }

    public function updateServiceMedia(int $id, bool $restore, Masters $masters): void
    {
        $user = $this->guard();
        abort_unless($this->tab === 'catalog' && $this->mediaTab === 'services', 403);
        $masters->serviceMedia($user, $id, $restore);
        session()->flash('success', $restore ? 'Contoh gambar bawaan dipulihkan.' : 'Gambar layanan disembunyikan.');
    }

    public function editCover(int $id): void
    {
        $this->guard();
        abort_unless($this->tab === 'catalog' && in_array($this->mediaTab, ['branches', 'promos'], true), 403);
        $this->reset('cover');
        $this->coverEditing = $id;
    }

    public function saveCover(Masters $masters): void
    {
        $user = $this->guard();
        abort_unless($this->tab === 'catalog' && $this->coverEditing, 403);
        $this->validate(['cover' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $masters->publicCover($user, $this->mediaTab, $this->coverEditing, 'replace', $this->cover);
        $this->clear();
        session()->flash('success', 'Foto baru berhasil disimpan.');
    }

    public function updateCover(int $id, bool $restore, Masters $masters): void
    {
        $user = $this->guard();
        abort_unless($this->tab === 'catalog', 403);
        $masters->publicCover($user, $this->mediaTab, $id, $restore ? 'default' : 'hide');
        session()->flash('success', $restore ? 'Foto bawaan dipulihkan.' : 'Foto sudah disembunyikan.');
    }

    public function deleteService(int $id, Masters $m)
    {
        $u = $this->guard();
        abort_unless($this->tab === 'catalog', 403);
        $m->removeService($u, $id);
        if ($this->editing === $id) {
            $this->clear();
        }
        session()->flash('success', 'Layanan dihapus dari katalog. Pesanan yang sudah dibuat tetap dapat dibuka.');
    }

    public function addQrisGroup(Masters $m)
    {
        $u = $this->guard();
        abort_unless($this->tab === 'qris', 403);
        $this->validate(['newQrisImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $group = $m->createQrisGroup($u, $this->qrisGroupName, $this->newQrisImage);
        $this->qrisId = $group->id;
        $this->reset('qrisGroupName', 'newQrisImage', 'file');
        session()->flash('success', 'Grup QRIS ditambahkan. Pilih grup ini saat mengatur cabang.');
    }

    public function updatedQrisId()
    {
        $this->reset('file');
    }

    public function save(Masters $m)
    {
        $u = $this->guard();
        switch ($this->tab) {
            case 'catalog':
                $d = $this->d;
                if ($this->editing) {
                    $this->validate([
                        'poster' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
                        'confirmPosterPrice' => $this->poster ? 'accepted' : 'boolean',
                        'd.min_price' => $this->pricePending ? 'nullable' : 'required|integer|min:1|max:100000000',
                        'd.max_price' => $this->pricePending ? 'nullable' : 'required|integer|min:1|max:100000000',
                    ], ['confirmPosterPrice.accepted' => 'Centang kecocokan harga pada gambar dengan harga pembayaran.']);
                    $d['pricing_mode'] = $this->pricePending ? 'QUOTE' : ((int) $d['min_price'] === (int) $d['max_price'] ? 'FIXED' : 'RANGE');
                    $d['poster_source'] = $this->poster || ! $this->autoPoster ? 'CUSTOM' : 'AUTO';
                    if ($this->pricePending) {
                        $d['min_price'] = $d['max_price'] = null;
                    }
                }
                $m->tariff($u, $this->editing, $d, $this->cover, $this->poster, $this->tariffVersion);
                break;
            case 'branches':
                $this->validate(['file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120']);
                $m->branch($u, $this->editing, $this->d, $this->cover, $this->file);
                break;
            case 'accounts':$m->account($u, $this->editing, $this->d);
                break;
            case 'promos':$d = $this->d;
                foreach (['starts_at', 'ends_at'] as $key) {
                    if ($d[$key]) {
                        $d[$key] = Carbon::parse($d[$key], 'Asia/Jakarta')->utc()->toIso8601String();
                    }
                }
                $m->promotion($u, $this->editing, $d, $this->cover);
                break;
            default:abort(403);
        }
        $this->clear();
        session()->flash('success', 'Perubahan disimpan. Versi transaksi lama tetap utuh.');
    }

    public function saveQris(Masters $m)
    {
        $u = $this->guard();
        abort_unless($this->tab === 'qris', 403);
        $this->validate(['file' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $m->qris($u, $this->qrisId, $this->file);
        $this->reset('file');
        session()->flash('success', 'QRIS grup versi baru diterbitkan.');
    }

    public function saveShipping(Masters $m)
    {
        $u = $this->guard();
        abort_unless($this->tab === 'branches', 403);
        $m->shipping($u, $this->rate);
        session()->flash('success', 'Tarif jemput versi baru diterbitkan.');
    }

    public function archive(int $id, Masters $m)
    {
        $u = $this->guard();
        abort_unless($this->tab === 'promos', 403);
        $m->archivePromotion($u, $id);
        if ($this->editing === $id) {
            $this->clear();
        }
        session()->flash('success', 'Promo diarsipkan.');
    }

    public function showPromoArchive(bool $archive)
    {
        $this->guard();
        abort_unless($this->tab === 'promos', 403);
        $this->promoArchive = $archive;
        $this->clear();
    }

    public function switchPromotion(int $id, Masters $masters): void
    {
        $user = $this->guard();
        abort_unless($this->tab === 'promos', 403);
        $masters->togglePromotion($user, $id);
        if ($this->editing === $id) {
            $this->edit($id);
        }
        session()->flash('success', 'Status promo tersimpan.');
    }

    public function updateAccountAccess(int $id, bool $archive, Masters $masters): void
    {
        $user = $this->guard();
        abort_unless($this->tab === 'accounts', 403);
        $masters->accountAccess($user, $id, $archive);
        if ($this->editing === $id) {
            $this->clear();
        }
        session()->flash('success', $archive ? 'Akun diarsipkan dan akses login dicabut.' : 'Status akun tersimpan.');
    }

    public function setGrantGroup(string $role, int $index, bool $on): void
    {
        $this->guard();
        abort_unless($this->tab === 'permissions', 403);
        $members = array_values(self::GRANT_GROUPS[$role] ?? [])[$index] ?? [];
        abort_if($members === [], 404);
        $current = $this->roleGrants[$role] ?? [];
        $this->roleGrants[$role] = array_values($on ? array_unique(array_merge($current, $members)) : array_diff($current, $members));
    }

    public function savePermissions(Masters $m)
    {
        $u = $this->guard();
        abort_unless($this->tab === 'permissions', 403);
        $data = $this->validate(['roleGrants' => 'required|array', 'roleGrants.*' => 'array|max:12', 'roleGrants.*.*' => 'string|max:32']);
        $m->permissions($u, $data['roleGrants']);
        session()->flash('success', 'Izin diperbarui.');
    }

    public function sync(bool $embed, Knowledge $k)
    {
        $this->guard();
        abort_unless($this->tab === 'knowledge', 403);
        try {
            $r = $k->sync($embed);
            $this->indexMessage = "{$r['documents']} dokumen dibaca; {$r['embedded']} chunk baru di-embed.";
        } catch (\Throwable $e) {
            $this->addError('knowledge', $e->getMessage());
        }
    }

    public function uploadKnowledge(Knowledge $k)
    {
        $this->guard();
        abort_unless($this->tab === 'knowledge', 403);
        $this->validate(['file' => 'required|file|mimes:pdf,txt,md|max:10240']);
        $name = Str::uuid().'-'.preg_replace('/[^a-zA-Z0-9._-]/', '-', basename($this->file->getClientOriginalName()));
        File::ensureDirectoryExists(config('puro.knowledge_directory'));
        File::copy($this->file->getRealPath(), config('puro.knowledge_directory').'/'.$name);
        $this->reset('file');
        $this->sync(false, $k);
    }

    public function toggleDocument(int $id)
    {
        $this->guard();
        abort_unless($this->tab === 'knowledge', 403);
        $d = KnowledgeDocument::findOrFail($id);
        $d->update(['active' => ! $d->active]);
    }

    private function staffAccounts(StaffUser $u): Builder
    {
        return StaffUser::with('role', 'branch')
            ->where('username', '<>', '__system')
            ->whereNull('archived_at')
            ->when($u->isRole('admin'), fn ($q) => $q
                ->where('branch_id', $u->branch_id)
                ->where(fn ($allowed) => $allowed->whereKey($u->id)->orWhereHas('role', fn ($role) => $role->whereIn('code', ['tech', 'qc', 'courier']))));
    }

    private function filteredAccounts(StaffUser $u)
    {
        $q = $this->staffAccounts($u);
        if ($u->isRole('superadmin')) {
            if ($this->accountBranch === 'central') {
                $q->whereNull('branch_id');
            } elseif (ctype_digit($this->accountBranch)) {
                $q->where('branch_id', (int) $this->accountBranch);
            }
        }
        if (ctype_digit($this->accountRole)) {
            $q->where('role_id', (int) $this->accountRole);
        }
        if (in_array($this->accountStatus, ['active', 'inactive'], true)) {
            $q->where('active', $this->accountStatus === 'active');
        }
        if ($name = trim(mb_substr($this->accountSearch, 0, 100))) {
            $q->where(fn ($w) => $w->where('full_name', 'ilike', '%'.$name.'%')->orWhere('username', 'ilike', '%'.$name.'%'));
        }
        match ($this->accountSort) {
            'branch' => $q->orderBy(Branch::select('name')->whereColumn('branches.id', 'staff_users.branch_id')),
            'role' => $q->orderBy(Role::select('name')->whereColumn('roles.id', 'staff_users.role_id')),
            'username' => $q->orderBy('username'),
            default => $q,
        };

        return $q->orderBy('full_name')->orderBy('id')->paginate(12, pageName: 'staffPage');
    }

    public function updated(string $property)
    {
        if (in_array($property, ['accountBranch', 'accountRole', 'accountStatus', 'accountSearch', 'accountSort'], true)) {
            $this->resetPage('staffPage');
        }
        if ($this->tab === 'reports' && in_array($property, ['from', 'to'], true)) {
            $this->reportPeriod = 'custom';
        }
    }

    public function useReportPeriod(string $period, Reports $reports): void
    {
        $user = $this->guard();
        abort_unless($this->tab === 'reports' && in_array($period, ['7', '30', 'all'], true), 404);
        $this->reportPeriod = $period;
        $today = now(config('puro.timezone'));
        $this->from = $period === 'all' ? $reports->firstDay($user) : $today->copy()->subDays((int) $period - 1)->toDateString();
        $this->to = $today->toDateString();
    }

    public function render(Reports $reports)
    {
        $u = $this->guard();
        $rows = match ($this->tab) {
            'catalog' => match ($this->mediaTab) {
                'services' => Service::with('latestTariff.cover', 'latestTariff.poster', 'category')->whereNull('archived_at')->whereHas('latestTariff')->orderBy('display_order')->orderBy('id')->get(),
                'branches' => Branch::with('cover')->orderBy('id')->get(),
                'promos' => Promotion::with('cover', 'latestVersion')->whereNull('branch_id')->whereNull('archived_at')->orderBy('id')->get(),
            },
            'branches' => Branch::with('qrisAccount')->orderBy('id')->get(),
            'qris' => QrisAccount::where('active', true)->with(['latestVersion.media', 'branches'])->orderBy('id')->get(),
            'accounts' => $this->filteredAccounts($u),
            'promos' => Promotion::with('latestVersion', 'branch')
                ->when($this->promoArchive, fn ($q) => $q->whereNotNull('archived_at')->orderByDesc('archived_at'), fn ($q) => $q->whereNull('archived_at'))
                ->when($u->isRole('admin'), fn ($q) => $q->where('branch_id', $u->branch_id))
                ->orderBy('code')->get(),
            'knowledge' => KnowledgeDocument::withCount(['chunks', 'chunks as indexed_count' => fn ($q) => $q->whereNotNull('embedding')->where('embedding_model', config('puro.embedding_model'))])->get(),
            'permissions' => Role::orderBy('id')->whereIn('code', array_keys(self::GRANT_GROUPS))->get(),
            default => collect(),
        };
        $sales = null;
        $reportError = null;
        $reportParams = [];
        if ($this->tab === 'reports') {
            $branch = $u->isRole('superadmin') && ctype_digit($this->reportBranch) ? (int) $this->reportBranch : null;
            try {
                $sales = $reports->analytics($u, $this->from, $this->to, $branch);
                $reportParams = ['from' => $this->from, 'to' => $this->to, 'branch' => $branch];
            } catch (ValidationException $e) {
                $reportError = $e->validator->errors()->first();
            }
        }

        return view('livewire.management', compact('rows', 'sales', 'u', 'reportError', 'reportParams') + [
            'categories' => ServiceCategory::orderBy('display_order')->get(),
            'branches' => Branch::when($u->isRole('admin'), fn ($q) => $q->whereKey($u->branch_id))->orderBy('name')->get(),
            'groups' => $this->tab === 'branches' ? QrisAccount::where('active', true)->with('latestVersion.media')->withCount('branches')->orderBy('id')->get() : collect(),
            'roles' => Role::when($u->isRole('admin'), fn ($q) => $q->whereIn('code', ['tech', 'qc', 'courier']))->orderBy('name')->get(),
            'grantGroups' => self::GRANT_GROUPS,
            'permissionLabels' => $this->tab === 'permissions' ? Permission::pluck('name', 'code') : collect(),
            'activeGlobalPromos' => $this->tab === 'promos' && $u->isRole('admin') ? Promotion::whereNull('branch_id')->whereNull('archived_at')->where('active', true)->orderBy('code')->pluck('code') : collect(),
        ])->title('Manajemen · PURO');
    }
}
