<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\StaffUser;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Reports
{
    public function query(StaffUser $u, string $from, string $to, ?int $branch = null): Builder
    {
        $u = $u->fresh();
        abort_unless($u && $u->allows('reports') && ($u->isRole('superadmin') || $u->isRole('admin')), 403);
        abort_unless($u->isRole('superadmin') || $branch === null || $branch === $u->branch_id, 403);
        Validator::make(compact('from', 'to', 'branch'), [
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            'branch' => 'nullable|exists:branches,id',
        ])->validate();
        [$a, $b] = $this->range($from, $to);
        $branch = $u->isRole('superadmin') ? $branch : $u->branch_id;

        return DB::table('verified_sales as v')
            ->join('orders as o', 'o.id', '=', 'v.order_id')
            ->join('branches as b', 'b.id', '=', 'o.branch_id')
            ->join('service_tariff_versions as t', 't.id', '=', 'o.tariff_version_id')
            ->join('payment_submissions as p', 'p.id', '=', 'v.submission_id')
            ->join('price_quotes as q', 'q.id', '=', 'p.quote_id')
            ->where('v.verified_at', '>=', $a)
            ->where('v.verified_at', '<', $b)
            ->when($branch !== null, fn ($q) => $q->where('o.branch_id', $branch));
    }

    public function rows(Builder $query): Builder
    {
        return $query->select(['o.id', 'b.name as branch', 't.display_name', 'q.kind', 'o.status', 'v.total', 'v.verified_at'])
            ->orderByDesc('v.verified_at')->orderByDesc('o.id');
    }

    private function range(string $from, string $to): array
    {
        $tz = config('puro.timezone');

        return [
            Carbon::parse($from, $tz)->startOfDay()->utc(),
            Carbon::parse($to, $tz)->startOfDay()->addDay()->utc(),
        ];
    }

    public function firstDay(StaffUser $user): string
    {
        $date = DB::table('orders')->when(! $user->isRole('superadmin'), fn ($query) => $query->where('branch_id', $user->branch_id))->min('created_at');

        return $date ? Carbon::parse($date)->timezone(config('puro.timezone'))->toDateString() : now(config('puro.timezone'))->toDateString();
    }

    public function analytics(StaffUser $user, string $from, string $to, ?int $branch): array
    {
        $paid = $this->query($user, $from, $to, $branch);
        $allPaid = $branch === null ? clone $paid : $this->query($user, $from, $to);
        [$a, $b] = $this->range($from, $to);
        $branches = Branch::query()->when(! $user->isRole('superadmin'), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')->get(['id', 'name', 'area']);
        $counts = DB::table('orders as o')
            ->whereIn('o.branch_id', $branches->pluck('id'))
            ->where('o.created_at', '>=', $a)->where('o.created_at', '<', $b)
            ->selectRaw("o.branch_id, count(*) as orders, count(*) filter (where o.status = 'PENDING_PAYMENT') as pending")
            ->groupBy('o.branch_id')->get()->keyBy('branch_id');
        $revenue = $allPaid
            ->selectRaw('o.branch_id, count(v.order_id) as paid, sum(v.total) as total')
            ->groupBy('o.branch_id')->get()->keyBy('branch_id');
        $byBranch = $branches->map(function (Branch $branch) use ($counts, $revenue) {
            $row = $counts->get($branch->id);
            $income = $revenue->get($branch->id);

            return [
                'id' => $branch->id, 'name' => $branch->name, 'area' => $branch->area,
                'orders' => (int) ($row->orders ?? 0), 'count' => (int) ($income->paid ?? 0),
                'pending' => (int) ($row->pending ?? 0), 'total' => (int) ($income->total ?? 0),
            ];
        });
        $selected = $branch === null ? $byBranch : $byBranch->where('id', $branch);
        $ending = Carbon::parse($to, config('puro.timezone'));

        return [
            'total' => (int) $selected->sum('total'),
            'count' => (int) $selected->sum('count'),
            'pending' => (int) $selected->sum('pending'),
            'branches' => $byBranch,
            'weekly' => $this->weekly(clone $paid, $ending),
            'rows' => $this->rows($paid)->limit(100)->get(),
        ];
    }

    public function weekly(Builder $payments, Carbon $ending): array
    {
        $daily = $payments->where('v.verified_at', '>=', $ending->copy()->subDays(6)->startOfDay()->utc())
            ->where('v.verified_at', '<', $ending->copy()->addDay()->startOfDay()->utc())
            ->selectRaw('date(v.verified_at AT TIME ZONE ?) as day, sum(v.total) as total', [config('puro.timezone')])
            ->groupBy('day')->pluck('total', 'day');

        return array_map(function (int $days) use ($ending, $daily) {
            $date = $ending->copy()->subDays($days);

            return ['label' => $date->locale('id')->isoFormat('ddd'), 'total' => (int) ($daily[$date->toDateString()] ?? 0)];
        }, range(6, 0));
    }
}
