<div>
    @if ($tab === 'qris')
        <div class="dashboard-intro"><div><span class="eyebrow">SUPER ADMIN / PAYMENT SETTINGS</span><h1>Grup QRIS</h1><p>Atur gambar QRIS yang dipakai cabang.</p></div></div>
        @include('partials.feedback')
    @endif
    @include('livewire.management.' . $tab)
</div>
