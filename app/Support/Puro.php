<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Promotion;
use App\Models\TariffVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Puro
{
    public const STATES = [
        'WAITING_ASSESSMENT' => 'Menunggu penilaian', 'NEEDS_INSPECTION' => 'Perlu pemeriksaan langsung', 'WAITING_APPROVAL' => 'Menunggu persetujuan harga',
        'WAITING_PAYMENT' => 'Menunggu pembayaran', 'QUOTE_REJECTED' => 'Penawaran ditolak', 'PAYMENT_REJECTED' => 'Bukti ditolak - kirim ulang', 'PENDING_PAYMENT' => 'Menunggu verifikasi admin',
        'WAITING_PICKUP' => 'Menunggu penjemputan', 'PICKING_UP' => 'Sedang penjemputan', 'WAITING_HANDOVER' => 'Menunggu barang diantar ke cabang', 'WAITING_TECH' => 'Menunggu teknisi',
        'CLEANING' => 'Sedang dirawat', 'WAITING_QC' => 'Quality checking', 'REWORK' => 'Pengerjaan ulang', 'READY_DELIVERY' => 'Siap diantar', 'OUT_FOR_DELIVERY' => 'Dalam pengantaran',
        'READY_COLLECTION' => 'Siap diambil', 'COMPLETE' => 'Pesanan selesai', 'CANCELLED' => 'Pesanan dibatalkan'];

    public const MODES = ['BOTH' => 'Jemput + antar', 'DELIVERY' => 'Antar kembali saja', 'DROPOFF' => 'Antar & ambil sendiri'];

    public const ROLES = ['superadmin' => 'Super Admin', 'admin' => 'Admin Cabang', 'tech' => 'Teknisi', 'qc' => 'Quality Control', 'courier' => 'Kurir'];

    public static function money($value): string
    {
        return $value === null ? 'Menunggu penilaian' : 'Rp'.number_format((int) $value, 0, ',', '.');
    }

    public static function date($value): string
    {
        return $value ? Carbon::parse($value)->timezone(config('puro.timezone'))->translatedFormat('d M Y, H:i') : '-';
    }

    public static function color(string $status): string
    {
        return match (true) {
            $status === 'CANCELLED' => 'red',in_array($status, ['COMPLETE', 'READY_DELIVERY', 'READY_COLLECTION']) => 'green',in_array($status, ['WAITING_APPROVAL', 'WAITING_TECH', 'CLEANING', 'OUT_FOR_DELIVERY', 'PICKING_UP']) => 'blue',default => 'amber'
        };
    }

    public static function tariffPrice(TariffVersion $tariff): string
    {
        return match ($tariff->pricing_mode) {
            'QUOTE' => 'Harga setelah penilaian',
            'RANGE' => self::money($tariff->min_price).'–'.self::money($tariff->max_price),
            default => self::money($tariff->min_price),
        };
    }

    public static function photo(string $code): ?string
    {
        $map = ['fast' => 'shoe-2', 'women-kids' => 'white-shoe', 'deep' => 'shoe-1', 'extra' => 'shoe-2', 'leather' => 'shoe-4', 'white' => 'white-shoe', 'unyellow' => 'shoe-3', 'repaint' => 'shoe-5', 'reglue' => 'shoe-6', 'clutch' => 'clutch', 'shoulder' => 'shoulder', 'backpack' => 'backpack', 'half-face' => 'half-face', 'full-face' => 'full-face', 'coating' => 'shoe-5', 'helmet-care' => 'full-face', 'cap-hat' => 'cap-hat'];

        return isset($map[$code]) ? asset('assets/service-photos/'.$map[$code].'.jpg') : null;
    }

    public static function catalogueEntry(string $code): ?array
    {
        static $entries;
        $entries ??= array_column(json_decode(file_get_contents(database_path('seeders/catalogue.json')), true)['services'], null, 'id');

        return $entries[$code] ?? null;
    }

    public static function catalogueCopy(string $code, TariffVersion $tariff): string
    {
        $original = self::catalogueEntry($code);

        return $original && $original['details'] === $tariff->description
            ? $original['desc'] : Str::limit($tariff->description, 95);
    }

    public static function serviceCover(string $code, TariffVersion $tariff): ?string
    {
        return $tariff->media_hidden ? null : ($tariff->poster?->url ?? $tariff->cover?->url ?? self::photo($code));
    }

    public static function stockPoster(string $code, TariffVersion $tariff): ?string
    {
        $original = self::catalogueEntry($code);
        if (! $original || $tariff->media_hidden || $tariff->poster_source !== 'AUTO' || $tariff->cover_media_id) {
            return null;
        }
        if ($tariff->display_name === $original['name'] && $tariff->description === $original['details']
            && $tariff->customer_note === $original['note'] && $tariff->min_price === $original['minPrice']
            && $tariff->max_price === $original['maxPrice'] && $tariff->unit === $original['unit']
            && $tariff->duration_text === $original['duration']) {
            return asset('assets/service-posters/'.$code.'.png');
        }

        return null;
    }

    public static function branchCover(Branch $branch): ?string
    {
        return $branch->media_hidden ? null : ($branch->cover?->url ?? asset('assets/branch-'.(($branch->id - 1) % 4 + 1).'.jpg'));
    }

    public static function promoCover(Promotion $promo): ?string
    {
        return $promo->media_hidden ? null : ($promo->cover?->url ?? asset('assets/hero-puro-sneaker.jpg'));
    }

    public static function whatsapp(string $number, string $message): ?string
    {
        $number = preg_replace('/\D/', '', $number);
        if (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        }

        return preg_match('/^[1-9][0-9]{8,14}$/', $number) ? 'https://wa.me/'.$number.'?text='.rawurlencode($message) : null;
    }

    public static function reference(string $value): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '', trim($value)));
    }
}
