<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\KnowledgeDocument;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Management extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $tab;

    #[Locked]
    public ?int $editing = null;

    public array $d = [];

    public $cover;

    public $poster;

    public $file;

    public int $qrisId = 1;

    public int $rate = 5000;

    public int $roleId = 0;

    public array $codes = [];

    public string $from = '';

    public string $to = '';

    public string $indexMessage = '';

    private function guard()
    {
        $u = auth()->user()?->fresh();
        abort_unless($u && $u->allows($this->tab) && ($u->isRole('superadmin') || ($u->isRole('admin') && in_array($this->tab, ['accounts', 'promos', 'reports']))), 403);

        return $u;
    }

    public function mount(string $tab)
    {
        $this->tab = $tab;
        $this->guard();
        $this->from = now()->timezone('Asia/Jakarta')->format('Y-m-01');
        $this->to = now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $this->rate = ShippingPolicy::orderByDesc('version')->value('rate_per_km');
        $this->clear();
    }

    public function clear()
    {
        $this->guard();
        $this->editing = null;
        $this->reset('cover', 'poster', 'file');
        $this->resetValidation();
        $this->d = match ($this->tab) {
            'catalog' => ['category_id' => ServiceCategory::value('id'), 'code' => '', 'display_name' => '', 'description' => '', 'pricing_mode' => 'FIXED', 'min_price' => '', 'max_price' => '', 'unit' => 'unit', 'duration_text' => '3–4 hari', 'express_available' => false, 'active' => true, 'poster_source' => 'AUTO', 'customer_note' => ''],'branches' => ['name' => '', 'area' => '', 'address' => '', 'latitude' => '', 'longitude' => '', 'qris_account_id' => QrisAccount::value('id'), 'active' => true],'accounts' => ['username' => '', 'full_name' => '', 'role_id' => Role::where('code', 'tech')->value('id'), 'branch_id' => auth()->user()->branch_id ?? Branch::value('id'), 'password' => '', 'active' => true],'promos' => ['code' => '', 'branch_id' => auth()->user()->branch_id ?? '', 'percent' => 10, 'cap_amount' => 15000, 'starts_at' => '', 'ends_at' => '', 'active' => true],default => []
        };
    }

    public function edit(int $id)
    {
        $u = $this->guard();
        $this->clear();
        $this->editing = $id;
        if ($this->tab === 'catalog') {
            $s = Service::findOrFail($id);
            $t = $s->latestTariff;
            $this->d = collect($t->getAttributes())->only(array_keys($this->d))->all() + ['code' => $s->code, 'category_id' => $s->category_id, 'active' => $s->active];
        } elseif ($this->tab === 'branches') {
            $this->d = Branch::findOrFail($id)->only(array_keys($this->d));
        } elseif ($this->tab === 'accounts') {
            $s = StaffUser::with('role')->findOrFail($id);
            abort_unless($s->username !== '__system' && ($u->isRole('superadmin') || ($s->branch_id === $u->branch_id && in_array($s->role->code, ['tech', 'qc', 'courier']))), 403);
            $this->d = $s->only(array_keys($this->d));
            $this->d['password'] = '';
        } elseif ($this->tab === 'promos') {
            $p = Promotion::findOrFail($id);
            abort_unless($u->isRole('superadmin') || $p->branch_id === $u->branch_id, 403);
            $v = $p->latestVersion;
            $this->d = ['code' => $p->code, 'branch_id' => $p->branch_id ?? '', 'active' => $p->active, 'percent' => $v->percent, 'cap_amount' => $v->cap_amount, 'starts_at' => $v->starts_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? '', 'ends_at' => $v->ends_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i') ?? ''];
        }
    }

    public function save(Masters $m)
    {
        $u = $this->guard();
        switch ($this->tab) {
            case 'catalog':$m->tariff($u, $this->editing, $this->d, $this->cover, $this->poster);
                break;
            case 'branches':$m->branch($u, $this->editing, $this->d, $this->cover);
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
        session()->flash('success', 'Promo diarsipkan.');
    }

    public function selectRole(int $id)
    {
        $this->guard();
        abort_unless($this->tab === 'permissions', 403);
        $r = Role::where('code', '<>', 'superadmin')->findOrFail($id);
        $this->roleId = $id;
        $this->codes = $r->permissions()->pluck('code')->all();
    }

    public function savePermissions(Masters $m)
    {
        $u = $this->guard();
        abort_unless($this->tab === 'permissions', 403);
        $m->permissions($u, $this->roleId, $this->codes);
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

    public function render(Reports $reports)
    {
        $u = $this->guard();
        $rows = match ($this->tab) {
            'catalog' => Service::with('latestTariff', 'category')->orderBy('display_order')->get(),'branches' => Branch::with('qrisAccount')->get(),'qris' => QrisAccount::with('latestVersion.media')->get(),'accounts' => StaffUser::with('role', 'branch')->where('username', '<>', '__system')->when($u->isRole('admin'), fn ($q) => $q->where('branch_id', $u->branch_id)->whereHas('role', fn ($r) => $r->whereIn('code', ['tech', 'qc', 'courier'])))->get(),'promos' => Promotion::with('latestVersion', 'branch')->whereNull('archived_at')->when($u->isRole('admin'), fn ($q) => $q->where('branch_id', $u->branch_id))->get(),'knowledge' => KnowledgeDocument::withCount(['chunks', 'chunks as indexed_count' => fn ($q) => $q->whereNotNull('embedding')->where('embedding_model', config('puro.embedding_model'))])->get(),'permissions' => Role::with('permissions')->where('code', '<>', 'superadmin')->get(),default => collect()
        };
        $sales = null;
        if ($this->tab === 'reports') {
            $q = $reports->query($u, $this->from, $this->to);
            $sales = ['total' => (int) (clone $q)->sum('v.total'), 'count' => (clone $q)->count(), 'rows' => (clone $q)->select(['b.name', 't.display_name', 'q.kind', 'o.status', 'v.total', 'v.verified_at'])->orderByDesc('v.verified_at')->limit(100)->get()];
        }

        return view('livewire.management', compact('rows', 'sales', 'u') + ['categories' => ServiceCategory::orderBy('display_order')->get(), 'branches' => Branch::all(), 'groups' => QrisAccount::all(), 'roles' => Role::when($u->isRole('admin'), fn ($q) => $q->whereIn('code', ['tech', 'qc', 'courier']))->get()])->title('Manajemen · PURO');
    }
}
