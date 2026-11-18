<div class="container page-shell">
    @include('partials.staff-nav')<div class="page-intro">
        <div><span class="eyebrow">PURO / MANAGEMENT</span>
            <h1>{{ ['catalog' => 'Layanan & media', 'branches' => 'Cabang & ongkir', 'qris' => 'QRIS cabang', 'accounts' => 'Akun staf', 'permissions' => 'Izin peran', 'promos' => 'Promosi', 'reports' => 'Laporan penjualan', 'knowledge' => 'Dokumen chatbot'][$tab] }}
            </h1>
        </div>
        <p>Pengaturan mengikuti peran dan cakupan cabangmu.</p>
    </div>@include('partials.feedback')
    @if (in_array($tab, ['catalog', 'branches', 'accounts', 'promos']))
        <div class="management-layout">
            <section class="panel panel-pad">
                <div class="section-heading">
                    <h2>Daftar
                        {{ $tab === 'catalog' ? 'layanan' : ($tab === 'branches' ? 'cabang' : ($tab === 'accounts' ? 'akun' : 'promo')) }}
                    </h2><button class="btn btn-lime btn-sm" wire:click="clear">＋ Tambah</button>
                </div>
                <div class="management-list">
                    @forelse($rows as $row)
                        <article class="management-row"><button class="row-selector"
                                wire:click="edit({{ $row->id }})">
                                @if ($tab === 'catalog')
                                    <strong>{{ $row->latestTariff?->display_name }}</strong><small>{{ $row->category->name }}
                                        · {{ $row->latestTariff?->pricing_mode }} ·
                                        v{{ $row->latestTariff?->version }}</small>
                                @elseif($tab === 'branches')
                                    <strong>{{ $row->name }} ·
                                        {{ $row->area }}</strong><small>{{ $row->qrisAccount->group_name }}</small>
                                @elseif($tab === 'accounts')
                                    <strong>{{ $row->full_name }}</strong><small>{{ $row->username }} ·
                                        {{ $row->role->name }} ·
                                    {{ $row->branch?->name ?? 'Pusat' }}</small>@else<strong>{{ $row->code }} ·
                                        {{ $row->latestVersion?->percent }}%</strong><small>{{ $row->branch?->name ?? 'Global' }}
                                        · Maks.
                                        {{ \App\Support\Puro::money($row->latestVersion?->cap_amount) }}</small>
                                @endif
                                <span class="pill {{ $row->active ? 'green' : 'neutral' }}">
                                    {{ $row->active ? 'Aktif' : 'Nonaktif' }}</span>
                            </button>
                            @if ($tab === 'promos')
                                <button class="btn btn-ghost btn-sm" wire:click="archive({{ $row->id }})"
                                    wire:confirm="Arsipkan promo? Riwayat pesanan tetap tersimpan.">Arsipkan</button>
                            @endif
                        </article>
                    @empty<p>Belum ada data.</p>
                    @endforelse
                </div>
            </section>
            <form wire:submit="save" class="panel panel-pad"><span
                    class="eyebrow">{{ $editing ? 'EDIT / VERSI BARU' : 'TAMBAH DATA' }}</span>
                <h2>{{ $editing ? 'Perbarui data' : 'Buat data baru' }}</h2>
                @if ($tab === 'catalog')<label>Kategori<select class="field"
                            wire:model="d.category_id">
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="form-grid"><label>Kode layanan<input class="field" wire:model="d.code" required
                                maxlength="40" placeholder="contoh: deep"></label><label>Nama layanan<input
                                class="field" wire:model="d.display_name" required maxlength="80"></label></div>
                    <label>Deskripsi
                        <textarea class="textarea" wire:model="d.description" required maxlength="2000"></textarea>
                    </label><label>Jenis tarif<select class="field" wire:model.live="d.pricing_mode">
                            <option value="FIXED">Harga tetap</option>
                            <option value="RANGE">Rentang penilaian</option>
                            <option value="QUOTE">Penawaran admin</option>
                        </select></label>
                    @if ($d['pricing_mode'] !== 'QUOTE')
                        <div class="form-grid"><label>Harga minimum (rupiah)<input type="number" class="field"
                                    wire:model="d.min_price" min="1" step="1" required></label><label>Harga
                                maksimum (sama jika tetap)<input type="number" class="field" wire:model="d.max_price"
                                    min="1" step="1" required></label></div>
                    @endif
                    <div class="form-grid">
                        <label>Satuan<select class="field" wire:model="d.unit">
                                <option value="pasang">Pasang</option>
                                <option value="unit">Unit</option>
                            </select></label><label>Estimasi pengerjaan<input class="field"
                                wire:model="d.duration_text" required maxlength="80"></label>
                    </div><label class="check-label"><input type="checkbox" wire:model="d.express_available"> Mendukung
                        permintaan One Day (+Rp25.000 per barang)</label><label>Catatan untuk customer
                        <textarea class="textarea" wire:model="d.customer_note" maxlength="1000"></textarea>
                    </label><label>Foto cover baru (opsional)<input type="file" class="field" wire:model="cover"
                            accept="image/jpeg,image/png,image/webp"></label><label>Poster tarif<select class="field"
                            wire:model.live="d.poster_source">
                            <option value="AUTO">Otomatis sesuai versi harga</option>
                            <option value="CUSTOM">Unggah poster baru</option>
                        </select></label>
                    @if ($d['poster_source'] === 'CUSTOM')
                        <label>Poster baru (wajib setiap publikasi)<input type="file" class="field"
                                wire:model="poster" accept="image/jpeg,image/png,image/webp" required></label>
                    @endif
                    <div class="notice small">
                        Perubahan menerbitkan versi tarif baru. Tarif pada pesanan lama tetap sama.</div>
                @elseif($tab === 'branches')
                    @foreach (['name' => 'Nama cabang', 'area' => 'Kota / area', 'address' => 'Alamat lengkap'] as $key => $label)
                        <label>{{ $label }}<input class="field" wire:model="d.{{ $key }}" required
                                maxlength="{{ $key === 'address' ? 500 : 100 }}"></label>
                    @endforeach
                    <div class="form-grid">
                        <label>Latitude<input class="field" type="number" step="any" min="-90" max="90"
                                wire:model="d.latitude" required></label><label>Longitude<input class="field"
                                type="number" step="any" min="-180" max="180" wire:model="d.longitude"
                                required></label>
                    </div><label>Grup QRIS<select class="field" wire:model="d.qris_account_id">
                            @foreach ($groups as $g)
                                <option value="{{ $g->id }}">{{ $g->group_name }}</option>
                            @endforeach
                        </select>
                    </label><label>Foto cabang baru (opsional)<input class="field" type="file" wire:model="cover"
                            accept="image/jpeg,image/png,image/webp"></label>
                @elseif($tab === 'accounts')
                    <label>Nama staf<input class="field" wire:model="d.full_name" required
                            maxlength="100"></label><label>Username<input class="field" wire:model="d.username"
                            required maxlength="40" autocomplete="off"></label><label>Peran<select class="field"
                            wire:model="d.role_id">
                            @foreach ($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    @if ($u->isRole('superadmin'))
                        <label>Cabang (kosong untuk Super Admin)<select class="field" wire:model="d.branch_id">
                                <option value="">Pusat</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <label>
                        {{ $editing ? 'Password baru (kosongkan untuk mempertahankan)' : 'Password (minimal 10 karakter)' }}<input
                            class="field" type="password" wire:model="d.password" autocomplete="new-password"
                            minlength="10" maxlength="100" @required(!$editing)></label>
                @elseif($tab === 'promos')
                    <label>Kode promo<input class="field" wire:model="d.code" required maxlength="32"></label>
                    @if ($u->isRole('superadmin'))
                        <label>Cakupan<select class="field" wire:model="d.branch_id" @disabled($editing)>
                                <option value="">Global / semua cabang</option>
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <div class="form-grid">
                        <label>Diskon (%)<input class="field" type="number" wire:model="d.percent" min="1"
                                max="90" required></label><label>Maksimum potongan (rupiah)<input class="field"
                                type="number" wire:model="d.cap_amount" min="1" required></label><label>Mulai
                            (WIB, opsional)<input class="field" type="datetime-local"
                                wire:model="d.starts_at"></label><label>Berakhir (WIB, opsional)<input class="field"
                                type="datetime-local" wire:model="d.ends_at"></label>
                    </div><label>Cover promo baru (opsional)<input class="field" type="file" wire:model="cover"
                            accept="image/jpeg,image/png,image/webp"></label>
                @endif
                <label class="check-label">
                    <input type="checkbox" wire:model="d.active"> Aktif</label><button class="btn btn-lime btn-wide"
                    type="submit" wire:loading.attr="disabled">{{ $editing ? 'Simpan perubahan' : 'Buat data' }}
                    ↗</button>
                <p class="small muted">Gambar JPG/PNG/WebP, maksimal 5 MB.</p>
            </form>
        </div>
        @if ($tab === 'branches')
            <form class="panel panel-pad history-panel" wire:submit="saveShipping">
                <h2>Tarif jemput untuk booking baru</h2>
                <p>4 km pertama gratis; kelebihan dibulatkan ke atas. Pengembalian gratis. Versi tarif lama
                    dipertahankan.</p><label>Tarif per km (rupiah)<input class="field" type="number" min="0"
                        max="100000" wire:model="rate" required></label><button class="btn btn-dark"
                    type="submit">Terbitkan tarif baru</button>
                <p class="small muted">Sesudah mengubah kebijakan, perbarui panduan pelanggan dan indeks chatbot.</p>
            </form>
        @endif
    @elseif($tab === 'qris')
        <div class="management-layout">
            <section class="panel panel-pad">
                <h2>QRIS per grup</h2>
                <p>Cabang 01 & 02 memakai Grup A; Cabang 03 & 04 memakai Grup B. Cabang baru dapat mengikuti grup yang
                    dipilih.</p>
                <div class="qris-grid">
                    @foreach ($rows as $g)
                        <article>
                            <h3>{{ $g->group_name }}</h3>
                            @if ($g->latestVersion)
                                <img src="{{ $g->latestVersion->media->url }}" class="payment-qr"
                                    alt="QRIS {{ $g->group_name }}">
                            <p>Versi {{ $g->latestVersion->version }}</p>@else<div class="notice warn">Belum
                                    dipasang.</div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
            <form wire:submit="saveQris" class="panel panel-pad">
                <h2>Ganti QRIS grup</h2><label>Grup<select class="field" wire:model="qrisId">
                        @foreach ($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->group_name }}</option>
                        @endforeach
                    </select>
                </label><label>Gambar QRIS<input class="field" type="file" wire:model="file"
                        accept="image/jpeg,image/png,image/webp" required></label>
                <div class="notice">QRIS versi baru dipakai booking baru. Pesanan yang sudah punya QRIS tetap mengacu
                    pada versi saat booking.</div><button class="btn btn-lime" type="submit"
                    wire:loading.attr="disabled">Terbitkan QRIS baru ↗</button>
            </form>
        </div>
    @elseif($tab === 'permissions')
        <div class="management-layout">
            <section class="panel panel-pad">
                <h2>Pilih peran</h2>
                @foreach ($rows as $role)
                    <button class="row-selector"
                        wire:click="selectRole({{ $role->id }})"><strong>{{ $role->name }}</strong><small>{{ $role->permissions->count() }}
                            izin aktif</small></button>
                @endforeach
            </section>
            <form wire:submit="savePermissions" class="panel panel-pad">
                <h2>Izin sesuai batas peran</h2>
                <p>Admin tetap dibatasi cabang; teknisi dan kurir tetap dibatasi penugasan. Izin tidak memindahkan
                    kewenangan antarperan.</p>@php($selected = $rows->firstWhere('id', $roleId))@if ($selected)@php($allowed = ['admin' => ['orders.view', 'orders.manage', 'payments.review', 'orders.cancel', 'inspections.manage', 'receipts.manage', 'couriers.assign', 'technicians.assign', 'accounts', 'promos', 'reports'], 'tech' => ['orders.view', 'treatment.work'], 'qc' => ['orders.view', 'qc.review'], 'courier' => ['orders.view', 'courier.work']][$selected->code])@foreach (\App\Models\Permission::whereIn('code', $allowed)->get() as $p)
                        <label class="check-label"><input type="checkbox" wire:model="codes"
                                value="{{ $p->code }}"> {{ $p->name }}</label>
                    @endforeach
                    <button class="btn btn-lime" type="submit">
                    Simpan izin</button>@else<div class="notice">Pilih peran untuk melihat izin.</div>
                @endif
            </form>
        </div>
    @elseif($tab === 'reports')
        <section class="panel panel-pad">
            <div class="filter-bar"><label>Dari (WIB)<input class="field" type="date" wire:model.change="from"
                        required></label><label>Sampai (WIB)<input class="field" type="date"
                        wire:model.change="to" required></label><a
                    href="{{ route('staff.export', ['from' => $from, 'to' => $to]) }}" class="btn btn-dark">Unduh CSV
                    ↗</a>
            </div>
            <div class="metrics-grid">
                <div class="metric"><span>Pembayaran terverifikasi</span><strong>{{ $sales['count'] }}</strong></div>
                <div class="metric"><span>Total
                        terverifikasi</span><strong>{{ \App\Support\Puro::money($sales['total']) }}</strong></div>
            </div>
            <p class="muted small">Tagihan belum dibayar dan bukti ditolak tidak masuk penjualan. Biaya jemput saja
                ditandai LOGISTICS. Daftar menampilkan 100 transaksi terakhir; CSV memuat seluruh rentang.</p>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu verifikasi WIB</th>
                            <th>Cabang / Layanan</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales['rows'] as $r)
                            <tr>
                                <td>{{ \App\Support\Puro::date($r->verified_at) }}</td>
                                <td>{{ $r->name }}<br>{{ $r->display_name }}</td>
                                <td>{{ $r->kind }}</td>
                                <td>{{ \App\Support\Puro::STATES[$r->status] }}</td>
                                <td>{{ \App\Support\Puro::money($r->total) }}</td>
                        </tr>@empty<tr>
                                <td colspan="5">Belum ada pembayaran terverifikasi pada rentang ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @elseif($tab === 'knowledge')
        <div class="management-layout">
            <section class="panel panel-pad">
                <h2>Sumber jawaban customer</h2>
                <p>Dokumen aktif dipakai chatbot dan tersedia di panduan customer. Unggah informasi layanan yang boleh
                    dibaca customer.</p>
                @foreach ($rows as $doc)
                    <article class="management-row">
                        <div><strong>{{ ucfirst($doc->title) }}</strong>
                            <p>{{ $doc->chunks_count }} chunk · {{ $doc->indexed_count }} embedding siap</p><span
                                class="pill {{ $doc->active ? 'green' : 'neutral' }}">{{ $doc->active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div><button class="btn btn-ghost btn-sm"
                            wire:click="toggleDocument({{ $doc->id }})">{{ $doc->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </article>
                @endforeach
                <div class="notice">
                    Model jawaban: {{ config('puro.chat_model') }}<br>Model embedding:
                    {{ config('puro.embedding_model') }}<br>API key:
                    {{ config('puro.gemini_key') ? 'Terisi di server' : 'Belum diatur di .env' }}</div>
            </section>
            <section class="panel panel-pad">
                <h2>Perbarui panduan</h2>
                <form wire:submit="uploadKnowledge"><label>Dokumen MD, TXT atau PDF teks (maks. 10 MB)<input
                            type="file" class="field" wire:model="file" accept=".md,.txt,.pdf" required></label>
                    <p class="small muted">PDF scan perlu di-OCR dahulu. Dokumen dipecah menjadi potongan informasi
                        yang dapat dicari.</p><button class="btn btn-lime" type="submit"
                        wire:loading.attr="disabled">Unggah & baca dokumen</button>
                </form>
                <div class="rule form-subhead"></div>
                <h3>Indeks RAG</h3>
                <p>Isi GEMINI_API_KEY di .env, lalu buat embedding agar pertanyaan dapat dicocokkan dengan panduan.
                    Proses ini memakai API Gemini.</p>
                <div class="inline-actions"><button class="btn btn-ghost" wire:click="sync(false)"
                        wire:loading.attr="disabled">Baca ulang folder</button><button class="btn btn-dark"
                        wire:click="sync(true)" wire:loading.attr="disabled">Perbarui embedding ↗</button></div>
                <p class="small muted">Untuk dokumen besar gunakan terminal: php artisan puro:knowledge-sync --embed
                </p>
                @if ($indexMessage)
                    <div class="notice success">{{ $indexMessage }}</div>
                @endif
                <a href="{{ route('guide') }}" wire:navigate class="inline-link">Tinjau panduan customer ↗</a>
            </section>
        </div>
    @endif
</div>
