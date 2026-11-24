<section class="container promo-v35-section" id="promo">
    <div class="puro-v35-sectionhead">
        <div><span class="puro-v35-mini">02 / SPECIAL OFFERS</span><h2>Promo Spesial<span class="puro-v35-dot">.</span></h2><p>Promo pusat berlaku di semua cabang. Promo cabang hanya berlaku di outlet yang tertera.</p></div>
        <a href="{{ route('book') }}" wire:navigate class="puro-v35-textlink">Pesan sekarang <span>↗</span></a>
    </div>
    <div class="promo-v35-columns">
        @foreach (['Global' => $promotions->whereNull('branch_id'), 'Cabang' => $promotions->whereNotNull('branch_id')] as $scope => $list)
            <div class="promo-v35-group">
                <div class="promo-v35-grouphead"><h3>Promo {{ $scope }}</h3><span class="active-text">{{ $list->count() }} promo aktif</span></div>
                <div class="promo-v35-grid">
                    @forelse ($list as $promo)
                        @php
                            $cover = \App\Support\Puro::promoCover($promo);
                        @endphp
                        <article @class(['promo-v35-card', 'promo-v34-global' => !$promo->branch_id, 'promo-v34-local' => $promo->branch_id, 'promo-v35-' . (($loop->index + ($promo->branch_id ? 2 : 0)) % 4 + 1), 'v3610-promo-no-photo' => !$cover])>
                            @if ($cover)<img class="promo-v35-shoe" src="{{ $cover }}" alt="Foto promo {{ $promo->code }}" loading="lazy">@endif
                            <div class="promo-v35-copy">
                                <span class="promo-v35-tag">{{ $promo->branch ? $promo->branch->name . ' · ' . $promo->branch->area : 'GLOBAL · ALL BRANCHES' }}</span>
                                <span class="promo-v35-code">{{ $promo->code }}</span>
                                <h4>Diskon {{ $promo->latestVersion->percent }}%</h4>
                                <p>Maks. potongan {{ \App\Support\Puro::money($promo->latestVersion->cap_amount) }}</p>
                                <a class="promo-v35-cta" href="{{ route('book', ['promo' => $promo->code, 'branch' => $promo->branch_id]) }}" wire:navigate>Pakai Promo <span>↗</span></a>
                            </div>
                        </article>
                    @empty
                        <div class="promo-v35-empty">Belum ada promo {{ strtolower($scope) }} aktif.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</section>
