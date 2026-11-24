@if ($paginator->hasPages())
    <nav class="puro-pagination" aria-label="Halaman data">
        <button class="btn btn-ghost btn-sm" type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" @disabled($paginator->onFirstPage())>← Sebelumnya</button>
        <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        <button class="btn btn-ghost btn-sm" type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" @disabled(! $paginator->hasMorePages())>Berikutnya →</button>
    </nav>
@endif
