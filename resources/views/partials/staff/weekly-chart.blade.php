@php
    $peak = max(1, max(array_column($weekly, 'total')));
@endphp
<div class="weekly-chart" aria-label="Penjualan harian tujuh hari terakhir">
    @foreach ($weekly as $day)
        <div class="weekly-column">
            <div class="weekly-bar-space"><div class="weekly-bar" aria-hidden="true" title="{{ $day['label'] }}: {{ \App\Support\Puro::money($day['total']) }}" style="height:{{ max(2, $day['total'] / $peak * 100) }}%"></div></div>
            <span>{{ $day['label'] }}<span class="sr-only"> · {{ \App\Support\Puro::money($day['total']) }}</span></span>
        </div>
    @endforeach
</div>
<div class="chart-legend"><span class="legend-swatch" aria-hidden="true"></span> Transaksi yang sudah diverifikasi</div>
