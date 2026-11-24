@if ($order->status === 'NEEDS_INSPECTION' && $order->latestInspection)
                <section class="v3610-price-offer"><span class="eyebrow">PERSETUJUAN PEMERIKSAAN</span>
                    <h3>Perlu dilihat langsung di cabang</h3>
                    <p>{{ $order->latestInspection->reason }}</p>
                    <p>Estimasi biaya jemput satu kali:
                        <strong>{{ \App\Support\Puro::money(app(\App\Services\Pricing::class)->pickup($order->fulfilment_mode, $order->distance_km, $order->shippingPolicy)) }}</strong>.
                        Pengembalian gratis. Jika treatment ditolak setelah barang dijemput, biaya jemput yang disetujui
                        tetap perlu diselesaikan.
                    </p>
                    <div class="v3610-quote-actions"><button class="btn btn-lime"
                            wire:click="inspection({{ $order->latestInspection->id }},true)"
                            wire:loading.attr="disabled">Setujui pemeriksaan</button><button class="btn btn-ghost"
                            wire:click="inspection({{ $order->latestInspection->id }},false)"
                            wire:confirm="Tidak menyetujui pemeriksaan akan membatalkan booking. Lanjutkan?">Tidak
                            setuju</button></div>
                </section>
            @endif
