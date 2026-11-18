<?php

namespace App\Services;

use App\Models\StaffUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Reports
{
    public function query(StaffUser $u, string $from, string $to)
    {
        $u = $u->fresh();
        abort_unless($u && $u->allows('reports') && ($u->isRole('superadmin') || $u->isRole('admin')), 403);
        Validator::make(compact('from', 'to'), ['from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from'])->validate();
        $a = Carbon::createFromFormat('Y-m-d', $from, 'Asia/Jakarta')->startOfDay()->utc();
        $b = Carbon::createFromFormat('Y-m-d', $to, 'Asia/Jakarta')->endOfDay()->utc();

        return DB::table('verified_sales as v')->join('orders as o', 'o.id', '=', 'v.order_id')->join('branches as b', 'b.id', '=', 'o.branch_id')->join('service_tariff_versions as t', 't.id', '=', 'o.tariff_version_id')->join('payment_submissions as p', 'p.id', '=', 'v.submission_id')->join('price_quotes as q', 'q.id', '=', 'p.quote_id')->whereBetween('v.verified_at', [$a, $b])->when(! $u->isRole('superadmin'), fn ($q) => $q->where('o.branch_id', $u->branch_id));
    }
}
