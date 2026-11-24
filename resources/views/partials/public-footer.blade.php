<footer class="site-footer" id="site-footer" x-data>
    <div class="container footer-main">
        <div>
            <div class="footer-brand"><img src="{{ asset('assets/puro-logo-yellow.svg') }}" alt="PURO Shoes Care" width="124" height="145"></div>
            <p>Pure Care<br>Pure Solution</p>
        </div>
        <div>
            <span class="footer-label">EXPLORE</span>
            <a href="{{ route('home') }}" wire:navigate>Beranda</a>
            <a href="{{ route('locations') }}" wire:navigate>{{ \App\Models\Branch::where('active', true)->count() }} cabang</a>
            <a href="{{ route('book') }}" wire:navigate>Buat pesanan</a>
            <a href="{{ route('home') }}#about" @unless(request()->routeIs('home')) wire:navigate @endunless>Tentang PURO</a>
            <a href="{{ route('home') }}#contact" @unless(request()->routeIs('home')) wire:navigate @endunless>Kontak</a>
        </div>
        <div>
            <span class="footer-label">INFORMATION</span>
            <button type="button" @click="$dispatch('puro-track')" aria-haspopup="dialog">Tracking pesanan</button>
            <a href="{{ route('guide') }}" wire:navigate>Panduan pelanggan</a>
            <a href="{{ route('guide.pdf') }}">Unduh panduan PDF ↓</a>
            @if (config('puro.demo')) <span>Senin–Sabtu · 09.00–19.00*</span> @endif
            <div class="puro-footer-social">
                <a href="https://www.instagram.com/puroshoescare/" target="_blank" rel="noopener noreferrer">Instagram ↗</a>
                <a href="https://www.tiktok.com/@puroshoescare" target="_blank" rel="noopener noreferrer">TikTok ↗</a>
            </div>
            <a class="staff-entry" href="{{ auth()->check() ? route('staff.dashboard') : route('login') }}" wire:navigate>Masuk dashboard staf ↗</a>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© {{ now()->year }} PURO SHOES CARE.</span>
        @if (config('puro.demo')) <span>Mode demo · Alamat cabang adalah contoh.</span> @endif
        <span>PURE CARE. PURE SOLUTION.</span>
    </div>
</footer>
