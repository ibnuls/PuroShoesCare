<div class="container page-shell" wire:poll.8s.visible>
    @include('partials.staff-nav')<a href="{{ route('staff.dashboard') }}" wire:navigate class="inline-link">← Kembali ke
        pesanan</a>
    <div class="page-intro">
        <div><span class="eyebrow">{{ $order->access_code }}</span>
            <h1>{{ $order->tariff->display_name }}</h1>
        </div><span
            class="pill {{ \App\Support\Puro::color($order->status) }}">{{ \App\Support\Puro::STATES[$order->status] }}</span>
    </div>@include('partials.feedback')
    <div class="staff-detail-layout">
        <section class="panel panel-pad">
            <h2>Detail pesanan</h2>
            <dl class="detail-list">
                <dt>Customer</dt>
                <dd>{{ $order->customer_name }}</dd>
                <dt>WhatsApp</dt>
                <dd>{{ $order->customer_phone }}</dd>
                <dt>Alamat</dt>
                <dd>{{ $order->customer_address ?? 'Antar sendiri' }}</dd>
                <dt>Cabang</dt>
                <dd>{{ $order->branch->name }}</dd>
                <dt>Metode</dt>
                <dd>{{ \App\Support\Puro::MODES[$order->fulfilment_mode] }}</dd>
                <dt>Jarak</dt>
                <dd>{{ $order->distance_km ?? '−' }} km</dd>
                <dt>Catatan</dt>
                <dd>{{ $order->notes ?? '−' }}</dd>
                <dt>One Day</dt>
                <dd>{{ $order->express_requested ? 'Diminta, perlu konfirmasi staf' : 'Tidak' }}</dd>
                <dt>Barang diterima</dt>
                <dd>{{ $order->receipt ? \App\Support\Puro::date($order->receipt->received_at) : 'Belum' }}</dd>
                <dt>Pembayaran</dt>
                <dd>{{ $order->verifiedPayment ? 'Terverifikasi' : 'Belum diverifikasi' }}</dd>
            </dl>
            @foreach ($order->items as $item)
                <div class="item-detail">
                    <h3>Barang {{ $item->item_no }} · {{ $item->item_description }}</h3>
                    <p>{{ $item->condition_notes }}</p>
                    <div class="photo-strip">
                        @foreach ($item->photos as $p)
                            <a href="{{ $p->media->url }}" target="_blank" rel="noopener"><img
                                    src="{{ $p->media->url }}" alt="Kondisi barang {{ $item->item_no }}"
                                    loading="lazy"></a>
                        @endforeach
                    </div>
                </div>
            @endforeach
            @if ($order->cancellation)
                <div class="notice error"><strong>Dibatalkan:</strong> {{ $order->cancellation->reason }}@if ($order->cancellation->return_required)
                        <p>Pengembalian barang perlu ditangani admin secara langsung.</p>
                    @endif
                </div>
            @endif
            @php($q = $order->currentQuote)@if ($q)
                <div class="quote-box"><span class="eyebrow">TAGIHAN V{{ $q->version }} / {{ $q->kind }} /
                        {{ $q->decision }}</span>
                    <h3>{{ \App\Support\Puro::money($q->totals()['total']) }}</h3>
                    <p>{{ $q->assessment_note }}</p>
                    @foreach ($q->lines as $line)
                        <div class="summary-row"><span>Barang
                                {{ $loop->iteration }}</span><strong>{{ \App\Support\Puro::money($line->unit_price + $line->express_fee) }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif
            <h3 class="form-subhead">Riwayat proses</h3>
            <ol class="order-timeline">
                @foreach ($order->history as $event)
                    <li><span class="timeline-dot">✓</span>
                        <div><strong>{{ \App\Support\Puro::STATES[$event->to_status] }}</strong>
                            <p>{{ $event->note }}</p><small>{{ \App\Support\Puro::date($event->created_at) }}</small>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
        <aside class="panel panel-pad actions-panel"><span class="eyebrow">NEXT ACTION</span>
            <h2>Kelola pekerjaan</h2>
            @if ($user->isRole('admin'))
                @if (in_array($order->status, ['WAITING_ASSESSMENT', 'WAITING_APPROVAL', 'QUOTE_REJECTED']) &&
                        $user->allows('orders.manage'))
                    <form wire:submit="quote({{ $q?->version ?? 0 }})">
                        <h3>Terbitkan penawaran</h3>
                        <p class="small muted">Harga harus sesuai rentang tarif v{{ $order->tariff->version }} saat
                            booking: {{ \App\Support\Puro::money($order->tariff->min_price) }} –
                            {{ \App\Support\Puro::money($order->tariff->max_price) }}.</p>
                        @foreach ($order->items as $item)
                            <label>Harga barang {{ $item->item_no }} (rupiah)<input class="field" type="number"
                                    wire:model="prices.{{ $item->id }}" min="1" step="1"
                                    required></label>
                        @endforeach
                        <label>
                            Hasil penilaian (dibaca customer)
                            <textarea class="textarea" wire:model="note" required minlength="3" maxlength="1000"></textarea>
                        </label>
                        @if ($order->express_requested)
                            <label class="check-label"><input type="checkbox" wire:model="expressAccepted"> One Day
                                tersedia dan disetujui staf (+Rp25.000 per barang).</label>
                        @endif
                        <button class="btn btn-lime btn-wide" type="submit" wire:loading.attr="disabled">
                            Kirim penawaran v{{ ($q?->version ?? 0) + 1 }} ↗</button>
                    </form>
                @endif
                @if ($order->status === 'WAITING_ASSESSMENT' && !$order->receipt && $user->allows('inspections.manage'))
                    <details class="decision-details">
                        <summary>Minta pemeriksaan langsung</summary><label>Alasan pemeriksaan
                            <textarea class="textarea" wire:model="note" maxlength="1000"></textarea>
                        </label><button class="btn btn-ghost" wire:click="requestInspection">Minta persetujuan
                            customer</button>
                    </details>
                @endif
                @if ($order->status === 'PENDING_PAYMENT' && $q?->latestSubmission && $user->allows('payments.review'))
                    @php($s = $q->latestSubmission)<section>
                        <h3>Periksa bukti terbaru</h3>
                        <p>Upaya #{{ $s->attempt_no }} · {{ \App\Support\Puro::money($s->claimed_amount) }}</p>
                        <p>Referensi: <strong>{{ $s->reference->canonical_reference }}</strong></p><a
                            href="{{ $s->proof->url }}" target="_blank" rel="noopener"><img class="evidence-preview"
                                src="{{ $s->proof->url }}" alt="Bukti pembayaran terbaru"></a><label>Ketik ulang nomor
                            referensi<input class="field" wire:model="typedReference" maxlength="80"
                                autocomplete="off"></label><label class="check-label"><input type="checkbox"
                                wire:model="bankChecked"> Nominal dan referensi sudah cocok dengan mutasi
                            rekening/merchant.</label><button type="button" class="btn btn-lime btn-wide"
                            wire:click="review({{ $s->id }},true)"
                            wire:confirm="Verifikasi pembayaran ini? Pastikan mutasi benar."
                            wire:loading.attr="disabled">Setujui pembayaran</button><label>Alasan jika bukti ditolak
                            <textarea class="textarea" wire:model="note" maxlength="1000"></textarea>
                        </label><button type="button" class="btn btn-ghost btn-wide"
                            wire:click="review({{ $s->id }},false)"
                            wire:confirm="Tolak bukti? Pesanan tetap aktif dan customer dapat mengirim ulang.">Tolak
                            bukti · izinkan kirim ulang</button>
                    </section>
                @endif
                @if ($order->status === 'WAITING_HANDOVER' && $user->allows('receipts.manage'))
                    <h3>Terima barang di cabang</h3><label>Foto penerimaan<input class="field" type="file"
                            wire:model="photo" accept="image/jpeg,image/png,image/webp"></label><button
                        class="btn btn-lime" wire:click="receive" wire:loading.attr="disabled">Barang sudah
                        diterima</button>
                @endif
                @if (in_array($order->status, ['WAITING_PICKUP', 'READY_DELIVERY']) && $user->allows('couriers.assign'))@php($type = $order->status === 'WAITING_PICKUP' ? 'PICKUP' : 'RETURN')
                    @if (!$order->tasks->contains('task_type', $type))
                        <h3>Tugaskan kurir</h3><label>Kurir cabang<select class="field" wire:model="assignee">
                                <option value="">Pilih kurir</option>
                                @foreach ($staff->filter(fn($s) => $s->isRole('courier')) as $s)
                                    <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                                @endforeach
                            </select>
                        </label><button class="btn btn-lime" wire:click="assignCourier">Simpan penugasan</button>
                    @else
                        <div class="notice">Kurir sudah ditugaskan. Menunggu penyelesaian tugas.</div>
                    @endif
                @endif
                @if ($order->status === 'WAITING_TECH' && !$order->latestTreatment && $user->allows('technicians.assign'))
                    <h3>Tugaskan teknisi</h3><label>Teknisi cabang<select class="field" wire:model="assignee">
                            <option value="">Pilih teknisi</option>
                            @foreach ($staff->filter(fn($s) => $s->isRole('tech')) as $s)
                                <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                            @endforeach
                        </select>
                    </label><button class="btn btn-lime" wire:click="assignTech">Simpan penugasan</button>
                @endif
                @if ($order->status === 'READY_COLLECTION' && $user->allows('receipts.manage'))
                    <p>Pastikan customer telah mengambil barang di cabang.</p><button class="btn btn-lime"
                        wire:click="collect" wire:confirm="Barang sudah diambil customer?">Tandai sudah diambil</button>
                @endif
                @if (!$order->isClosed() && !$order->verifiedPayment && $user->allows('orders.cancel'))
                    <details class="decision-details danger-zone">
                        <summary>Batalkan seluruh pesanan</summary>
                        <p class="small">Tindakan ini menutup pesanan dan pengiriman bukti. Pengembalian barang/dana
                            ditangani admin secara terpisah.</p><label>Alasan pembatalan (wajib)
                            <textarea class="textarea" wire:model="note" maxlength="1000"></textarea>
                        </label><button class="btn btn-danger" wire:click="cancel"
                            wire:confirm="Batalkan seluruh pesanan? Tindakan ini berbeda dari penolakan bukti.">Batalkan
                            pesanan</button>
                    </details>
                @endif
            @elseif($user->isRole('tech') && $order->latestTreatment?->technician_id === $user->id && $user->allows('treatment.work'))
                @php($t = $order->latestTreatment)@if (in_array($order->status, ['WAITING_TECH', 'REWORK', 'CLEANING']))
                    <h3>Treatment siklus {{ $t->cycle_no }}</h3><label>Foto
                        {{ $order->status === 'CLEANING' ? 'sesudah' : 'sebelum' }}<input class="field"
                            type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp"></label>
                    @if ($order->status === 'CLEANING')
                        <label>Catatan hasil
                            <textarea class="textarea" wire:model="note" maxlength="1000"></textarea>
                        </label>
                    @endif
                    <button class="btn btn-lime"
                        wire:click="treatment({{ $t->id }},{{ $order->status === 'CLEANING' ? 'true' : 'false' }})"
                        wire:loading.attr="disabled">
                    {{ $order->status === 'CLEANING' ? 'Kirim hasil ke QC' : 'Mulai pengerjaan' }} ↗</button>@else
                    <div class="notice">Belum ada tindakan untuk teknisi di tahap ini.</div>
                @endif
            @elseif($user->isRole('qc') && $order->status === 'WAITING_QC' && $user->allows('qc.review'))
                @php($t = $order->latestTreatment)<h3>QC siklus {{ $t->cycle_no }}</h3><label>Hasil pemeriksaan / instruksi
                    rework
                    <textarea class="textarea" wire:model="note" maxlength="1000"></textarea>
                </label>
                <div class="inline-actions"><button class="btn btn-lime" wire:click="qc({{ $t->id }},true)"
                        wire:confirm="Luluskan hasil treatment ini?">Lulus QC</button><button class="btn btn-ghost"
                        wire:click="qc({{ $t->id }},false)">Minta rework</button></div>
            @elseif($user->isRole('courier') && $user->allows('courier.work'))
                @forelse($order->tasks->where('courier_id',$user->id)->whereIn('status',['ASSIGNED','IN_PROGRESS']) as $task)
                    <h3>{{ $task->task_type === 'PICKUP' ? 'Penjemputan' : 'Pengantaran' }}</h3>
                    @if ($task->status === 'ASSIGNED')
                        <button class="btn btn-lime" wire:click="courier({{ $task->id }},false)">Mulai tugas
                        ↗</button>@else<label>Foto bukti serah terima<input class="field" type="file"
                                wire:model="photo" accept="image/jpeg,image/png,image/webp"></label><button
                            class="btn btn-lime" wire:click="courier({{ $task->id }},true)"
                            wire:loading.attr="disabled">Selesaikan dengan bukti ↗</button>
                    @endif @empty<div class="notice">Tidak ada tugas aktif.</div>
                @endforelse
            @else<div class="notice">Tidak ada tindakan operasional untuk peranmu pada tahap ini.</div>
            @endif
        </aside>
    </div>
    @if ($order->treatments->isNotEmpty())
        <section class="panel panel-pad history-panel">
            <h2>Dokumentasi treatment & QC</h2>
            @foreach ($order->treatments as $t)
                <article class="cycle-card">
                    <h3>Siklus {{ $t->cycle_no }} · {{ $t->status }}</h3>
                    <p>{{ $t->notes }}</p>
                    <div class="photo-strip">
                        @foreach ($t->photos as $p)
                            <figure><a href="{{ $p->media->url }}" target="_blank" rel="noopener"><img
                                        src="{{ $p->media->url }}"
                                        alt="{{ $p->phase }} siklus {{ $t->cycle_no }}" loading="lazy"></a>
                                <figcaption>{{ $p->phase }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                    @if ($t->qc)
                        <div class="notice {{ $t->qc->result === 'PASSED' ? 'success' : 'warn' }}">QC:
                            {{ $t->qc->result }}
                            · {{ $t->qc->notes }}</div>
                    @endif
                </article>
            @endforeach
        </section>
    @endif
    @if ($user->isRole('admin') && $user->allows('payments.review') && $order->quotes->isNotEmpty())
        <section class="panel panel-pad history-panel">
            <h2>Riwayat bukti pembayaran</h2>
            @foreach ($order->quotes as $quote)
                @foreach ($quote->submissions as $s)
                    <article class="submission-row"><strong>Tagihan v{{ $quote->version }} / Upaya
                            #{{ $s->attempt_no }}</strong><span class="pill neutral">{{ $s->decision }}</span>
                        <p>{{ $s->reference->canonical_reference }} ·
                            {{ \App\Support\Puro::money($s->claimed_amount) }} ·
                            {{ \App\Support\Puro::date($s->submitted_at) }}</p>
                        <p>{{ $s->review_reason }}</p><a href="{{ $s->proof->url }}" target="_blank" rel="noopener"
                            class="inline-link">Buka bukti tersimpan ↗</a>
                    </article>
                @endforeach
            @endforeach
        </section>
    @endif
</div>
