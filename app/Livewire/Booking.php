<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Service;
use App\Models\ShippingPolicy;
use App\Services\Pricing;
use App\Services\Workflow;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Booking extends Component
{
    use WithFileUploads;

    public array $d = ['service' => '', 'branch' => '', 'name' => '', 'phone' => '', 'address' => '', 'mode' => 'BOTH', 'distance' => '', 'express' => false, 'promo' => '', 'notes' => '', 'items' => [['description' => '', 'condition' => '']], 'request_key' => ''];

    public string $quantity = '1';

    public string $promoInput = '';

    public array $photos = [];

    public array $uploads = [];

    public function mount()
    {
        $this->d['request_key'] = (string) Str::uuid();
        $this->d['items'][0]['_key'] = (string) Str::uuid();
        $services = Service::where('active', true)->whereNull('archived_at')->whereHas('latestTariff');
        $this->d['service'] = (clone $services)->whereKey(request()->integer('service'))->value('id')
            ?? (clone $services)->where('code', 'deep')->value('id')
            ?? $services->orderBy('display_order')->orderBy('id')->value('id') ?? '';
        $branches = Branch::where('active', true);
        $this->d['branch'] = (clone $branches)->whereKey(request()->integer('branch'))->value('id')
            ?? $branches->orderBy('id')->value('id') ?? '';
        $promo = request()->query('promo');
        if (is_string($promo) && preg_match('/^[A-Za-z0-9-]{3,32}$/D', $promo)) {
            $this->d['promo'] = strtoupper($promo);
            $this->promoInput = $this->d['promo'];
        }
    }

    public function updatedQuantity(): void
    {
        if (! preg_match('/^(?:[1-9]|1[0-9]|20)$/D', $this->quantity)) {
            return;
        }
        $count = (int) $this->quantity;
        $this->d['items'] = array_slice($this->d['items'], 0, $count);
        $this->photos = array_intersect_key($this->photos, $this->d['items']);
        while (count($this->d['items']) < $count) {
            $this->d['items'][] = ['description' => '', 'condition' => '', '_key' => (string) Str::uuid()];
        }
    }

    public function updatedUploads($files, int $index): void
    {
        if (! isset($this->d['items'][$index])) {
            unset($this->uploads[$index]);

            return;
        }
        try {
            $this->validate([
                "uploads.$index" => 'array|max:3',
                "uploads.$index.*" => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);
            $combined = array_merge($this->photos[$index] ?? [], $files ?? []);
            if (count($combined) > 3) {
                $this->addError("uploads.$index", 'Maksimal tiga foto per barang.');

                return;
            }
            $this->photos[$index] = $combined;
            $this->resetValidation("uploads.$index");
        } finally {
            unset($this->uploads[$index]);
        }
    }

    public function removePhoto(int $item, int $photo): void
    {
        if (isset($this->photos[$item][$photo])) {
            array_splice($this->photos[$item], $photo, 1);
        }
    }

    public function applyPromo(Pricing $pricing): void
    {
        $this->validate(['promoInput' => 'nullable|regex:/^[A-Za-z0-9-]{3,32}$/D']);
        $code = strtoupper(trim($this->promoInput));
        if ($code !== '' && ! $pricing->promotion($code, (int) $this->d['branch'])) {
            $this->addError('promoInput', 'Kode promo tidak berlaku untuk cabang ini.');

            return;
        }
        $this->d['promo'] = $code;
    }

    public function updatedDService()
    {
        $this->d['express'] = false;
        $this->resetValidation();
    }

    public function updatedDBranch()
    {
        $this->d['distance'] = '';
        if (trim($this->d['promo']) !== '' && ! app(Pricing::class)->promotion($this->d['promo'], (int) $this->d['branch'])) {
            $this->d['promo'] = '';
        }
    }

    public function gps($lat, $lng, Pricing $pricing)
    {
        $this->validate(['d.branch' => 'required|exists:branches,id']);
        abort_unless(is_numeric($lat) && is_numeric($lng), 422);
        $b = Branch::findOrFail($this->d['branch']);
        if ($b->latitude === null || $b->longitude === null) {
            $this->addError('d.distance', 'Koordinat cabang belum tersedia.');

            return;
        }
        $this->d['distance'] = $pricing->distance((float) $lat, (float) $lng, $b->latitude, $b->longitude);
    }

    public function submit(Workflow $workflow)
    {
        $this->validate(['quantity' => ['required', 'regex:/^(?:[1-9]|1[0-9]|20)$/D']]);
        $this->updatedQuantity();
        $key = 'book:'.hash('sha256', request()->ip());
        if (RateLimiter::tooManyAttempts($key, 12)) {
            $this->addError('workflow', 'Terlalu banyak booking. Coba beberapa menit lagi.');

            return;
        }
        RateLimiter::hit($key, 300);
        $data = $this->d;
        foreach ($data['items'] as &$item) {
            $item['description'] = $data['items'][0]['description'];
        }
        unset($item);
        $o = $workflow->book($data, $this->photos, session()->getId());
        session()->flash('success', 'Booking diterima. Simpan kode pesanan ini.');
        $route = $o->status === 'WAITING_PAYMENT' ? 'book.checkout' : 'track';
        $this->redirectRoute($route, ['code' => $o->access_code], navigate: true);
    }

    public function render(Pricing $pricing)
    {
        $services = Service::where('active', true)->whereNull('archived_at')->whereHas('latestTariff')
            ->with('latestTariff', 'category')->orderBy('display_order')->orderBy('id')->get();
        $service = $services->firstWhere('id', (int) $this->d['service']);
        $estimate = null;
        $pricingError = null;
        $policy = ShippingPolicy::where('code', 'PICKUP')->orderByDesc('version')->firstOrFail();
        $branch = Branch::where('active', true)->find($this->d['branch']);
        $promo = null;
        try {
            if ($service && $branch) {
                $promo = $pricing->promotion($this->d['promo'], $branch->id);
                $estimate = $pricing->estimate($service->latestTariff, count($this->d['items']), (bool) $this->d['express'], $this->d['mode'], $this->d['distance'] === '' ? null : (float) $this->d['distance'], $policy, $promo);
            }
        } catch (ValidationException $e) {
            $pricingError = $e->validator->errors()->first();
        }

        return view('livewire.booking', compact('services', 'service', 'estimate', 'pricingError', 'policy', 'promo') + ['branches' => Branch::where('active', true)->orderBy('id')->get()])->title('Buat Pesanan · PURO');
    }
}
