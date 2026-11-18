<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderReceipt;
use App\Models\OrderStatusHistory;
use App\Models\PaymentReference;
use App\Models\PaymentSubmission;
use App\Models\QcCheck;
use App\Models\Service;
use App\Models\ShippingPolicy;
use App\Models\StaffUser;
use App\Models\VerifiedPayment;
use App\Support\Puro;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Every command locks the parent order. UI state is never an authority. */
class Workflow
{
    public function __construct(private OrderAccess $access, private Pricing $pricing, private MediaStorage $media) {}

    private function check(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['workflow' => $message]);
        }
    }

    private function text(string $value, int $min = 3, int $max = 1000): string
    {
        $value = trim($value);
        $this->check(mb_strlen($value) >= $min && mb_strlen($value) <= $max, "Isi keterangan {$min}–{$max} karakter.");

        return $value;
    }

    private function locked(int $id): Order
    {
        return Order::lockForUpdate()->findOrFail($id);
    }

    private function open(Order $o): void
    {
        $this->check(! $o->isClosed(), 'Pesanan sudah ditutup.');
    }

    public function audit(?StaffUser $u, string $type, int|string $id, string $action, string $summary): void
    {
        AuditLog::create(['actor_id' => $u?->id, 'entity_type' => $type, 'entity_key' => (string) $id, 'action' => $action, 'safe_summary' => $summary]);
    }

    private function state(Order $o, string $status, ?StaffUser $u, string $note, string $kind = 'STAFF'): void
    {
        $before = $o->status;
        $o->status = $status;
        $o->save();
        OrderStatusHistory::create(['order_id' => $o->id, 'sequence_no' => ($o->history()->max('sequence_no') ?? 0) + 1, 'from_status' => $before, 'to_status' => $status, 'actor_kind' => $u ? 'STAFF' : $kind, 'actor_id' => $u?->id, 'note' => $note]);
    }

    public function book(array $data, array $photos, string $sessionId): Order
    {
        Validator::make($data, ['service' => 'required|integer', 'branch' => 'required|integer', 'name' => 'required|string|min:2|max:100', 'phone' => ['required', 'regex:/^(?:\+?62|0)[0-9]{8,13}$/'], 'address' => 'nullable|string|max:500', 'mode' => 'required|in:BOTH,DELIVERY,DROPOFF', 'distance' => 'nullable|numeric|min:0|max:200', 'express' => 'required|boolean', 'promo' => 'nullable|string|max:32', 'notes' => 'nullable|string|max:1000', 'items' => 'required|array|min:1|max:20', 'items.*.description' => 'required|string|min:2|max:150', 'items.*.condition' => 'nullable|string|max:500', 'request_key' => 'required|uuid'])->validate();
        $key = hash('sha256', $sessionId.':'.$data['request_key']);

        return $this->media->atomic(function () use ($data, $photos, $key) {
            // PostgreSQL transaction advisory lock makes double-click retries idempotent.
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', [$key]);
            if ($existing = Order::where('request_key', $key)->first()) {
                return $existing;
            }
            $b = Branch::where('active', true)->lockForUpdate()->findOrFail($data['branch']);
            $s = Service::where('active', true)->lockForUpdate()->findOrFail($data['service']);
            $t = $s->latestTariff;
            $this->check((bool) $t, 'Layanan belum memiliki tarif.');
            $policy = ShippingPolicy::where('code', 'PICKUP')->orderByDesc('version')->firstOrFail();
            $distance = isset($data['distance']) && $data['distance'] !== '' ? (float) $data['distance'] : null;
            $this->check($data['mode'] === 'DROPOFF' || mb_strlen(trim($data['address'] ?? '')) >= 10, 'Alamat lengkap dibutuhkan untuk jemput/antar.');
            $this->check($data['mode'] !== 'BOTH' || $distance !== null, 'Isi estimasi jarak cabang ke alamat penjemputan.');
            $promo = $this->pricing->promotion($data['promo'] ?? '', $b->id);
            $this->check(trim($data['promo'] ?? '') === '' || $promo !== null, 'Kode promo tidak aktif untuk cabang ini.');
            $this->pricing->estimate($t, count($data['items']), (bool) $data['express'], $data['mode'], $distance, $policy, $promo);
            $assessment = $t->needsAssessment((bool) $data['express']);
            $code = 'PURO-'.strtoupper(bin2hex(random_bytes(8)));
            $o = Order::create(['access_code_hash' => hash('sha256', $code), 'access_code_ciphertext' => Crypt::encryptString($code), 'branch_id' => $b->id, 'tariff_version_id' => $t->id, 'shipping_policy_version_id' => $policy->id, 'promotion_version_id' => $promo?->id, 'qris_version_id' => $b->qrisAccount->latestVersion?->id, 'customer_name' => trim($data['name']), 'customer_phone' => $data['phone'], 'customer_address' => $data['address'] ?? null, 'fulfilment_mode' => $data['mode'], 'distance_km' => $distance, 'express_requested' => $data['express'], 'notes' => $data['notes'] ?? null, 'status' => $assessment ? 'WAITING_ASSESSMENT' : 'WAITING_PAYMENT', 'request_key' => $key]);
            foreach ($data['items'] as $i => $item) {
                $files = $photos[$i] ?? [];
                Validator::make(['photos' => $files], ['photos' => 'array|max:3', 'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120'])->validate();
                $this->check(! $assessment || count($files) >= 1, 'Layanan dengan penilaian memerlukan 1–3 foto setiap barang.');
                $it = $o->items()->create(['item_no' => $i + 1, 'item_description' => trim($item['description']), 'condition_notes' => $item['condition'] ?? null]);
                foreach ($files as $j => $file) {
                    DB::table('item_photos')->insert(['order_item_id' => $it->id, 'slot_no' => $j + 1, 'media_asset_id' => $this->media->store($file)->id]);
                }
            }
            if (! $assessment) {
                $q = $o->quotes()->create(['version' => 1, 'kind' => 'FIXED', 'decision' => 'ACCEPTED', 'decided_at' => now(), 'acceptance_basis' => 'CHECKOUT', 'assessment_note' => 'Tarif tetap disetujui saat pemesanan.']);
                foreach ($o->items as $it) {
                    DB::table('quote_items')->insert(['quote_id' => $q->id, 'order_item_id' => $it->id, 'unit_price' => $t->min_price, 'express_fee' => 0]);
                }
            }
            $this->state($o, $o->status, null, 'Booking diterima. Simpan kode pesanan untuk membuka kembali status.', 'SYSTEM');

            return $o;
        });
    }

    public function quote(StaffUser $u, int $id, array $prices, string $note, bool $expressAccepted, int $expectedVersion): void
    {
        DB::transaction(function () use ($u, $id, $prices, $note, $expressAccepted, $expectedVersion) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'admin', 'orders.manage');
            $this->open($o);
            $this->check(in_array($o->status, ['WAITING_ASSESSMENT', 'WAITING_APPROVAL', 'QUOTE_REJECTED']), 'Penawaran tidak dapat diubah pada tahap ini.');
            $this->check(($o->currentQuote?->version ?? 0) === $expectedVersion, 'Penawaran sudah berubah. Muat ulang rincian.');
            $this->check(! $o->verifiedPayment()->exists() && ! PaymentSubmission::whereIn('quote_id', $o->quotes()->select('id'))->exists(), 'Bukti pembayaran sudah masuk; penawaran dikunci.');
            $note = $this->text($note);
            $items = $o->items;
            $t = $o->tariff;
            $this->check(count($prices) === $items->count(), 'Isi harga semua barang.');
            $this->check(! $o->express_requested || $expressAccepted, 'Konfirmasi ketersediaan One Day terlebih dahulu.');
            foreach ($items as $it) {
                $p = $prices[$it->id] ?? null;
                $this->check(filter_var($p, FILTER_VALIDATE_INT) !== false && (int) $p > 0 && (int) $p <= 100000000, 'Harga harus berupa rupiah bulat positif.');
                $this->check(($t->min_price === null || $p >= $t->min_price) && ($t->max_price === null || $p <= $t->max_price), 'Harga di luar rentang tarif saat booking.');
            }
            $o->quotes()->where('decision', 'PENDING')->update(['decision' => 'SUPERSEDED', 'decided_at' => now()]);
            $q = $o->quotes()->create(['version' => $expectedVersion + 1, 'kind' => 'ASSESSMENT', 'assessment_note' => $note, 'issued_by' => $u->id]);
            foreach ($items as $it) {
                DB::table('quote_items')->insert(['quote_id' => $q->id, 'order_item_id' => $it->id, 'unit_price' => (int) $prices[$it->id], 'express_fee' => $o->express_requested ? $t->express_price : 0]);
            }
            $this->state($o, 'WAITING_APPROVAL', $u, 'Rincian harga baru siap ditinjau customer.');
        });
    }

    public function decideQuote(string $code, int $quoteId, bool $accept, string $reason = ''): void
    {
        DB::transaction(function () use ($code, $quoteId, $accept, $reason) {
            $o = $this->access->guest($code, true);
            $this->open($o);
            $q = $o->currentQuote;
            $this->check($o->status === 'WAITING_APPROVAL' && $q?->id === $quoteId && $q->decision === 'PENDING', 'Penawaran ini sudah tidak aktif.');
            $q->update(['decision' => $accept ? 'ACCEPTED' : 'REJECTED', 'decided_at' => now(), 'acceptance_basis' => $accept ? 'EXPLICIT' : null, 'decision_note' => $accept ? null : $this->text($reason)]);
            if ($accept) {
                $this->state($o, 'WAITING_PAYMENT', null, 'Customer menyetujui penawaran.', 'CUSTOMER');

                return;
            }
            if (! $o->receipt()->exists()) {
                $this->state($o, 'QUOTE_REJECTED', null, 'Customer menolak penawaran.', 'CUSTOMER');

                return;
            }
            $fee = $this->pricing->pickup($o->fulfilment_mode, $o->distance_km, $o->shippingPolicy);
            if ($fee > 0 && $o->latestInspection?->consented_at) {
                $o->quotes()->create(['version' => $q->version + 1, 'kind' => 'LOGISTICS', 'assessment_note' => 'Hanya biaya penjemputan yang telah disetujui saat pemeriksaan. Treatment tidak dilanjutkan.', 'decision' => 'ACCEPTED', 'decided_at' => now(), 'acceptance_basis' => 'INSPECTION_CONSENT']);
                $this->state($o, 'WAITING_PAYMENT', null, 'Treatment ditolak. Selesaikan biaya jemput sebelum pengembalian.', 'CUSTOMER');
            } else {
                $this->state($o, $o->fulfilment_mode === 'DROPOFF' ? 'READY_COLLECTION' : 'READY_DELIVERY', null, 'Treatment ditolak; barang siap dikembalikan.', 'CUSTOMER');
            }
        });
    }

    public function reassess(string $code): void
    {
        DB::transaction(function () use ($code) {
            $o = $this->access->guest($code, true);
            $this->check($o->status === 'QUOTE_REJECTED' && ! $o->receipt()->exists(), 'Penilaian ulang tidak tersedia.');
            $this->state($o, 'WAITING_ASSESSMENT', null, 'Customer meminta penilaian ulang.', 'CUSTOMER');
        });
    }

    public function requestInspection(StaffUser $u, int $id, string $reason): void
    {
        DB::transaction(function () use ($u, $id, $reason) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'admin', 'inspections.manage');
            $this->check($o->status === 'WAITING_ASSESSMENT' && ! $o->receipt()->exists(), 'Pemeriksaan tidak tersedia pada tahap ini.');
            $o->inspections()->create(['request_no' => ($o->inspections()->max('request_no') ?? 0) + 1, 'requested_by' => $u->id, 'reason' => $this->text($reason)]);
            $this->state($o, 'NEEDS_INSPECTION', $u, 'Pemeriksaan langsung diminta. Customer perlu memberi persetujuan.');
        });
    }

    public function consentInspection(string $code, int $inspectionId, bool $accept): void
    {
        DB::transaction(function () use ($code, $inspectionId, $accept) {
            $o = $this->access->guest($code, true);
            $this->check($o->status === 'NEEDS_INSPECTION' && $o->latestInspection?->id === $inspectionId, 'Permintaan pemeriksaan sudah berubah.');
            if (! $accept) {
                $this->cancelLocked($o, null, 'Customer tidak menyetujui pemeriksaan.');

                return;
            }
            $o->latestInspection->update(['consented_at' => now()]);
            $this->state($o, $o->fulfilment_mode === 'BOTH' ? 'WAITING_PICKUP' : 'WAITING_HANDOVER', null, 'Customer menyetujui pemeriksaan dan biaya jemput yang ditampilkan.', 'CUSTOMER');
        });
    }

    public function submitPayment(string $code, int $quoteId, string $reference, int $amount, $file, string $requestKey, ?int $expectedQrisVersion = null): void
    {
        $reference = Puro::reference($reference);
        Validator::make(['reference' => $reference, 'request_key' => $requestKey], ['reference' => ['required', 'regex:/^[A-Z0-9\-_.\/]{5,80}$/'], 'request_key' => 'required|uuid'])->validate();
        $this->media->atomic(function () use ($code, $quoteId, $reference, $amount, $file, $requestKey, $expectedQrisVersion) {
            $o = $this->access->guest($code, true);
            $this->open($o);
            $q = $o->currentQuote;
            $key = hash('sha256', $o->id.':'.$requestKey);
            if (PaymentSubmission::where('request_key', $key)->where('quote_id', $quoteId)->exists()) {
                return;
            }
            $this->check(in_array($o->status, ['WAITING_PAYMENT', 'PAYMENT_REJECTED']) && ! $o->verifiedPayment()->exists() && $q?->id === $quoteId && $q->decision === 'ACCEPTED', 'Tagihan berubah atau sedang diperiksa.');
            $this->check($q->totals()['total'] === $amount, 'Nominal harus sama dengan total tagihan.');
            if (! $o->qris_version_id) {
                $v = $o->branch->qrisAccount->latestVersion;
                $this->check($v !== null, 'QRIS cabang belum tersedia. Hubungi admin.');
                $this->check($expectedQrisVersion !== null && $v->id === $expectedQrisVersion, 'QRIS baru tersedia atau berubah. Tinjau QRIS pada halaman ini sebelum mengirim bukti.');
                $o->update(['qris_version_id' => $v->id]);
            } elseif ($expectedQrisVersion !== null) {
                $this->check($o->qris_version_id === $expectedQrisVersion, 'QRIS tidak sesuai dengan pesanan.');
            }
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['payment-reference:'.$reference]);
            $r = PaymentReference::firstOrCreate(['canonical_reference' => $reference], ['order_id' => $o->id]);
            $this->check($r->order_id === $o->id, 'Referensi sudah digunakan pesanan lain.');
            $proof = $this->media->store($file);
            $q->submissions()->create(['reference_id' => $r->id, 'attempt_no' => ($q->submissions()->max('attempt_no') ?? 0) + 1, 'proof_media_id' => $proof->id, 'claimed_amount' => $amount, 'request_key' => $key]);
            $this->state($o, 'PENDING_PAYMENT', null, 'Bukti pembayaran dikirim. Menunggu pemeriksaan admin.', 'CUSTOMER');
        });
    }

    public function reviewPayment(StaffUser $u, int $id, int $submissionId, bool $accept, string $typedReference, string $reason, bool $bankChecked): void
    {
        DB::transaction(function () use ($u, $id, $submissionId, $accept, $typedReference, $reason, $bankChecked) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'admin', 'payments.review');
            $this->check($o->status === 'PENDING_PAYMENT' && ! $o->verifiedPayment()->exists(), 'Pesanan tidak menunggu verifikasi.');
            $q = $o->currentQuote;
            $s = $q?->latestSubmission;
            $this->check($s?->id === $submissionId && $s->decision === 'PENDING' && $q->decision === 'ACCEPTED', 'Bukti telah berubah. Tinjau bukti terbaru.');
            if ($accept) {
                $this->check($bankChecked, 'Cocokkan pembayaran pada mutasi rekening/merchant terlebih dahulu.');
                $this->check(Puro::reference($typedReference) === $s->reference->canonical_reference, 'Ketik ulang referensi pembayaran dengan benar.');
                $this->check($s->claimed_amount === $q->totals()['total'], 'Nominal tagihan tidak sesuai.');
            }
            $s->update(['decision' => $accept ? 'VERIFIED' : 'REJECTED', 'reviewed_by' => $u->id, 'reviewed_at' => now(), 'review_reason' => $accept ? 'Referensi dan mutasi dikonfirmasi admin.' : $this->text($reason)]);
            if (! $accept) {
                $this->state($o, 'PAYMENT_REJECTED', $u, 'Bukti pembayaran ditolak. Customer dapat mengirim bukti ulang.');

                return;
            }
            VerifiedPayment::create(['order_id' => $o->id, 'submission_id' => $s->id]);
            $next = $q->kind === 'LOGISTICS' ? 'READY_DELIVERY' : ($o->receipt()->exists() ? 'WAITING_TECH' : ($o->fulfilment_mode === 'BOTH' ? 'WAITING_PICKUP' : 'WAITING_HANDOVER'));
            $this->state($o, $next, $u, 'Pembayaran diverifikasi.');
            $this->audit($u, 'order', $o->id, 'PAYMENT_VERIFIED', 'Bukti terbaru diverifikasi manual.');
        });
    }

    private function cancelLocked(Order $o, ?StaffUser $u, string $reason): void
    {
        $this->open($o);
        $this->check(! $o->verifiedPayment()->exists(), 'Pembayaran sudah diverifikasi. Hubungi penanggung jawab untuk penanganan lanjutan.');
        OrderCancellation::create(['order_id' => $o->id, 'actor_kind' => $u ? 'STAFF' : 'CUSTOMER', 'cancelled_by' => $u?->id, 'reason' => $this->text($reason), 'cancelled_at' => now(), 'return_required' => $o->receipt()->exists()]);
        $this->state($o, 'CANCELLED', $u, 'Pesanan dibatalkan.', $u ? 'STAFF' : 'CUSTOMER');
    }

    public function cancel(StaffUser $u, int $id, string $reason): void
    {
        DB::transaction(function () use ($u, $id, $reason) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'admin', 'orders.cancel');
            $this->cancelLocked($o, $u, $reason);
        });
    }

    private function received(Order $o, StaffUser $u, ?int $proof = null): void
    {
        OrderReceipt::create(['order_id' => $o->id, 'received_by' => $u->id, 'received_at' => now(), 'proof_media_id' => $proof]);
        $this->state($o, $o->treatmentPaid() ? 'WAITING_TECH' : 'WAITING_ASSESSMENT', $u, 'Barang diterima di cabang.');
    }

    public function receive(StaffUser $u, int $id, $file): void
    {
        $this->media->atomic(function () use ($u, $id, $file) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'admin', 'receipts.manage');
            $this->check($o->status === 'WAITING_HANDOVER' && ! $o->receipt()->exists(), 'Tidak menunggu penyerahan barang.');
            $this->received($o, $u, $this->media->store($file, $u)->id);
        });
    }

    public function assignCourier(StaffUser $u, int $id, int $courierId): void
    {
        DB::transaction(function () use ($u, $id, $courierId) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'admin', 'couriers.assign');
            $this->check(in_array($o->status, ['WAITING_PICKUP', 'READY_DELIVERY']), 'Tugas kurir belum dapat dibuat.');
            $c = StaffUser::findOrFail($courierId);
            $this->check($c->isRole('courier') && $c->branch_id === $o->branch_id, 'Pilih kurir aktif di cabang ini.');
            $type = $o->status === 'WAITING_PICKUP' ? 'PICKUP' : 'RETURN';
            $this->check(! $o->tasks()->where('task_type', $type)->exists(), 'Tugas ini sudah memiliki kurir.');
            $o->tasks()->create(['courier_id' => $c->id, 'task_type' => $type, 'purpose' => $type === 'PICKUP' ? ($o->treatmentPaid() ? 'TREATMENT' : 'INSPECTION') : ($o->treatmentPaid() ? 'TREATMENT' : 'QUOTE_REJECTION'), 'status' => 'ASSIGNED', 'assigned_by' => $u->id]);
            $this->audit($u, 'order', $o->id, 'ASSIGN_COURIER', 'Kurir cabang ditugaskan.');
        });
    }

    public function courier(StaffUser $u, int $id, int $taskId, bool $complete, $file = null): void
    {
        $this->media->atomic(function () use ($u, $id, $taskId, $complete, $file) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'courier', 'courier.work');
            $this->open($o);
            $task = $o->tasks()->findOrFail($taskId);
            $this->check($task->courier_id === $u->id, 'Tugas bukan milik Anda.');
            $pickup = $task->task_type === 'PICKUP';
            if (! $complete) {
                $this->check($task->status === 'ASSIGNED' && $o->status === ($pickup ? 'WAITING_PICKUP' : 'READY_DELIVERY'), 'Tugas belum siap dimulai.');
                $task->update(['status' => 'IN_PROGRESS', 'started_at' => now()]);
                $this->state($o, $pickup ? 'PICKING_UP' : 'OUT_FOR_DELIVERY', $u, $pickup ? 'Kurir menuju penjemputan.' : 'Kurir mengantar barang.');
            } else {
                $this->check($task->status === 'IN_PROGRESS' && $o->status === ($pickup ? 'PICKING_UP' : 'OUT_FOR_DELIVERY'), 'Tugas tidak sedang berlangsung.');
                $m = $this->media->store($file, $u);
                $task->update(['status' => 'COMPLETED', 'completed_at' => now(), 'proof_media_id' => $m->id]);
                if ($pickup) {
                    $this->received($o, $u, $m->id);
                } else {
                    $this->state($o, 'COMPLETE', $u, 'Barang diserahkan. Bukti pengantaran tersimpan.');
                }
            }
        });
    }

    public function assignTech(StaffUser $u, int $id, int $techId): void
    {
        DB::transaction(function () use ($u, $id, $techId) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'admin', 'technicians.assign');
            $this->check($o->status === 'WAITING_TECH' && $o->treatmentPaid() && $o->receipt()->exists(), 'Pembayaran dan penerimaan barang harus lengkap.');
            $t = StaffUser::findOrFail($techId);
            $this->check($t->isRole('tech') && $t->branch_id === $o->branch_id, 'Pilih teknisi aktif cabang ini.');
            $this->check(! $o->treatments()->exists(), 'Teknisi telah ditugaskan.');
            $o->treatments()->create(['cycle_no' => 1, 'technician_id' => $t->id, 'assigned_by' => $u->id, 'status' => 'ASSIGNED']);
            $this->audit($u, 'order', $o->id, 'ASSIGN_TECH', 'Teknisi cabang ditugaskan.');
        });
    }

    public function treatment(StaffUser $u, int $id, int $cycleId, bool $finish, $file, string $note = ''): void
    {
        $this->media->atomic(function () use ($u, $id, $cycleId, $finish, $file, $note) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'tech', 'treatment.work');
            $this->check($o->treatmentPaid() && $o->receipt()->exists(), 'Barang belum siap dikerjakan.');
            $t = $o->latestTreatment;
            $this->check($t?->id === $cycleId && $t->technician_id === $u->id, 'Penugasan telah berubah.');
            $this->check($finish ? ($o->status === 'CLEANING' && $t->status === 'WORKING') : (in_array($o->status, ['WAITING_TECH', 'REWORK']) && $t->status === 'ASSIGNED'), 'Tahap pengerjaan tidak sesuai.');
            $m = $this->media->store($file, $u);
            DB::table('treatment_photos')->insert(['treatment_id' => $t->id, 'phase' => $finish ? 'AFTER' : 'BEFORE', 'media_asset_id' => $m->id]);
            $t->update($finish ? ['status' => 'SUBMITTED', 'submitted_at' => now(), 'notes' => $this->text($note)] : ['status' => 'WORKING', 'started_at' => now()]);
            $this->state($o, $finish ? 'WAITING_QC' : 'CLEANING', $u, $finish ? 'Hasil treatment siap diperiksa QC.' : 'Teknisi mulai merawat barang.');
        });
    }

    public function qc(StaffUser $u, int $id, int $cycleId, bool $pass, string $note): void
    {
        DB::transaction(function () use ($u, $id, $cycleId, $pass, $note) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'qc', 'qc.review');
            $t = $o->latestTreatment;
            $this->check($o->status === 'WAITING_QC' && $t?->id === $cycleId && $t->status === 'SUBMITTED' && ! $t->qc()->exists(), 'Siklus QC sudah berubah.');
            QcCheck::create(['treatment_id' => $t->id, 'qc_user_id' => $u->id, 'result' => $pass ? 'PASSED' : 'REWORK', 'notes' => $this->text($note), 'checked_at' => now()]);
            if (! $pass) {
                $o->treatments()->create(['cycle_no' => $t->cycle_no + 1, 'technician_id' => $t->technician_id, 'assigned_by' => $u->id, 'status' => 'ASSIGNED']);
            }
            $this->state($o, $pass ? ($o->fulfilment_mode === 'DROPOFF' ? 'READY_COLLECTION' : 'READY_DELIVERY') : 'REWORK', $u, $pass ? 'QC menyetujui hasil treatment.' : 'QC meminta pengerjaan ulang.');
        });
    }

    public function collect(StaffUser $u, int $id): void
    {
        DB::transaction(function () use ($u, $id) {
            $o = $this->locked($id);
            $this->access->operational($u, $o, 'admin', 'receipts.manage');
            $this->check($o->status === 'READY_COLLECTION', 'Pesanan belum siap diambil.');
            $this->state($o, 'COMPLETE', $u, 'Customer telah mengambil barang di cabang.');
        });
    }
}
