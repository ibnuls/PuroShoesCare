<?php

namespace App\Http\Controllers;

use App\Models\TariffVersion;
use App\Support\Puro;

class PosterController extends Controller
{
    public function __invoke(TariffVersion $tariff)
    {
        if ($tariff->poster_source === 'CUSTOM' && $tariff->poster) {
            return redirect($tariff->poster->url);
        }
        $e = fn ($s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $price = $tariff->pricing_mode === 'QUOTE' ? 'Penilaian admin' : Puro::money($tariff->min_price).($tariff->pricing_mode === 'RANGE' ? ' – '.Puro::money($tariff->max_price) : '');
        $name = $e($tariff->display_name);
        $price = $e($price);
        $unit = $e($tariff->unit);
        $duration = $e($tariff->duration_text);
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1000"><rect width="800" height="1000" fill="#111"/><rect x="45" y="45" width="710" height="110" rx="20" fill="#e8ff00"/><text x="80" y="118" font-family="Arial" font-size="50" font-weight="bold">PURO / CARE MENU</text><circle cx="650" cy="420" r="240" fill="#24271c"/><text x="55" y="350" fill="white" font-family="Arial" font-size="38" font-weight="bold">'.$name.'</text><text x="55" y="480" fill="#e8ff00" font-family="Arial" font-size="34">'.$price.'</text><text x="55" y="540" fill="white" font-family="Arial" font-size="24">Per '.$unit.' · '.$duration.'</text><text x="55" y="730" fill="white" font-family="Arial" font-size="24">Pure Care. Pure Solution.</text><text x="55" y="800" fill="#aaa" font-family="Arial" font-size="20">Harga akhir mengikuti jenis tarif dan kondisi barang.</text><text x="55" y="920" fill="#aaa" font-family="Arial" font-size="18">Tarif versi '.$tariff->version.' · PURO</text></svg>';

        return response($svg)->header('Content-Type', 'image/svg+xml')->header('Cache-Control', 'public, max-age=3600');
    }
}
