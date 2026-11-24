<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeDocument;
use App\Services\PdfDownload;

class GuideController extends Controller
{
    public function __invoke(PdfDownload $pdf)
    {
        $documents = KnowledgeDocument::where('active', true)->with('chunks')->get();

        return $pdf->view('reports.customer-guide-pdf', compact('documents'), 'Panduan_Pelanggan_PURO.pdf');
    }
}
