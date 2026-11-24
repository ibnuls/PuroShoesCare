<?php

namespace App\Http\Controllers;

use App\Services\OrderAccess;
use Illuminate\Support\Facades\Storage;

class DeliveryProofController extends Controller
{
    public function __invoke(string $code, OrderAccess $access)
    {
        $order = $access->guest($code);
        abort_unless($order->status === 'COMPLETE', 404);
        $media = $order->returnDelivery?->proof;
        abort_unless(
            $media && $media->visibility === 'PRIVATE'
                && in_array($media->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)
                && Storage::disk('local')->exists($media->storage_key),
            404,
        );

        return Storage::disk('local')->response($media->storage_key, null, [
            'Cache-Control' => 'no-store, private',
            'Content-Type' => $media->mime_type,
        ]);
    }
}
