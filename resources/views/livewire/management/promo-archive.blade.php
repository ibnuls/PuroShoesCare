<section class="panel panel-pad">
    <div class="management-heading">
        <h2>Arsip promo</h2>
        <span class="pill neutral">{{ $rows->count() }} promo</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Kode</th><th>Cakupan</th><th>Diskon</th><th>Batas potongan</th><th>Diarsipkan (WIB)</th></tr></thead>
            <tbody>
                @forelse ($rows as $promo)
                    <tr wire:key="promo-archive-{{ $promo->id }}">
                        <td><strong>{{ $promo->code }}</strong></td>
                        <td>{{ $promo->branch?->name ?? 'Global' }}</td>
                        <td>{{ $promo->latestVersion?->percent }}%</td>
                        <td>{{ \App\Support\Puro::money($promo->latestVersion?->cap_amount) }}</td>
                        <td>{{ \App\Support\Puro::date($promo->archived_at) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada promo yang diarsipkan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
