<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan PURO</title>
    <style>
        @page { margin: 30pt 32pt 38pt; }
        body { font-family: "DejaVu Sans", sans-serif; color: #151715; font-size: 9pt; }
        .brand { background: #111; color: #e8ff00; font-size: 13pt; font-weight: bold; padding: 11pt 14pt; }
        h1 { margin: 16pt 0 4pt; font-size: 23pt; }
        .meta { color: #454a44; margin: 2pt 0 12pt; }
        .summary { background: #edf6ea; border: 1pt solid #cbdac8; margin: 0 0 14pt; padding: 10pt 13pt; }
        .summary b { color: #186139; font-size: 12pt; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        th { text-align: left; background: #f2f3ee; color: #242923; font-size: 8pt; }
        th, td { padding: 6pt; border-bottom: 0.6pt solid #daddd8; vertical-align: top; overflow-wrap: break-word; }
        .num { text-align: right; }
        .note { margin: 12pt 0 0; color: #454a44; font-size: 8pt; }
    </style>
</head>
<body>
    <div class="brand">PURO SHOES CARE</div>
    <h1>Laporan Penjualan</h1>
    <p class="meta">{{ $scope }} · {{ \App\Support\Puro::date($from.' 00:00:00 +07:00') }} sampai {{ \App\Support\Puro::date($to.' 23:59:59 +07:00') }} WIB</p>
    <div class="summary">{{ $rows->count() }} pembayaran terverifikasi &nbsp;&nbsp; <b>{{ \App\Support\Puro::money($rows->sum('total')) }}</b></div>
    <table>
        <thead>
            <tr>
                <th style="width: 7%">Order</th>
                <th style="width: 18%">Verifikasi (WIB)</th>
                <th style="width: 15%">Cabang</th>
                <th style="width: 20%">Layanan</th>
                <th style="width: 12%">Jenis</th>
                <th style="width: 17%">Status</th>
                <th class="num" style="width: 11%">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>#{{ $row->id }}</td>
                    <td>{{ \App\Support\Puro::date($row->verified_at) }}</td>
                    <td>{{ $row->branch }}</td>
                    <td>{{ $row->display_name }}</td>
                    <td>{{ $row->kind === 'LOGISTICS' ? 'Biaya jemput' : 'Treatment' }}</td>
                    <td>{{ \App\Support\Puro::STATES[$row->status] ?? $row->status }}</td>
                    <td class="num">{{ \App\Support\Puro::money($row->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada pembayaran terverifikasi pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="note">Dicetak {{ \App\Support\Puro::date(now()) }} WIB.</p>
</body>
</html>
