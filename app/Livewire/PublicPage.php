<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\KnowledgeDocument;
use App\Models\Promotion;
use App\Models\ServiceCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PublicPage extends Component
{
    #[Locked]
    public string $page = 'home';

    public function mount(string $page = 'home')
    {
        abort_unless(in_array($page, ['home', 'services', 'locations', 'about', 'contact', 'guide']), 404);
        $this->page = $page;
    }

    public function render()
    {
        $data = match ($this->page) {
            'home', 'services' => [
                'categories' => ServiceCategory::orderByRaw("CASE WHEN code = 'headwear' THEN 1 ELSE 0 END")
                    ->orderBy('display_order')->orderBy('id')
                    ->with(['services' => fn ($query) => $query->where('active', true)->whereNull('archived_at')
                        ->whereHas('latestTariff')->orderBy('display_order')->orderBy('id')->with('latestTariff.cover', 'latestTariff.poster')])
                    ->get(),
            ],
            'guide' => ['documents' => KnowledgeDocument::where('active', true)->with('chunks')->get()],
            default => [],
        };

        if (in_array($this->page, ['home', 'locations'], true)) {
            $data['branches'] = Branch::where('active', true)->orderBy('id')->with('cover')->get();
        }

        if ($this->page === 'home') {
            $now = now();
            $data['promotions'] = Promotion::with('latestVersion', 'branch', 'cover')->where('active', true)
                ->whereNull('archived_at')->orderBy('id')->get()
                ->filter(fn ($promo) => $promo->latestVersion
                    && ($promo->branch_id === null || $promo->branch?->active)
                    && (! $promo->latestVersion->starts_at || $promo->latestVersion->starts_at->lte($now))
                    && (! $promo->latestVersion->ends_at || $promo->latestVersion->ends_at->gte($now)));
        }

        return view('livewire.public-page', $data)->title(match ($this->page) {
            'services' => 'Layanan & FAQ',
            'locations' => 'Lokasi Cabang',
            'guide' => 'Panduan Customer',
            'about' => 'Tentang PURO',
            'contact' => 'Hubungi PURO',
            default => 'PURO · Pure Care Pure Solution',
        });
    }
}
