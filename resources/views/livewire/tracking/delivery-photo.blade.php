@if ($order->status === 'COMPLETE' && $order->returnDelivery)
                @php($deliveryPhoto = route('track.delivery-proof', $order->access_code))
                <section class="delivery-proof" aria-labelledby="delivery-proof-title">
                    <h3 id="delivery-proof-title">Foto bukti pengantaran</h3>
                    @if ($order->returnDelivery->delivery_note)
                        <p><strong>Catatan kurir</strong> · {{ $order->returnDelivery->delivery_note }}</p>
                    @endif
                    <a href="{{ $deliveryPhoto }}" target="_blank" rel="noopener" aria-label="Perbesar foto bukti pengantaran">
                        <img src="{{ $deliveryPhoto }}" alt="Foto barang saat diserahkan atau dititipkan oleh kurir" loading="lazy">
                    </a>
                </section>
            @endif
