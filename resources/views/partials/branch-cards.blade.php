<div class="branch-grid">
    @foreach ($branches->take($limit) as $branch)
        <article class="location-card"><img class="branch-photo"
                src="{{ $branch->cover?->url ?? asset('assets/branch-' . (($loop->index % 4) + 1) . '.jpg') }}"
                alt="{{ $branch->name }}" loading="lazy">
            <div class="panel-pad"><span class="eyebrow">{{ $branch->area }}</span>
                <h3>{{ $branch->name }}</h3>
                <p>{{ $branch->address }}</p>
                <div class="inline-actions"><a href="{{ route('book') }}" wire:navigate class="btn btn-dark">Pesan di PURO
                        ↗</a>
                    @if ($branch->latitude !== null && $branch->longitude !== null)
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $branch->latitude }},{{ $branch->longitude }}"
                            target="_blank" rel="noopener noreferrer" class="btn btn-ghost">Peta ↗</a>
                    @endif
                </div>
            </div>
        </article>
    @endforeach
</div>
