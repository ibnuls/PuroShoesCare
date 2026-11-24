<dialog class="modal-box tracking-dialog puro-native-modal" x-data="puroDialog" @puro-track.window="open()" @close="restoreFocus()" @click.self="$el.close()" aria-labelledby="tracking-dialog-title">
    <div class="puro-dialog-content">
        <div class="modal-head">
            <div><span class="eyebrow">PURO / TRACKING</span><h2 id="tracking-dialog-title">Status pesanan.</h2></div>
            <button class="icon-close" type="button" aria-label="Tutup" @click="$el.close()">×</button>
        </div>
        <p class="muted tracking-dialog-copy">Masukkan kode pesanan yang tersedia sejak booking dibuat, atau buka Pesanan terakhir.</p>
        <form method="GET" action="{{ route('track') }}" class="track-lookup">
            <input class="field" name="code" aria-label="Kode pesanan" placeholder="Masukkan kode pesanan" maxlength="40" required autofocus>
            <button class="btn btn-dark" type="submit">Cek ↗</button>
        </form>
        @include('partials.recent-orders')
    </div>
</dialog>
