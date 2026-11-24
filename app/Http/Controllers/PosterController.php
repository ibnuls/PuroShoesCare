<?php

namespace App\Http\Controllers;

use App\Models\TariffVersion;
use App\Support\Puro;
use Illuminate\Support\Facades\Storage;

class PosterController extends Controller
{
    public function __invoke(TariffVersion $tariff)
    {
        $code = $tariff->service->code;
        if ($tariff->media_hidden) {
            abort(404);
        }
        if ($tariff->poster_source === 'CUSTOM' && $tariff->poster) {
            return redirect($tariff->poster->url);
        }
        if ($stock = Puro::stockPoster($code, $tariff)) {
            return redirect($stock);
        }

        $image = null;
        if ($tariff->cover && $tariff->cover->visibility === 'PUBLIC') {
            $contents = Storage::disk('public')->get($tariff->cover->storage_key);
            if ($contents) {
                $image = 'data:'.$tariff->cover->mime_type.';base64,'.base64_encode($contents);
            }
        } elseif ($photo = Puro::photo($code)) {
            $image = 'data:image/jpeg;base64,'.base64_encode(file_get_contents(public_path('assets/service-photos/'.basename($photo))));
        }

        $title = $this->wrap(mb_strtoupper($tariff->display_name), 23);
        $description = $this->wrap($tariff->description, 59);
        $terms = $this->wrap(($tariff->pricing_mode === 'QUOTE' ? Puro::tariffPrice($tariff) : Puro::tariffPrice($tariff).' / '.$tariff->unit).' · Estimasi '.$tariff->duration_text, 62);
        $notes = $this->wrap($tariff->customer_note ?? '', 76);
        $imageY = 50 + count($title) * 70;
        $bodyY = $imageY + ($image ? 540 : 0) + 44;
        $termsY = $bodyY + count($description) * 40 + 16;
        $noteY = $termsY + count($terms) * 36 + 20;
        $height = $noteY + count($notes) * 30 + 42;

        return response()->view('public.service-poster', compact('title', 'image', 'description', 'terms', 'notes', 'height', 'imageY', 'bodyY', 'termsY', 'noteY'))
            ->header('Content-Type', 'image/svg+xml; charset=UTF-8')->header('Cache-Control', 'public, max-age=3600');
    }

    private function wrap(string $value, int $width): array
    {
        $text = preg_replace('/\s+/u', ' ', trim($value));

        if ($text === '') {
            return [];
        }
        $lines = [];
        $line = '';
        foreach (explode(' ', $text) as $word) {
            if ($line !== '' && mb_strlen($line.' '.$word) > $width) {
                $lines[] = $line;
                $line = '';
            }
            $line .= ($line === '' ? '' : ' ').$word;
        }
        $lines[] = $line;

        return $lines;
    }
}
