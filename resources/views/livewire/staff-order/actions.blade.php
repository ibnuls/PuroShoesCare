<div class="action-panel">
    @if (!$user->isRole('admin'))
        <h3>Aksi sesuai role</h3>
    @endif
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
                            <label class="checkbox-line"><input type="checkbox" wire:model="expressAccepted"> One Day
                                tersedia dan disetujui staf (+Rp25.000 per barang).</label>
                        @endif
                        <button class="btn btn-dark btn-wide" type="submit" wire:loading.attr="disabled">
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
                    @include('livewire.staff-order.review')
                @elseif ($order->status === 'PAYMENT_REJECTED')
                    <h3>Menunggu perbaikan bukti</h3><p class="muted">Customer dapat melihat alasan penolakan dan mengirim ulang bukti melalui Cek status.</p>
                @endif
                @if ($order->status === 'WAITING_HANDOVER' && $user->allows('receipts.manage'))
                    <h3>Terima barang di cabang</h3><label>Foto penerimaan<input class="field" type="file"
                            wire:model="photo" accept="image/jpeg,image/png,image/webp"></label><button
                        class="btn btn-dark" wire:click="receive" wire:loading.attr="disabled">Barang sudah
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
                        </label><button class="btn btn-dark" wire:click="assignCourier">Simpan penugasan</button>
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
                    </label><button class="btn btn-dark" wire:click="assignTech">Simpan penugasan</button>
                @endif
                @if ($order->status === 'READY_COLLECTION' && $user->allows('receipts.manage'))
                    <p>Pastikan customer telah mengambil barang di cabang.</p><button class="btn btn-dark"
                        wire:click="collect" wire:confirm="Barang sudah diambil customer?">Tandai sudah diambil</button>
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
                    <button class="btn btn-dark"
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
                <div class="inline-actions"><button class="btn btn-dark" wire:click="qc({{ $t->id }},true)"
                        wire:confirm="Luluskan hasil treatment ini?">Lulus QC</button><button class="btn btn-ghost"
                        wire:click="qc({{ $t->id }},false)">Minta rework</button></div>
            @elseif($user->isRole('courier') && $user->allows('courier.work'))
                @forelse($order->tasks->where('courier_id',$user->id)->whereIn('status',['ASSIGNED','IN_PROGRESS']) as $task)
                    <h3>{{ $task->task_type === 'PICKUP' ? 'Penjemputan' : 'Pengantaran' }}</h3>
                    @if ($task->status === 'ASSIGNED')
                        <button class="btn btn-dark" wire:click="courier({{ $task->id }},false)">Mulai tugas ↗</button>
                    @else
                        <label>Foto bukti serah terima<input class="field" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp"></label>
                        @if ($task->task_type === 'RETURN')
                            <label>Lokasi barang dititipkan (opsional)
                                <textarea class="textarea" wire:model="deliveryNote" maxlength="250" placeholder="Contoh: di rak sebelah pintu depan"></textarea>
                            </label>
                            <p class="small muted">Customer bisa melihat foto dan catatan ini setelah pesanan selesai. Foto barang dan titik titipnya saja.</p>
                        @endif
                        <button
                            class="btn btn-dark" wire:click="courier({{ $task->id }},true)"
                            wire:loading.attr="disabled" wire:target="photo,courier">Selesaikan dengan bukti ↗</button>
                    @endif @empty<div class="notice">Tidak ada tugas aktif.</div>
                @endforelse
            @else<div class="notice">Tidak ada tindakan operasional untuk peranmu pada tahap ini.</div>
            @endif
</div>
