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

    public array $d = ['service' => '', 'branch' => '', 'name' => '', 'phone' => '', 'address' => '', 'mode' => 'DROPOFF', 'distance' => '', 'express' => false, 'promo' => '', 'notes' => '', 'items' => [['description' => '', 'condition' => '']], 'request_key' => ''];

    public array $photos = [];

    public function mount()
    {
        $this->d['request_key'] = (string) Str::uuid();
        $this->d['items'][0]['_key'] = (string) Str::uuid();
        $this->d['service'] = request()->integer('service') ?: Service::where('active', true)->value('id');
        $this->d['branch'] = Branch::where('active', true)->value('id');
    }

    public function addItem()
    {
        if (count($this->d['items']) < 20) {
            $this->d['items'][] = ['description' => '', 'condition' => '', '_key' => (string) Str::uuid()];
        }
    }

    public function removeItem(int $i)
    {
        if (count($this->d['items']) > 1 && isset($this->d['items'][$i])) {
            array_splice($this->d['items'], $i, 1);
            $shifted = [];
            foreach ($this->photos as $index => $files) {
                if ($index !== $i) {
                    $shifted[$index > $i ? $index - 1 : $index] = $files;
                }
            }
            $this->photos = $shifted;
        }
    }

    public function updatedDService()
    {
        $this->d['express'] = false;
        $this->resetValidation();
    }

    public function updatedDBranch()
    {
        $this->d['distance'] = '';
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
        $key = 'book:'.hash('sha256', request()->ip());
        if (RateLimiter::tooManyAttempts($key, 12)) {
            $this->addError('workflow', 'Terlalu banyak booking. Coba beberapa menit lagi.');

            return;
        }
        RateLimiter::hit($key, 300);
        $o = $workflow->book($this->d, $this->photos, session()->getId());
        session()->flash('success', 'Booking diterima. Simpan kode pesanan ini.');
        $this->redirectRoute('track', ['code' => $o->access_code], navigate: true);
    }

    public function render(Pricing $pricing)
    {
        $services = Service::where('active', true)->with('latestTariff', 'category')->orderBy('display_order')->get();
        $service = $services->firstWhere('id', (int) $this->d['service']);
        $estimate = null;
        $pricingError = null;
        try {
            if ($service && $this->d['branch']) {
                $estimate = $pricing->estimate($service->latestTariff, count($this->d['items']), (bool) $this->d['express'], $this->d['mode'], $this->d['distance'] === '' ? null : (float) $this->d['distance'], ShippingPolicy::orderByDesc('version')->firstOrFail(), $pricing->promotion($this->d['promo'], (int) $this->d['branch']));
            }
        } catch (ValidationException $e) {
            $pricingError = $e->validator->errors()->first();
        }

        return view('livewire.booking', compact('services', 'service', 'estimate', 'pricingError') + ['branches' => Branch::where('active', true)->get()])->title('Buat Pesanan · PURO');
    }
}
