<?php

namespace Tests\Feature;

use App\Livewire\Booking;
use App\Livewire\Management;
use App\Livewire\StaffLogin;
use App\Livewire\StaffOrder;
use App\Livewire\Tracking;
use App\Models\Branch;
use App\Models\Order;
use App\Models\PaymentSubmission;
use App\Models\Permission;
use App\Models\Service;
use App\Models\StaffUser;
use App\Models\TreatmentLog;
use App\Models\VerifiedPayment;
use App\Services\Masters;
use App\Services\Reports;
use App\Services\Workflow;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_reseeding_preserves_changed_codes_branch_names_and_revoked_permissions(): void
    {
        $admin = $this->actor();
        $permission = Permission::where('code', 'orders.cancel')->first();
        $admin->role->permissions()->detach($permission->id);
        $admin->branch->update(['name' => 'PURO Outlet Pilihan']);
        Service::where('code', 'women-kids')->update(['code' => 'women-kids-updated']);
        $admin->update(['username' => 'operator01']);
        $this->seed();
        $this->assertSame(4, Branch::count());
        $this->assertSame(17, Service::count());
        $this->assertFalse(StaffUser::where('username', 'admin01')->exists());
        $this->assertFalse($admin->fresh()->allows('orders.cancel'));
        $this->assertSame('PURO Outlet Pilihan', $admin->fresh()->branch->name);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function actor(string $name = 'admin01'): StaffUser
    {
        return StaffUser::where('username', $name)->firstOrFail();
    }

    public function test_every_management_screen_is_renderable_and_scope_is_enforced(): void
    {
        $this->actingAs($this->actor('superadmin'));
        foreach (['catalog', 'branches', 'qris', 'accounts', 'permissions', 'promos', 'reports', 'knowledge'] as $tab) {
            $this->get('/staff/manage/'.$tab)->assertOk();
        }
        $this->actingAs($this->actor());
        foreach (['accounts', 'promos', 'reports'] as $tab) {
            $this->get('/staff/manage/'.$tab)->assertOk();
        }
        $this->get('/staff/manage/branches')->assertForbidden();
    }

    public function test_one_day_needs_assessment_even_for_fixed_price_and_requires_staff_confirmation(): void
    {
        $w = app(Workflow::class);
        $d = $this->data();
        $d['express'] = true;
        $o = $w->book($d, [[$this->image()]], 'express-session');
        $this->assertSame('WAITING_ASSESSMENT', $o->status);
        $prices = [$o->items->first()->id => 30000];
        $this->invalid(fn () => $w->quote($this->actor(), $o->id, $prices, 'Pengerjaan One Day tersedia.', false, 0));
        $w->quote($this->actor(), $o->id, $prices, 'Pengerjaan One Day tersedia.', true, 0);
        $this->assertSame(55000, $o->fresh()->total());
    }

    public function test_payment_double_click_is_idempotent_and_qris_version_is_explicit_on_late_setup(): void
    {
        $o = $this->book();
        $w = app(Workflow::class);
        $oldVersion = $o->qris_version_id;
        app(Masters::class)->qris($this->actor('superadmin'), $o->branch->qris_account_id, $this->image());
        $newVersion = $o->branch->qrisAccount->latestVersion->id;
        $this->assertNotSame($oldVersion, $newVersion);
        $this->assertSame($oldVersion, $o->fresh()->qris_version_id);
        $o->update(['qris_version_id' => null]);
        $key = (string) Str::uuid();
        $this->invalid(fn () => $w->submitPayment($o->access_code, $o->currentQuote->id, 'LATEREF001', $o->total(), $this->image(), $key, $oldVersion));
        $w->submitPayment($o->access_code, $o->currentQuote->id, 'LATEREF001', $o->total(), $this->image(), $key, $newVersion);
        $w->submitPayment($o->access_code, $o->currentQuote->id, 'LATEREF001', $o->total(), $this->image(), $key, $newVersion);
        $this->assertSame(1, PaymentSubmission::count());
        $this->assertSame($newVersion, $o->fresh()->qris_version_id);
    }

    public function test_database_rejects_historical_version_and_final_evidence_overwrites(): void
    {
        $o = $this->book();
        $s = $this->claim($o);
        app(Workflow::class)->reviewPayment($this->actor(), $o->id, $s->id, false, '', 'Gambar referensi perlu diperjelas.', false);
        foreach ([fn () => DB::table('service_tariff_versions')->where('id', $o->tariff_version_id)->update(['min_price' => 1]), fn () => DB::table('payment_submissions')->where('id', $s->id)->update(['review_reason' => 'Overwritten'])] as $change) {
            try {
                DB::transaction($change);
                $this->fail('Trigger historis seharusnya menolak perubahan.');
            } catch (QueryException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame(30000, $o->fresh()->total());
        $this->assertSame('Gambar referensi perlu diperjelas.', $s->fresh()->review_reason);
    }

    public function test_forged_image_fails_without_persisting_order_or_private_file(): void
    {
        $d = $this->data('deep');
        $path = tempnam(sys_get_temp_dir(), 'puro-forged-');
        file_put_contents($path, 'This is not image data.');
        $bad = new UploadedFile($path, 'fake.jpg', 'image/jpeg', null, true);
        try {
            $this->invalid(fn () => app(Workflow::class)->book($d, [[$bad]], 'bad-photo'));
        } finally {
            @unlink($path);
        }
        $this->assertSame(0, Order::count());
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('evidence.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jYXkAAAAASUVORK5CYII='));
    }

    private function data(string $service = 'women-kids', string $mode = 'DROPOFF'): array
    {
        return ['service' => Service::where('code', $service)->value('id'), 'branch' => $this->actor()->branch_id, 'name' => 'Private Customer Example', 'phone' => '081234567890', 'address' => 'Private address 123, Jakarta', 'mode' => $mode, 'distance' => $mode === 'BOTH' ? 6.2 : '', 'express' => false, 'promo' => '', 'notes' => '', 'items' => [['description' => 'Sepatu kanvas putih', 'condition' => 'Kotor bagian depan']], 'request_key' => (string) Str::uuid()];
    }

    private function book(string $service = 'women-kids', string $mode = 'DROPOFF'): Order
    {
        $d = $this->data($service, $mode);

        return app(Workflow::class)->book($d, $service === 'women-kids' ? [] : [[$this->image()]], 'session-one');
    }

    private function invalid(callable $fn, string $contains = ''): void
    {
        try {
            $fn();
            $this->fail('Aksi tidak valid seharusnya ditolak.');
        } catch (ValidationException $e) {
            if ($contains) {
                $this->assertStringContainsString($contains, $e->getMessage());
            }
        }
    }

    private function claim(Order $o, string $reference = 'TESTREF001'): PaymentSubmission
    {
        $w = app(Workflow::class);
        $o = $o->fresh();
        $w->submitPayment($o->access_code, $o->currentQuote->id, $reference, $o->total(), $this->image(), (string) Str::uuid());

        return $o->currentQuote->latestSubmission()->first();
    }

    private function verify(Order $o, string $reference = 'TESTREF001'): void
    {
        $s = $this->claim($o, $reference);
        app(Workflow::class)->reviewPayment($this->actor(), $o->id, $s->id, true, $reference, '', true);
    }

    public function test_booking_has_stable_random_code_and_session_bound_idempotency(): void
    {
        $w = app(Workflow::class);
        $d = $this->data();
        $one = $w->book($d, [], 'same-browser');
        $again = $w->book($d, [], 'same-browser');
        $other = $w->book($d, [], 'other-browser');
        $this->assertSame($one->id, $again->id);
        $this->assertNotSame($one->id, $other->id);
        $this->assertMatchesRegularExpression('/^PURO-[A-F0-9]{16}$/', $one->access_code);
        $this->assertSame('WAITING_PAYMENT', $one->status);
        $this->assertSame(30000, $one->total());
        $this->assertNotSame($one->access_code, $one->access_code_hash);
    }

    public function test_assessment_requires_photos_per_item_and_rejects_empty_orders(): void
    {
        $w = app(Workflow::class);
        $d = $this->data('deep');
        $this->invalid(fn () => $w->book($d, [], 's'), 'foto');
        $d['items'] = [];
        $this->invalid(fn () => $w->book($d, [], 's'));
        $this->assertSame(0, Order::count());
    }

    public function test_shipping_and_discount_are_server_calculated_once_for_multiple_items(): void
    {
        $d = $this->data('fast', 'BOTH');
        $d['items'][] = $d['items'][0];
        $d['promo'] = 'FRESH10';
        $o = app(Workflow::class)->book($d, [[$this->image()], [$this->image()]], 'x');
        $this->assertSame('WAITING_ASSESSMENT', $o->status);
        $prices = $o->items->mapWithKeys(fn ($it) => [$it->id => 35000])->all();
        app(Workflow::class)->quote($this->actor(), $o->id, $prices, 'Kondisi dua barang membutuhkan pembersihan.', false, 0);
        $q = $o->fresh()->currentQuote;
        $this->assertSame(['subtotal' => 70000, 'discount' => 7000, 'pickup_fee' => 15000, 'total' => 78000], $q->totals());
    }

    public function test_stale_quote_and_out_of_tariff_prices_cannot_be_accepted(): void
    {
        $w = app(Workflow::class);
        $o = $this->book('fast');
        $id = $o->items->first()->id;
        $this->invalid(fn () => $w->quote($this->actor(), $o->id, [$id => 40000], 'Penilaian kondisi cukup berat.', false, 0), 'rentang');
        $w->quote($this->actor(), $o->id, [$id => 32000], 'Penilaian awal selesai.', false, 0);
        $old = $o->fresh()->currentQuote;
        $w->quote($this->actor(), $o->id, [$id => 33000], 'Perbaikan rincian harga.', false, 1);
        $this->invalid(fn () => $w->decideQuote($o->access_code, $old->id, true), 'aktif');
        $current = $o->fresh()->currentQuote;
        $w->decideQuote($o->access_code, $current->id, true);
        $this->assertSame('WAITING_PAYMENT', $o->fresh()->status);
    }

    public function test_rejected_evidence_retries_preserve_history_without_cancelling(): void
    {
        $w = app(Workflow::class);
        $o = $this->book();
        $code = $o->access_code;
        $s1 = $this->claim($o);
        $w->reviewPayment($this->actor(), $o->id, $s1->id, false, '', 'Referensi tidak terbaca, kirim foto yang jelas.', false);
        $this->assertSame('PAYMENT_REJECTED', $o->fresh()->status);
        $this->assertNull($o->fresh()->cancellation);
        $s2 = $this->claim($o);
        $this->assertNotSame($s1->id, $s2->id);
        $this->assertSame(2, $s2->attempt_no);
        $this->assertSame('REJECTED', $s1->fresh()->decision);
        $this->invalid(fn () => $w->reviewPayment($this->actor(), $o->id, $s1->id, true, 'TESTREF001', '', true), 'terbaru');
        $w->reviewPayment($this->actor(), $o->id, $s2->id, true, 'TESTREF001', '', true);
        $this->assertSame('WAITING_HANDOVER', $o->fresh()->status);
        $this->assertSame($code, $o->fresh()->access_code);
        $this->assertSame(1, VerifiedPayment::count());
        $this->assertSame(2, PaymentSubmission::count());
    }

    public function test_reference_cannot_cross_orders_and_nominal_and_bank_check_are_required(): void
    {
        $w = app(Workflow::class);
        $a = $this->book();
        $b = $this->book();
        $s = $this->claim($a);
        $this->invalid(fn () => $this->claim($b), 'lain');
        $this->invalid(fn () => $w->submitPayment($b->access_code, $b->currentQuote->id, 'REFNEW123', 1, $this->image(), (string) Str::uuid()), 'Nominal');
        $this->invalid(fn () => $w->reviewPayment($this->actor(), $a->id, $s->id, true, 'TESTREF001', '', false), 'mutasi');
        $this->invalid(fn () => $w->reviewPayment($this->actor(), $a->id, $s->id, true, 'WRONG123', '', true), 'referensi');
    }

    public function test_cancellation_is_separate_reason_required_and_freezes_late_commands(): void
    {
        $w = app(Workflow::class);
        $o = $this->book();
        $s = $this->claim($o);
        $this->invalid(fn () => $w->cancel($this->actor(), $o->id, ''));
        $w->cancel($this->actor(), $o->id, 'Customer meminta pembatalan booking.');
        $this->assertSame('CANCELLED', $o->fresh()->status);
        $this->assertNotNull($o->fresh()->cancellation);
        $this->invalid(fn () => $w->reviewPayment($this->actor(), $o->id, $s->id, true, 'TESTREF001', '', true));
        $this->invalid(fn () => $this->claim($o));
        $this->assertSame(0, VerifiedPayment::count());
    }

    public function test_superadmin_and_other_branch_cannot_verify_payments(): void
    {
        $o = $this->book();
        $s = $this->claim($o);
        foreach (['superadmin', 'admin02', 'teknisi01', 'qc01', 'kurir01'] as $name) {
            try {
                app(Workflow::class)->reviewPayment($this->actor($name), $o->id, $s->id, true, 'TESTREF001', '', true);
                $this->fail('Akses peran/cabang seharusnya ditolak.');
            } catch (AuthorizationException) {
                $this->assertSame('PENDING_PAYMENT', $o->fresh()->status);
            }
        }
    }

    public function test_treatment_requires_receipt_and_payment_and_preserves_rework_cycles(): void
    {
        $w = app(Workflow::class);
        $o = $this->book();
        $tech = $this->actor('teknisi01');
        $qc = $this->actor('qc01');
        $this->invalid(fn () => $w->assignTech($this->actor(), $o->id, $tech->id));
        $this->verify($o);
        $w->receive($this->actor(), $o->id, $this->image());
        $w->assignTech($this->actor(), $o->id, $tech->id);
        $t = $o->fresh()->latestTreatment;
        $w->treatment($tech, $o->id, $t->id, false, $this->image());
        $w->treatment($tech, $o->id, $t->id, true, $this->image(), 'Pembersihan tahap awal selesai.');
        $w->qc($qc, $o->id, $t->id, false, 'Noda di bagian upper perlu dibersihkan ulang.');
        $t2 = $o->fresh()->latestTreatment;
        $this->assertNotSame($t->id, $t2->id);
        $this->assertSame(2, $t2->cycle_no);
        $this->assertSame('REWORK', $t->fresh()->qc->result);
        $w->treatment($tech, $o->id, $t2->id, false, $this->image());
        $w->treatment($tech, $o->id, $t2->id, true, $this->image(), 'Pembersihan ulang selesai.');
        $w->qc($qc, $o->id, $t2->id, true, 'Kondisi bersih dan sesuai standar.');
        $this->assertSame('READY_COLLECTION', $o->fresh()->status);
        $this->assertSame(2, TreatmentLog::count());
        $this->assertSame(4, DB::table('treatment_photos')->count());
        $w->collect($this->actor(), $o->id);
        $this->assertSame('COMPLETE', $o->fresh()->status);
    }

    public function test_inspection_rejection_charges_only_consented_pickup_and_never_opens_treatment(): void
    {
        $w = app(Workflow::class);
        $o = $this->book('deep', 'BOTH');
        $w->requestInspection($this->actor(), $o->id, 'Perlu memeriksa bahan secara langsung.');
        $w->consentInspection($o->access_code, $o->fresh()->latestInspection->id, true);
        $courier = $this->actor('kurir01');
        $w->assignCourier($this->actor(), $o->id, $courier->id);
        $task = $o->tasks()->first();
        $w->courier($courier, $o->id, $task->id, false);
        $w->courier($courier, $o->id, $task->id, true, $this->image());
        $this->assertNotNull($o->fresh()->receipt);
        $w->quote($this->actor(), $o->id, [$o->items->first()->id => 45000], 'Pemeriksaan bahan selesai.', false, 0);
        $w->decideQuote($o->access_code, $o->fresh()->currentQuote->id, false, 'Harga belum sesuai, kembalikan barang.');
        $o = $o->fresh();
        $this->assertSame('LOGISTICS', $o->currentQuote->kind);
        $this->assertSame(15000, $o->total());
        $this->verify($o);
        $this->assertSame('READY_DELIVERY', $o->fresh()->status);
        $this->invalid(fn () => $w->assignTech($this->actor(), $o->id, $this->actor('teknisi01')->id));
        $w->assignCourier($this->actor(), $o->id, $courier->id);
        $return = $o->tasks()->where('task_type', 'RETURN')->first();
        $w->courier($courier, $o->id, $return->id, false);
        $w->courier($courier, $o->id, $return->id, true, $this->image());
        $this->assertSame('COMPLETE', $o->fresh()->status);
        $this->assertSame(0, TreatmentLog::count());
    }

    public function test_new_catalogue_and_shipping_versions_do_not_reprice_old_order(): void
    {
        $o = $this->book('women-kids', 'BOTH');
        $total = $o->total();
        $t = $o->tariff;
        $d = $t->getAttributes() + ['category_id' => $t->service->category_id, 'code' => $t->service->code, 'active' => true];
        $d['min_price'] = $d['max_price'] = 42000;
        $d['poster_source'] = 'AUTO';
        $m = app(Masters::class);
        $m->tariff($this->actor('superadmin'), $t->service_id, $d);
        $m->shipping($this->actor('superadmin'), 7000);
        $this->assertSame($total, $o->fresh()->total());
        $new = $this->book('women-kids', 'BOTH');
        $this->assertSame(63000, $new->total());
        $this->assertNotSame($o->tariff_version_id, $new->tariff_version_id);
    }

    public function test_admin_cannot_create_privileged_or_other_branch_accounts(): void
    {
        $m = app(Masters::class);
        $d = ['username' => 'newroot', 'full_name' => 'Wrong Privilege', 'role_id' => $this->actor('superadmin')->role_id, 'branch_id' => $this->actor()->branch_id, 'active' => true, 'password' => 'long-password-123'];
        $this->invalid(fn () => $m->account($this->actor(), null, $d));
        $d['role_id'] = $this->actor('teknisi01')->role_id;
        $m->account($this->actor(), null, $d);
        $this->assertSame($this->actor()->branch_id, StaffUser::where('username', 'newroot')->first()->branch_id);
    }

    public function test_reports_count_only_verified_payment_once_not_rejected_attempts(): void
    {
        $o = $this->book();
        $s = $this->claim($o);
        $w = app(Workflow::class);
        $w->reviewPayment($this->actor(), $o->id, $s->id, false, '', 'Mohon unggah foto yang jelas.', false);
        $this->verify($o);
        $this->book();
        $r = app(Reports::class);
        $q = $r->query($this->actor(), now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d'));
        $this->assertSame(1, $q->count());
        $this->assertSame(30000, (int) $q->sum('v.total'));
        $this->assertSame(0, $r->query($this->actor('admin02'), now()->subDay()->format('Y-m-d'), now()->addDay()->format('Y-m-d'))->count());
    }

    public function test_public_pages_and_tracking_never_render_customer_pii(): void
    {
        $o = $this->book();
        foreach (['/', '/layanan', '/lokasi', '/book', '/panduan', '/tentang', '/kontak', '/staff/login'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/track/'.$o->access_code)->assertOk()->assertSee($o->access_code)->assertDontSee('Private Customer Example')->assertDontSee('081234567890')->assertDontSee('Private address 123');
    }

    public function test_private_payment_media_enforces_branch_role_and_active_account(): void
    {
        $o = $this->book();
        $s = $this->claim($o);
        $url = route('media.private', $s->proof_media_id);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->actor('admin02'))->get($url)->assertForbidden();
        $this->actingAs($this->actor('teknisi01'))->get($url)->assertForbidden();
        $this->actingAs($this->actor())->get($url)->assertOk();
        $admin = $this->actor();
        $admin->update(['active' => false]);
        $this->actingAs($admin)->get($url)->assertRedirect(route('login'));
    }

    public function test_livewire_booking_login_tracking_and_branch_guards(): void
    {
        Livewire::test(Booking::class)->set('d.name', 'Livewire Customer')->set('d.phone', '081234567890')->set('d.service', Service::where('code', 'women-kids')->value('id'))->set('d.items.0.description', 'Sepatu putih')->call('submit')->assertHasNoErrors()->assertRedirect();
        $o = Order::first();
        Livewire::test(Tracking::class, ['code' => $o->access_code])->assertSee('Booking sudah tercatat');
        Livewire::test(StaffLogin::class)->set('username', 'admin01')->set('password', 'Demo123!')->call('login')->assertHasNoErrors()->assertRedirect(route('staff.dashboard'));
        $this->actingAs($this->actor('admin02'));
        Livewire::test(StaffOrder::class, ['id' => $o->id])->assertForbidden();
        $this->actingAs($this->actor());
        Livewire::test(Management::class, ['tab' => 'catalog'])->assertForbidden();
    }
}
