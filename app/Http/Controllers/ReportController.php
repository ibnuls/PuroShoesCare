<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Services\PdfDownload;
use App\Services\Reports;
use App\Support\Puro;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function filters(Request $request): array
    {
        $data = $request->validate([
            'from' => 'sometimes|required|date_format:Y-m-d',
            'to' => 'sometimes|required|date_format:Y-m-d',
            'branch' => 'nullable|integer|min:1',
        ]);
        $today = now(config('puro.timezone'));

        return [
            $data['from'] ?? $today->format('Y-m-01'),
            $data['to'] ?? $today->format('Y-m-d'),
            isset($data['branch']) ? (int) $data['branch'] : null,
        ];
    }

    public function __invoke(Request $request, Reports $reports)
    {
        [$from, $to, $branch] = $this->filters($request);
        $q = $reports->rows($reports->query($request->user(), $from, $to, $branch))->addSelect('o.access_code_ciphertext');

        return response()->streamDownload(function () use ($q) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Kode pesanan', 'Cabang', 'Layanan', 'Jenis tagihan', 'Status', 'Total terverifikasi', 'Waktu verifikasi WIB'], ',', '"', '');
            foreach ($q->cursor() as $row) {
                $code = (new Order(['access_code_ciphertext' => $row->access_code_ciphertext]))->access_code;
                $cells = [$code, $row->branch, $row->display_name, $row->kind, $row->status, $row->total, Puro::date($row->verified_at)];
                $cells = array_map(fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : $v, $cells);
                fputcsv($f, $cells, ',', '"', '');
            }
            fclose($f);
        }, 'puro-verified-'.$from.'-'.$to.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }

    public function pdf(Request $request, Reports $reports, PdfDownload $pdf)
    {
        [$from, $to, $branch] = $this->filters($request);
        $u = $request->user();
        $rows = $reports->rows($reports->query($u, $from, $to, $branch))->get();
        $scope = $u->isRole('superadmin')
            ? ($branch === null ? 'Seluruh cabang' : Branch::findOrFail($branch)->name)
            : $u->branch->name;

        return $pdf->view('reports.sales-pdf', compact('rows', 'scope', 'from', 'to'), 'puro-laporan-'.$from.'-'.$to.'.pdf', 'landscape');
    }
}
