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

    #[Locked]
    public string $stage = 'tracking';

    public string $search = '';

    public string $reason = '';

    public string $reference = '';

    public string $amount = '';

    public bool $confirmed = false;

    public $proof;

    public string $requestKey = '';

    public function mount(string $code = '', string $stage = 'tracking')
    {
        if ($code === '' && request()->query('code') !== null) {
            $input = request()->query('code');
            if (is_string($input) && preg_match('/^[A-Za-z0-9-]{1,40}$/D', trim($input))) {
                $this->redirectRoute('track', ['code' => strtoupper(trim($input))]);

                return;
            }
            $this->addError('search', 'Masukkan kode pesanan yang valid.');
        }
        $this->stage = $stage;
        $this->code = strtoupper(trim($code));
        $this->search = $this->code;
        $this->requestKey = (string) Str::uuid();
        $o = $this->order();
        abort_if($stage !== 'tracking' && ! $o, 404);
        if ($o) {
            $this->amount = (string) ($o->total() ?? '');
            if (($stage === 'checkout' && ! in_array($o->status, ['WAITING_PAYMENT', 'PAYMENT_REJECTED'], true))
                || ($stage === 'success' && ! $o->currentQuote?->latestSubmission)) {
                $this->redirectRoute('track', ['code' => $this->code]);
            }
        }
    }

    public function order(): ?Order
    {
        return $this->code ? Order::with(['branch.qrisAccount.latestVersion.media', 'tariff', 'currentQuote.lines', 'currentQuote.latestSubmission.reference', 'history', 'latestInspection', 'cancellation', 'qrisVersion.account', 'qrisVersion.media', 'receipt', 'verifiedPayment', 'returnDelivery'])
            ->withCount('items')->where('access_code_hash', hash('sha256', $this->code))->first() : null;
    }

    public function lookup()
    {
        $this->search = trim($this->search);
        $this->validate(['search' => 'required|string|max:40|regex:/^[A-Za-z0-9-]+$/']);
        $this->redirectRoute('track', ['code' => strtoupper(trim($this->search))], navigate: true);
    }

    public function decide(int $q, bool $accept, Workflow $w)
    {
        $w->decideQuote($this->code, $q, $accept, $this->reason);
        $this->reason = '';
        $o = $this->order();
        $this->amount = (string) ($o?->total() ?? '');
        session()->flash('success', $accept ? 'Harga disetujui. Silakan lanjutkan pembayaran.' : 'Keputusan tersimpan.');
        if ($accept && $o?->status === 'WAITING_PAYMENT') {
            $this->redirectRoute('book.checkout', ['code' => $this->code], navigate: true);
        }
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
        if ($this->stage === 'checkout') {
            $this->redirectRoute('book.success', ['code' => $this->code], navigate: true);
        }
    }

    public function render()
    {
        $order = $this->order();

        return view('livewire.tracking', compact('order'))->title(match ($this->stage) {
            'checkout' => 'Checkout · PURO',
            'success' => 'Konfirmasi Pembayaran · PURO',
            default => 'Cek Status · PURO',
        });
    }
}
