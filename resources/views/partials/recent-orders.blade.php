<section class="recent-orders" x-data="puroRecentOrders" @puro-recent-orders-updated.window="refresh()" x-show="rows.length" x-cloak>
    <h3>Pesanan terakhir</h3>
    <p class="help-text">Buka kembali pesanan yang dibuat di browser ini, atau gunakan kode pesanan yang sudah disimpan.</p>
    <template x-for="row in rows" :key="row.code">
        <article class="recent-order">
            <div><strong x-text="row.label || row.code"></strong><small x-text="row.label ? row.code : 'Kode pesanan'"></small></div>
            <a class="btn btn-ghost btn-sm" :href="@js(route('track')) + '/' + encodeURIComponent(row.code)" wire:navigate>Buka status ↗</a>
        </article>
    </template>
    <button type="button" class="btn btn-ghost btn-sm" @click="forget()">Hapus daftar di perangkat ini</button>
</section>
