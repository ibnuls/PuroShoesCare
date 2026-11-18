<div class="container page-shell tracking-shell" wire:poll.6s.visible>
    <div class="page-intro">
        <div><span class="eyebrow">YOUR ORDER, IN VIEW</span>
            <h1>Track your <em class="accent-word">care.</em></h1>
        </div>
        <p>Kode tersedia sejak booking dan tetap sama sampai pesanan selesai.</p>
    </div>@include('partials.feedback')<form wire:submit="lookup" class="track-search"><label class="sr-only"
            for="tracking-code">Kode pesanan</label><input id="tracking-code" class="field" wire:model="search"
            placeholder="Masukkan kode PURO-…" maxlength="40" required><button type="submit" class="btn btn-dark">Lacak
            ↗</button></form>
    <section class="recent-orders" x-data="{ rows: [], init() { try { this.rows = JSON.parse(localStorage.getItem('puro-recent-orders') || '[]').filter(r => /^PURO-[A-F0-9]{16}$/.test(r.code) && Date.now() - r.at < 2592000000).slice(0, 10) } catch {} } }">
        <details>
            <summary>Pesanan terakhir di perangkat ini</summary>
            <p class="small muted">Tersimpan selama 30 hari pada browser ini. Simpan kode atau tautan agar bisa membuka
                dari perangkat lain.</p><template x-for="r in rows" :key="r.code"><a class="recent-order"
                    :href="'/track/' + encodeURIComponent(r.code)" wire:navigate x-text="r.code"></a></template>
            <p x-show="!rows.length" class="small muted">Belum ada kode tersimpan di browser ini.</p><button
                x-show="rows.length" type="button" class="btn btn-ghost btn-sm"
                @click="localStorage.removeItem('puro-recent-orders');rows=[]">Hapus daftar di perangkat ini</button>
        </details>
    </section>
    @if ($code && !$order)
        <div class="notice warn">Kode pesanan tidak ditemukan. Periksa kembali kode yang disimpan saat booking.</div>
    @endif
    @if ($order)
        <article class="panel panel-pad track-result" x-data="{
            copied: false,
            init() {
                try {
                    let rows = JSON.parse(localStorage.getItem('puro-recent-orders') || '[]');
                    rows = rows.filter(r => r.code !== @js($order->access_code));
                    rows.unshift({ code: @js($order->access_code), at: Date.now() });
                    localStorage.setItem('puro-recent-orders', JSON.stringify(rows.slice(0, 10)))
                } catch {}
            }
        }">
            <div class="status-banner {{ \App\Support\Puro::color($order->status) }}"><span
                    class="eyebrow">{{ \App\Support\Puro::STATES[$order->status] }}</span>
                <h2>
                    @if ($order->status === 'PENDING_PAYMENT')
                        Bukti sedang diperiksa admin
                    @elseif($order->status === 'PAYMENT_REJECTED')
                        Perbaiki bukti, pesanan tetap aktif
                    @elseif($order->status === 'WAITING_APPROVAL')
                        Penawaran harga siap ditinjau
                    @elseif($order->status === 'CANCELLED')
                        Pesanan dibatalkan
                    @elseif($order->verifiedPayment)
                        Pembayaran sudah diverifikasi
                    @else
                        Booking sudah tercatat
                    @endif
                </h2>
                <p>Kode pesanan bukan tanda pembayaran disetujui. Persetujuan harga dan verifikasi pembayaran adalah dua
                    tahap berbeda.</p>
            </div>
            <div class="order-heading">
                <div><span class="eyebrow">KODE PESANAN / TRACKING</span>
                    <div class="tracking-token">{{ $order->access_code }}</div>
                    <div class="inline-actions"><button type="button" class="btn btn-ghost btn-sm"
                            @click="navigator.clipboard?.writeText(@js($order->access_code)).then(()=>copied=true)"><span
                                x-text="copied?'Tersalin ✓':'Salin kode ⧉'"></span></button><a
                            href="{{ route('track', $order->access_code) }}" class="inline-link">Tautan status ↗</a>
                    </div>
                </div><span
                    class="pill {{ \App\Support\Puro::color($order->status) }}">{{ \App\Support\Puro::STATES[$order->status] }}</span>
            </div>
            <h2>{{ $order->tariff->display_name }} · {{ $order->branch->name }}</h2>
            <p class="muted">{{ $order->items()->count() }} barang ·
                {{ \App\Support\Puro::MODES[$order->fulfilment_mode] }} · Diperbarui
                {{ \App\Support\Puro::date($order->updated_at) }}</p>
            @if ($order->cancellation)
                <div class="notice error"><strong>Alasan pembatalan:</strong> {{ $order->cancellation->reason }}
                    @if ($order->cancellation->return_required)
                        <p>Barang telah diterima cabang. Hubungi admin untuk pengembaliannya.</p>
                    @endif
                    <p>
                        Pembatalan tidak otomatis mengembalikan dana.</p>
                </div>
            @endif
            @if ($order->status === 'NEEDS_INSPECTION' && $order->latestInspection)
                <section class="quote-box"><span class="eyebrow">PERSETUJUAN PEMERIKSAAN</span>
                    <h3>Perlu dilihat langsung di cabang</h3>
                    <p>{{ $order->latestInspection->reason }}</p>
                    <p>Estimasi biaya jemput satu kali:
                        <strong>{{ \App\Support\Puro::money(app(\App\Services\Pricing::class)->pickup($order->fulfilment_mode, $order->distance_km, $order->shippingPolicy)) }}</strong>.
                        Pengembalian gratis. Jika treatment ditolak setelah barang dijemput, biaya jemput yang disetujui
                        tetap perlu diselesaikan.
                    </p>
                    <div class="inline-actions"><button class="btn btn-lime"
                            wire:click="inspection({{ $order->latestInspection->id }},true)"
                            wire:loading.attr="disabled">Setujui pemeriksaan</button><button class="btn btn-ghost"
                            wire:click="inspection({{ $order->latestInspection->id }},false)"
                            wire:confirm="Tidak menyetujui pemeriksaan akan membatalkan booking. Lanjutkan?">Tidak
                            setuju</button></div>
                </section>
            @endif
            @php($q = $order->currentQuote)
            @if ($q)
                <section class="quote-box"><span
                        class="eyebrow">{{ $q->kind === 'LOGISTICS' ? 'BIAYA JEMPUT SAJA' : 'PENAWARAN HARGA' }} /
                        V{{ $q->version }} · {{ $q->decision }}</span>
                    <h3>Rincian tagihan</h3>
                    @foreach ($q->lines as $line)
                        <div class="summary-row"><span>Barang {{ $loop->iteration }} @if ($line->express_fee)
                                    > One Day
                                @endif
                            </span>
                            <strong>{{ \App\Support\Puro::money($line->unit_price + $line->express_fee) }}</strong>
                        </div>
                    @endforeach @php($totals = $q->totals())<div class="summary-row"><span>Subtotal
                            treatment</span><strong>{{ \App\Support\Puro::money($totals['subtotal']) }}</strong></div>
                    <div class="summary-row"><span>Potongan
                            promo</span><strong>−{{ \App\Support\Puro::money($totals['discount']) }}</strong></div>
                    <div class="summary-row"><span>Jemput (satu
                            kali)</span><strong>{{ \App\Support\Puro::money($totals['pickup_fee']) }}</strong></div>
                    <div class="summary-row summary-total">
                        <span>Total</span><strong>{{ \App\Support\Puro::money($totals['total']) }}</strong>
                    </div>
                    <p>{{ $q->assessment_note }}</p>
                    @if ($order->status === 'WAITING_APPROVAL' && $q->decision === 'PENDING')
                        <div class="inline-actions"><button type="button" class="btn btn-lime"
                                wire:click="decide({{ $q->id }},true)"
                                wire:confirm="Setujui rincian harga dan lanjutkan ke pembayaran?"
                                wire:loading.attr="disabled">Setujui harga & lanjut bayar ↗</button></div>
                        <details class="decision-details">
                            <summary>Belum sesuai / tolak penawaran</summary><label>Alasan penolakan
                                <textarea class="textarea" wire:model="reason" maxlength="1000"></textarea>
                            </label><button class="btn btn-ghost" wire:click="decide({{ $q->id }},false)"
                                wire:confirm="Tolak penawaran ini?">Tolak penawaran</button>
                        </details>
                    @endif
                    @if ($order->status === 'QUOTE_REJECTED')
                        <div class="notice warn">Penawaran ditolak. Kamu dapat meminta penilaian ulang atau menghubungi
                            admin.</div><button class="btn btn-ghost" wire:click="reassess">Minta penilaian
                            ulang</button>
                    @endif
                </section>
            @endif
            @if (in_array($order->status, ['WAITING_PAYMENT', 'PAYMENT_REJECTED']) && $q?->decision === 'ACCEPTED')
                <section class="payment-section">
                    <h3>{{ $order->status === 'PAYMENT_REJECTED' ? 'Kirim ulang bukti pembayaran' : 'Lanjutkan pembayaran' }}
                    </h3>
                    @if ($order->status === 'PAYMENT_REJECTED')
                        <div class="notice error"><strong>Alasan admin:</strong>
                            {{ $q->latestSubmission?->review_reason }}<p>Jangan transfer ulang sebelum memastikan
                                transaksi sebelumnya dengan admin. Referensi yang sama boleh dipakai untuk memperbaiki
                                bukti pada pesanan ini.</p>
                        </div>
                    @endif
                    <div class="pay-layout">
                        <div>@php($qr = $order->qrisVersion ?? $order->branch->qrisAccount->latestVersion)@if ($qr)
                                <img class="payment-qr" src="{{ $qr->media->url }}"
                                alt="QRIS {{ $order->branch->name }}">@else<div class="notice warn">QRIS belum
                                    dipasang. Hubungi admin sebelum membayar.</div>
                                @endif @if (config('puro.demo'))
                                    <div class="notice warn">Mode demo — jangan transfer uang sungguhan ke QRIS demo.
                                    </div>
                                @endif
                                <p class="small muted">
                                    Nominal harus sesuai tagihan. Pembayaran diperiksa manual oleh admin.</p>
                        </div>
                        <form wire:submit="pay({{ $q->id }},{{ $qr?->id ?? 0 }})"><label>Nomor referensi
                                transaksi<input class="field" wire:model="reference" required maxlength="80"
                                    placeholder="Nomor pada bukti pembayaran"></label><label>Nominal pembayaran
                                (rupiah)<input class="field" type="number" min="1" step="1"
                                    wire:model="amount" required></label><label>Bukti pembayaran<input class="field"
                                    type="file" wire:model="proof" accept="image/jpeg,image/png,image/webp"
                                    required></label>
                            <p class="small muted">JPG, PNG atau WebP; maksimal 5 MB. Referensi harus terbaca.</p>
                            <label class="check-label"><input type="checkbox" wire:model="confirmed" required> Saya
                                sudah membayar sesuai tagihan dan memahami bahwa bukti masih perlu diverifikasi
                                admin.</label><button type="submit" class="btn btn-lime btn-wide"
                                wire:loading.attr="disabled"
                                wire:target="pay,proof">{{ $order->status === 'PAYMENT_REJECTED' ? 'Kirim ulang bukti' : 'Kirim bukti pembayaran' }}
                                ↗</button>
                        </form>
                    </div>
                </section>
            @endif
            <section class="progress-section">
                <h3>Perkembangan pesanan</h3>
                <ol class="order-timeline">
                    @foreach ($order->history as $event)
                        <li><span class="timeline-dot">{{ $loop->last ? '●' : '✓' }}</span>
                            <div><strong>{{ \App\Support\Puro::STATES[$event->to_status] }}</strong>
                                <p>{{ $event->note }}</p>
                                <small>{{ \App\Support\Puro::date($event->created_at) }}</small>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
            <div class="notice small">Simpan kode atau tautan ini. Halaman tracking menampilkan layanan dan progres
                tanpa nama, nomor HP, alamat, atau foto pribadi.</div>
        </article>
    @endif
</div>
