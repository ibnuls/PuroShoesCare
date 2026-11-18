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
        return view('livewire.public-page', ['categories' => ServiceCategory::orderBy('display_order')->with(['services' => fn ($q) => $q->where('active', true)->orderBy('display_order')->with('latestTariff.cover')])->get(), 'branches' => Branch::where('active', true)->with('cover')->get(), 'promotions' => Promotion::with('latestVersion', 'branch', 'cover')->where('active', true)->whereNull('archived_at')->get()->filter(fn ($p) => $p->latestVersion && (! $p->latestVersion->starts_at || $p->latestVersion->starts_at->lte(now())) && (! $p->latestVersion->ends_at || $p->latestVersion->ends_at->gte(now()))), 'documents' => KnowledgeDocument::where('active', true)->with('chunks')->get()])->title(match ($this->page) {
            'services' => 'Layanan & FAQ','locations' => 'Lokasi Cabang','guide' => 'Panduan Customer','about' => 'Tentang PURO','contact' => 'Hubungi PURO',default => 'PURO · Pure Care Pure Solution'
        });
    }
}
