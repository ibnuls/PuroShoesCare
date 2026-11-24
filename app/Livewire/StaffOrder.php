<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\StaffUser;
use App\Services\OrderAccess;
use App\Services\Workflow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class StaffOrder extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $id;

    #[Locked]
    public array $backFilters = [];

    public array $prices = [];

    public string $note = '';

    public string $deliveryNote = '';

    public string $typedReference = '';

    public bool $bankChecked = false;

    public bool $expressAccepted = false;

    public string $assignee = '';

    public $photo;

    public function mount(int $id, OrderAccess $a, array $backFilters = [])
    {
        $o = Order::findOrFail($id);
        $a->view(auth()->user()->fresh(), $o);
        $this->id = $id;
        $this->backFilters = $backFilters;
        foreach ($o->items as $it) {
            $this->prices[$it->id] = (string) ($o->tariff->min_price ?? '');
        }
    }

    private function done(string $message = 'Perubahan tersimpan.')
    {
        session()->flash('success', $message);
        $this->reset('note', 'deliveryNote', 'typedReference', 'bankChecked', 'photo', 'assignee');
    }

    public function quote(int $version, Workflow $w)
    {
        $w->quote(auth()->user(), $this->id, $this->prices, $this->note, $this->expressAccepted, $version);
        $this->done();
    }

    public function requestInspection(Workflow $w)
    {
        $w->requestInspection(auth()->user(), $this->id, $this->note);
        $this->done();
    }

    public function review(int $submissionId, bool $accept, Workflow $w)
    {
        $w->reviewPayment(auth()->user(), $this->id, $submissionId, $accept, $this->typedReference, $this->note, $this->bankChecked);
        $this->done($accept ? 'Pembayaran diverifikasi.' : 'Bukti ditolak. Pesanan masih aktif.');
    }

    public function cancel(Workflow $w)
    {
        $w->cancel(auth()->user(), $this->id, $this->note);
        $this->done('Pesanan dibatalkan.');
    }

    public function receive(Workflow $w)
    {
        $this->validate(['photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $w->receive(auth()->user(), $this->id, $this->photo);
        $this->done();
    }

    public function assignCourier(Workflow $w)
    {
        $this->validate(['assignee' => 'required|integer']);
        $w->assignCourier(auth()->user(), $this->id, (int) $this->assignee);
        $this->done();
    }

    public function assignTech(Workflow $w)
    {
        $this->validate(['assignee' => 'required|integer']);
        $w->assignTech(auth()->user(), $this->id, (int) $this->assignee);
        $this->done();
    }

    public function courier(int $task, bool $finish, Workflow $w)
    {
        if ($finish) {
            $this->validate([
                'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
                'deliveryNote' => 'nullable|string|max:250',
            ]);
        }
        $w->courier(auth()->user(), $this->id, $task, $finish, $this->photo, $this->deliveryNote);
        $this->done();
    }

    public function treatment(int $cycle, bool $finish, Workflow $w)
    {
        $this->validate(['photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $w->treatment(auth()->user(), $this->id, $cycle, $finish, $this->photo, $this->note);
        $this->done();
    }

    public function qc(int $cycle, bool $pass, Workflow $w)
    {
        $w->qc(auth()->user(), $this->id, $cycle, $pass, $this->note);
        $this->done();
    }

    public function collect(Workflow $w)
    {
        $w->collect(auth()->user(), $this->id);
        $this->done();
    }

    public function render(OrderAccess $a)
    {
        $u = auth()->user()->fresh();
        $o = Order::with(['tariff', 'branch', 'items.photos.media', 'currentQuote.latestSubmission.reference', 'currentQuote.latestSubmission.proof', 'currentQuote.lines', 'verifiedPayment', 'tasks.courier', 'latestTreatment.technician', 'treatments.photos.media', 'treatments.qc', 'history', 'cancellation', 'latestInspection'])->findOrFail($this->id);
        $a->view($u, $o);

        return view('livewire.staff-order', ['order' => $o, 'user' => $u, 'staff' => StaffUser::where('branch_id', $o->branch_id)->where('active', true)->with('role')->get()])->title('Rincian Pesanan · PURO');
    }
}
