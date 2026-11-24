@use('App\Support\Puro')
<dialog class="modal-box puro-native-modal order-detail-dialog" wire:ignore.self
    x-data x-init="$nextTick(() => { if (!$el.open) $el.showModal() })"
    @cancel.prevent="$refs.close.click()" @click.self="$refs.close.click()" aria-labelledby="staff-order-code">
    @php
        $q = $order->currentQuote;
        $s = $q?->latestSubmission;
        $desk = $user->isRole('admin') || $user->isRole('superadmin');
        $back = route('staff.dashboard', array_filter($backFilters, fn ($value) => $value !== ''));
        $wa = $desk ? Puro::whatsapp($order->customer_phone, "Halo, ini admin PURO.\nKode pesanan: " . $order->access_code . "\nLayanan: " . $order->tariff->display_name . "\nStatus: " . Puro::STATES[$order->status] . "\n" . route('track', $order->access_code)) : null;
    @endphp
    <div class="puro-dialog-content" wire:poll.8s.visible>
        <div class="modal-head">
            <div><span class="eyebrow">{{ $order->branch->name }} / ORDER DETAIL</span><h2 id="staff-order-code">{{ $order->access_code }}</h2></div>
            <a class="icon-close" href="{{ $back }}" x-ref="close" wire:navigate aria-label="Tutup">×</a>
        </div>
        <div class="order-detail-status"><span class="pill {{ Puro::color($order->status) }}">{{ Puro::STATES[$order->status] }}</span></div>
        <div class="detail-grid">
            @foreach ([
                'Nama' => $order->customer_name,
                'WhatsApp' => $order->customer_phone,
                'Jenis barang' => $order->items->first()?->item_description,
                'Treatment' => $order->tariff->display_name . ' × ' . $order->items->count(),
                'Metode' => Puro::MODES[$order->fulfilment_mode],
                'Alamat' => $order->customer_address,
                'Jarak' => $order->distance_km === null ? null : number_format($order->distance_km, 2, ',', '.') . ' km',
                'Total' => Puro::money($order->total()),
                'Status bayar' => $order->verifiedPayment ? 'Terverifikasi admin' : 'Belum diverifikasi',
                'Teknisi' => $order->latestTreatment?->technician?->username,
                'Dibuat' => Puro::date($order->created_at),
                'Catatan' => $order->notes,
            ] as $label => $value)
                <div class="detail-item"><span>{{ $label }}</span><strong>{{ $value ?: '—' }}</strong></div>
            @endforeach
        </div>
        @include('partials.feedback')
        @if ($wa)
            <div class="payment-message-actions"><a class="btn btn-whatsapp btn-sm" href="{{ $wa }}" target="_blank" rel="noopener noreferrer">WhatsApp ↗</a><small class="help-text">Pesan berisi detail transaksi dan status saat ini.</small></div>
        @endif
        @if ($s && $user->isRole('admin') && $user->allows('payments.review'))
            <section class="payment-proof">
                <span class="eyebrow">VERIFIKASI MANUAL / QRIS</span><h3>Bukti pembayaran customer</h3>
                <div class="detail-grid">
                    <div class="detail-item"><span>Nomor referensi</span><strong>{{ $s->reference->canonical_reference }}</strong></div>
                    <div class="detail-item"><span>Total yang dikonfirmasi</span><strong>{{ Puro::money($s->claimed_amount) }}</strong></div>
                    <div class="detail-item"><span>Diunggah</span><strong>{{ Puro::date($s->submitted_at) }}</strong></div>
                </div>
                <a class="payment-proof-image" href="{{ $s->proof->url }}" target="_blank" rel="noopener" aria-label="Perbesar bukti pembayaran"><img src="{{ $s->proof->url }}" alt="Bukti pembayaran terbaru"></a>
            </section>
        @endif
        @if ($order->items->contains(fn ($item) => $item->photos->isNotEmpty() || $item->condition_notes))
            <section class="order-conditions">
                <h3>Foto & kondisi saat booking</h3>
                @foreach ($order->items as $item)
                    <div class="v3610-condition-card"><strong>Barang {{ $item->item_no }}</strong><p class="help-text">{{ $item->item_description }} · {{ $item->condition_notes ?: 'Tidak ada catatan' }}</p>
                        <div class="v3610-condition-photos">
                            @foreach ($item->photos as $photo)
                                <a href="{{ $photo->media->url }}" target="_blank" rel="noopener"><img src="{{ $photo->media->url }}" alt="Kondisi barang {{ $item->item_no }}" loading="lazy"></a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
        @if ($q && $q->kind !== 'FIXED')
            <section class="v3610-price-offer"><span class="v3610-current-quote">Penawaran v{{ $q->version }}</span><h3>Rincian harga</h3>
                @foreach ($q->lines as $line)
                    <div class="v3610-offer-line"><span>Barang {{ $loop->iteration }}</span><strong>{{ Puro::money($line->unit_price + $line->express_fee) }}</strong></div>
                @endforeach
                <div class="v3610-offer-line total"><span>Total</span><strong>{{ Puro::money($order->total()) }}</strong></div>
                <p class="v3610-estimate-hint">{{ $q->assessment_note }}</p>
            </section>
        @endif
        @if ($order->cancellation)
            <div class="notice error"><strong>Dibatalkan:</strong> {{ $order->cancellation->reason }}
                @if ($order->cancellation->return_required)<p>Pengembalian barang perlu ditangani admin secara langsung.</p>@endif
            </div>
        @endif
        @include('livewire.staff-order.actions')
        @if ($user->isRole('admin') && !$order->isClosed() && !$order->verifiedPayment && $user->allows('orders.cancel'))
            <details class="order-cancellation">
                <summary>Batalkan pesanan</summary>
                <h3>Pembatalan pesanan</h3>
                <p class="help-text">Aksi ini menghentikan pesanan. Untuk memperbaiki bukti, gunakan Tolak bukti pembayaran.</p>
                <div class="field-wrap"><label for="cancel-order-reason">Alasan pembatalan *</label><textarea class="textarea" id="cancel-order-reason" wire:model="note" maxlength="1000" placeholder="Jelaskan mengapa pesanan dihentikan"></textarea></div>
                <button class="btn btn-reject" type="button" wire:click="cancel" wire:confirm="Batalkan seluruh pesanan?">Batalkan pesanan ini</button>
            </details>
        @endif
        @include('livewire.staff-order.history')
        <div class="timeline staff-detail-history">
            @foreach ($order->history->reverse() as $event)
                <p><strong>{{ Puro::STATES[$event->to_status] }}</strong> · {{ Puro::date($event->created_at) }}<span class="muted"> — {{ $event->note }}</span></p>
            @endforeach
        </div>
    </div>
</dialog>
