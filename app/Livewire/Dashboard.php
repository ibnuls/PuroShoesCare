<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\OrderAccess;
use Livewire\Attributes\Layout;
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

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function render(OrderAccess $access)
    {
        $q = $access->scope(Order::query(), auth()->user()->fresh());
        $counts = (clone $q)->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $rows = $q->with(['tariff', 'branch', 'latestTreatment.technician', 'verifiedPayment'])->when($this->status, fn ($q) => $q->where('status', $this->status))->when($this->search, function ($q) {
            $hash = hash('sha256', strtoupper(trim($this->search)));
            $s = '%'.str_replace(['%', '_'], ['\\%', '\\_'], mb_substr($this->search, 0, 100)).'%';
            $q->where(fn ($r) => $r->where('customer_name', 'ilike', $s)->orWhere('access_code_hash', $hash));
        })->orderByDesc('created_at')->paginate(15);

        return view('livewire.dashboard', ['orders' => $rows, 'counts' => $counts])->title('Dashboard Staf · PURO');
    }
}
