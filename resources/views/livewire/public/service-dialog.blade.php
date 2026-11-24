<dialog class="puro-service-dialog modal-box v3610-service-modal v3610-poster-modal" x-data="puroServiceDetails"
    @puro-service.window="show($event.detail)" @click.self="$el.close()" @close="restoreFocus()" aria-labelledby="puro-service-title">
    <div class="service-dialog-body">
        <button type="button" class="icon-close" aria-label="Tutup detail layanan" @click="$el.close()" autofocus>×</button>
        <h2 class="sr-only" id="puro-service-title" x-text="service.name"></h2>
        <template x-if="service.poster"><img class="v3610-full-poster" :src="service.poster" :alt="service.name"></template>
        <div class="v3610-poster-empty" x-show="!service.poster">
            <h2 x-text="service.name"></h2><p>Gambar layanan belum tersedia.</p><strong x-text="service.price"></strong>
        </div>
        <div class="v3610-poster-actions"><a class="btn btn-dark btn-wide" :href="service.book" wire:navigate>Buat pesanan ↗</a></div>
    </div>
</dialog>
