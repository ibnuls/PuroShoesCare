<?php

namespace App\Livewire;

use App\Models\Order;
use App\Services\Workflow;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Tracking extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $code = '';

    public string $search = '';

    public string $reason = '';

    public string $reference = '';

    public string $amount = '';

    public bool $confirmed = false;

    public $proof;

    public string $requestKey = '';

    public function mount(string $code = '')
    {
        $this->code = strtoupper(trim($code));
        $this->search = $this->code;
        $this->requestKey = (string) Str::uuid();
        if ($o = $this->order()) {
            $this->amount = (string) ($o->total() ?? '');
        }
    }

    public function order(): ?Order
    {
        return $this->code ? Order::with(['branch', 'tariff', 'currentQuote.latestSubmission.reference', 'history', 'latestInspection', 'cancellation', 'qrisVersion.media'])->where('access_code_hash', hash('sha256', $this->code))->first() : null;
    }

    public function lookup()
    {
        $this->validate(['search' => 'required|string|max:40']);
        $this->redirectRoute('track', ['code' => strtoupper(trim($this->search))], navigate: true);
    }

    public function decide(int $q, bool $accept, Workflow $w)
    {
        $w->decideQuote($this->code, $q, $accept, $this->reason);
        $this->reason = '';
        $o = $this->order();
        $this->amount = (string) ($o?->total() ?? '');
        session()->flash('success', $accept ? 'Harga disetujui. Silakan lanjutkan pembayaran.' : 'Keputusan tersimpan.');
    }

    public function inspection(int $id, bool $accept, Workflow $w)
    {
        $w->consentInspection($this->code, $id, $accept);
        session()->flash('success', 'Keputusan pemeriksaan tersimpan.');
    }

    public function reassess(Workflow $w)
    {
        $w->reassess($this->code);
        session()->flash('success', 'Permintaan penilaian ulang dikirim.');
    }

    public function pay(int $quoteId, int $qrisVersion, Workflow $w)
    {
        $this->validate(['confirmed' => 'accepted', 'amount' => 'required|integer|min:1', 'proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', 'reference' => 'required|string|max:80']);
        $key = 'proof:'.hash('sha256', request()->ip().$this->code);
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('workflow', 'Tunggu sebentar sebelum mengirim lagi.');

            return;
        }
        RateLimiter::hit($key, 300);
        $w->submitPayment($this->code, $quoteId, $this->reference, (int) $this->amount, $this->proof, $this->requestKey, $qrisVersion);
        $this->reset('proof', 'confirmed', 'reference');
        $this->requestKey = (string) Str::uuid();
        session()->flash('success', 'Bukti berhasil dikirim. Menunggu verifikasi admin.');
    }

    public function render()
    {
        return view('livewire.tracking', ['order' => $this->order()])->title('Cek Status · PURO');
    }
}
