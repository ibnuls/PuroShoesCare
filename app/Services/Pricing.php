<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\PromotionVersion;
use App\Models\ShippingPolicy;
use App\Models\TariffVersion;
use Illuminate\Validation\ValidationException;

class Pricing
{
    public function pickup(string $mode, ?float $distance, ShippingPolicy $policy): ?int
    {
        if (! in_array($mode, ['BOTH', 'DELIVERY', 'DROPOFF'])) {
            throw ValidationException::withMessages(['mode' => 'Metode tidak valid.']);
        }
        if ($mode !== 'BOTH') {
            return 0;
        }
        if ($distance === null) {
            return null;
        }
        if ($distance < 0 || $distance > 200) {
            throw ValidationException::withMessages(['distance' => 'Jarak harus 0-200 km.']);
        }

        return (int) ceil(max(0, round($distance, 3) - $policy->free_km - 1e-9)) * $policy->rate_per_km;
    }

    public function discount(int $subtotal, ?PromotionVersion $promo): int
    {
        return $promo ? min($promo->cap_amount, (int) round($subtotal * $promo->percent / 100)) : 0;
    }

    public function promotion(string $code, int $branch): ?PromotionVersion
    {
        if (trim($code) === '') {
            return null;
        }
        $p = Promotion::with('latestVersion')->where('code', mb_strtoupper(trim($code)))->where('active', true)->whereNull('archived_at')->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branch))->first();
        $v = $p?->latestVersion;

        return $v && (! $v->starts_at || $v->starts_at->lte(now())) && (! $v->ends_at || $v->ends_at->gte(now())) ? $v : null;
    }

    public function estimate(TariffVersion $tariff, int $quantity, bool $express, string $mode, ?float $distance, ShippingPolicy $policy, ?PromotionVersion $promo): array
    {
        if ($quantity < 1 || $quantity > 20) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah barang harus 1-20.']);
        }
        if ($express && ! $tariff->express_available) {
            throw ValidationException::withMessages(['express' => 'One Day tidak tersedia untuk layanan ini.']);
        }
        $fee = $this->pickup($mode, $distance, $policy);
        $extra = $express ? (int) $tariff->express_price * $quantity : 0;
        $min = $tariff->min_price === null ? null : $tariff->min_price * $quantity + $extra;
        $max = $tariff->max_price === null ? null : $tariff->max_price * $quantity + $extra;

        return ['minimum' => $min, 'maximum' => $max, 'pickup' => $fee, 'discountMin' => $min === null ? null : $this->discount($min, $promo), 'totalMin' => $min === null || $fee === null ? null : $min - $this->discount($min, $promo) + $fee, 'totalMax' => $max === null || $fee === null ? null : $max - $this->discount($max, $promo) + $fee, 'needsAssessment' => $tariff->needsAssessment($express)];
    }

    public function distance(float $a, float $b, float $c, float $d): float
    {
        if (abs($a) > 90 || abs($c) > 90 || abs($b) > 180 || abs($d) > 180) {
            throw ValidationException::withMessages(['distance' => 'Koordinat tidak valid.']);
        }
        $v = sin(deg2rad($c - $a) / 2) ** 2 + cos(deg2rad($a)) * cos(deg2rad($c)) * sin(deg2rad($d - $b) / 2) ** 2;

        return round(6371 * 2 * atan2(sqrt($v), sqrt(max(0, 1 - $v))), 2);
    }
}
