<div class="container page-shell">
    <div class="page-intro">
        <div><span class="eyebrow">STORES / {{ $branches->count() > 1 ? '01—' : '' }}{{ str_pad((string) $branches->count(), 2, '0', STR_PAD_LEFT) }}</span><h1>Find your<br><span class="accent-word">nearest care.</span></h1></div>
        <p>{{ config('puro.demo') ? 'Lokasi demo dengan operasional masing-masing cabang. Pilih cabang saat booking; staf cabang lain tidak bisa mengelola pesanannya.' : 'Pilih cabang terdekat saat booking. Tim di cabang tersebut akan menangani pesanan dan perawatan barangmu.' }}</p>
    </div>
    <div class="location-grid">
        @forelse ($branches as $branch)
            <article class="location-card" id="cabang-{{ $branch->id }}">
                <div>
                    <div class="loc-counter">STORE / {{ str_pad((string) $branch->id, 2, '0', STR_PAD_LEFT) }} — {{ mb_strtoupper($branch->area) }}</div>
                    <h2>{{ $branch->name }}</h2><p>{{ $branch->address }}</p>
                    <div class="loc-actions">
                        <a href="{{ route('book', ['branch' => $branch->id]) }}" wire:navigate class="btn btn-dark btn-sm">Booking di sini ↗</a>
                        @if ($branch->latitude !== null && $branch->longitude !== null)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($branch->latitude . ',' . $branch->longitude) }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm">Peta ↗</a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p>Belum ada cabang yang menerima pesanan.</p>
        @endforelse
    </div>
    @if (config('puro.demo'))
        <div class="info-bar"><strong>CATATAN DEMO:</strong> Alamat/koordinat hanya titik contoh di Jakarta. Super Admin mengaturnya melalui Manajemen Cabang.</div>
    @endif
</div>
