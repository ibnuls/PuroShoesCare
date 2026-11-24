<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Panduan Pelanggan PURO</title>
    <style>
        @page { margin: 40px 42px 52px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10.5px; line-height: 1.65; color: #172018; }
        header { background: #e8ff00; padding: 16px 20px; }
        h1 { font-size: 21px; margin: 3px 0; }
        h2 { font-size: 15px; margin: 22px 0 9px; border-bottom: 1px solid #dcded6; padding-bottom: 6px; }
        h3 { font-size: 12px; margin: 15px 0 5px; }
        h2, h3 { page-break-after: avoid; }
        p { margin: 0 0 8px; }
    </style>
</head>
<body>
    <header><strong>PURO SHOES CARE</strong><h1>Panduan Pelanggan</h1>Pesan layanan dan pantau perawatan barangmu.</header>
    @forelse ($documents as $document)
        <h2>{{ ucfirst($document->title) }}</h2>
        @foreach ($document->chunks as $chunk)
            <h3>{{ $chunk->heading }}</h3>
            <p>{!! nl2br(e($chunk->body)) !!}</p>
        @endforeach
    @empty
        <p>Panduan sedang disiapkan. Hubungi admin melalui menu Kontak untuk bantuan pemesanan.</p>
    @endforelse
</body>
</html>
