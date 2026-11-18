<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Reports;
use App\Support\Puro;

class ReportController extends Controller
{
    public function __invoke(Reports $reports)
    {
        $from = request('from', now()->format('Y-m-01'));
        $to = request('to', now()->format('Y-m-d'));
        $q = $reports->query(auth()->user(), $from, $to);

        return response()->streamDownload(function () use ($q) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Kode pesanan', 'Cabang', 'Layanan', 'Jenis tagihan', 'Status', 'Total terverifikasi', 'Waktu verifikasi WIB'], ',', '"', '');
            foreach ($q->select(['o.id', 'b.name as branch', 't.display_name', 'q.kind', 'o.status', 'v.total', 'v.verified_at'])->orderBy('v.verified_at')->cursor() as $row) {
                $code = Order::find($row->id)->access_code;
                $cells = [$code, $row->branch, $row->display_name, $row->kind, $row->status, $row->total, Puro::date($row->verified_at)];
                $cells = array_map(fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : $v, $cells);
                fputcsv($f, $cells, ',', '"', '');
            }
            fclose($f);
        }, 'puro-verified-'.$from.'-'.$to.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }
}
