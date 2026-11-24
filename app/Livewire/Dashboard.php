<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Order;
use App\Services\OrderAccess;
use App\Services\Reports;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $branch = '';

    #[Locked]
    public ?int $orderId = null;

    public function mount(?int $id = null): void
    {
        if ($id !== null) {
            app(OrderAccess::class)->view(auth()->user()->fresh(), Order::findOrFail($id));
            $this->orderId = $id;
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedBranch()
    {
        $this->resetPage();
    }

    public function render(OrderAccess $access, Reports $reports)
    {
        $actor = auth()->user()->fresh();
        $branches = $actor->isRole('superadmin') ? Branch::orderBy('id')->get() : collect();
        $selectedBranch = $branches->firstWhere('id', (int) $this->branch);
        $q = $access->scope(Order::query(), $actor)
            ->when($selectedBranch, fn ($query) => $query->where('branch_id', $selectedBranch->id));
        $counts = (clone $q)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $today = now(config('puro.timezone'))->startOfDay();
        $sales = null;
        $weekly = [];
        $branchSales = collect();
        if ($actor->allows('reports') && ($actor->isRole('superadmin') || $actor->isRole('admin'))) {
            $payments = DB::table('verified_sales as v')
                ->whereIn('v.order_id', (clone $q)->select('orders.id'));
            $sales = (int) (clone $payments)->sum('v.total');
            $weekly = $reports->weekly(clone $payments, $today);
            if ($branches->isNotEmpty()) {
                $totals = DB::table('verified_sales as v')->join('orders as o', 'o.id', '=', 'v.order_id')
                    ->selectRaw('o.branch_id, count(*) as payments, sum(v.total) as total')->groupBy('o.branch_id')->get()->keyBy('branch_id');
                $branchSales = $branches->map(fn ($item) => [
                    'id' => $item->id, 'name' => $item->name,
                    'total' => (int) ($totals->get($item->id)?->total ?? 0),
                    'count' => (int) ($totals->get($item->id)?->payments ?? 0),
                ]);
            }
        }

        $statusFilters = [
            'assessment' => ['WAITING_ASSESSMENT', 'NEEDS_INSPECTION', 'WAITING_APPROVAL', 'WAITING_PAYMENT', 'QUOTE_REJECTED'],
            'active' => ['WAITING_PICKUP', 'PICKING_UP', 'WAITING_HANDOVER', 'WAITING_TECH', 'CLEANING', 'WAITING_QC', 'REWORK', 'READY_DELIVERY', 'OUT_FOR_DELIVERY', 'READY_COLLECTION'],
        ];
        $rows = $q->with(['tariff', 'branch', 'currentQuote.lines', 'items' => fn ($query) => $query->limit(1)])->withCount('items')
            ->when($this->status, fn ($q) => $q->whereIn('status', $statusFilters[$this->status] ?? [$this->status]))->when($this->search, function ($q) {
                $hash = hash('sha256', strtoupper(trim($this->search)));
                $s = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_substr($this->search, 0, 100)).'%';
                $q->where(fn ($r) => $r->where('customer_name', 'ilike', $s)->orWhere('access_code_hash', $hash));
            })->orderByDesc('created_at')->orderByDesc('id')->paginate(15);

        return view('livewire.dashboard', [
            'orders' => $rows, 'counts' => $counts, 'actor' => $actor, 'branches' => $branches, 'selectedBranch' => $selectedBranch,
            'today' => $today, 'sales' => $sales, 'weekly' => $weekly, 'branchSales' => $branchSales,
        ])->title('Dashboard Staf · PURO');
    }
}
