<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class PdfDownload
{
    public function view(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $fontCache = storage_path('framework/cache/dompdf');
        File::ensureDirectoryExists($fontCache);
        $pdf = new Dompdf(new Options([
            'defaultFont' => 'DejaVu Sans',
            'fontCache' => $fontCache,
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
        ]));
        $pdf->setPaper('A4', $orientation);
        $pdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $pdf->render();

        $canvas = $pdf->getCanvas();
        $canvas->page_text($canvas->get_width() - 90, $canvas->get_height() - 30, '{PAGE_NUM} / {PAGE_COUNT}', $pdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [0.32, 0.34, 0.31]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
