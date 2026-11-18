<?php

namespace App\Support;

use Illuminate\Support\Carbon;

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

    public static function photo(string $code): string
    {
        $map = ['fast' => 'shoe-2', 'women-kids' => 'shoe-3', 'deep' => 'shoe-1', 'extra' => 'shoe-4', 'leather' => 'shoe-5', 'white' => 'white-shoe', 'unyellow' => 'shoe-3', 'repaint' => 'shoe-5', 'reglue' => 'shoe-6', 'clutch' => 'clutch', 'shoulder' => 'shoulder', 'backpack' => 'backpack', 'half-face' => 'half-face', 'full-face' => 'full-face', 'coating' => 'shoe-6', 'helmet-care' => 'full-face', 'cap-hat' => 'cap-hat'];

        return asset('assets/service-photos/'.($map[$code] ?? 'shoe-1').'.jpg');
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
