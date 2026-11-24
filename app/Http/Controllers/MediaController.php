<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Models\Order;
use App\Services\OrderAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function show(MediaAsset $media, OrderAccess $access)
    {
        $u = Auth::user()?->fresh();
        abort_unless($u?->active, 403);
        if ($media->visibility === 'PUBLIC') {
            return redirect($media->url);
        }
        $orders = Order::whereHas('items.photos', fn ($q) => $q->where('media_asset_id', $media->id))->orWhereHas('treatments.photos', fn ($q) => $q->where('media_asset_id', $media->id))->orWhereHas('tasks', fn ($q) => $q->where('proof_media_id', $media->id))->orWhereHas('receipt', fn ($q) => $q->where('proof_media_id', $media->id));
        $owner = $access->scope(Order::query()->whereIn('id', $orders->select('orders.id')), $u)->exists();
        $payment = Order::whereHas('quotes.submissions', fn ($q) => $q->where('proof_media_id', $media->id));
        $isPayment = $payment->exists();
        if ($isPayment) {
            $owner = $u->isRole('admin') && $u->allows('payments.review') && $payment->where('branch_id', $u->branch_id)->exists();
        }
        abort_unless($owner, 403);
        abort_unless(Storage::disk('local')->exists($media->storage_key), 404);

        return Storage::disk('local')->response($media->storage_key, null, ['Cache-Control' => 'no-store, private', 'Content-Type' => $media->mime_type]);
    }
}
