<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class PriceQuote extends Record
{
    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function lines()
    {
        return $this->hasMany(QuoteItem::class, 'quote_id')->with('item')->orderBy('order_item_id');
    }

    public function submissions()
    {
        return $this->hasMany(PaymentSubmission::class, 'quote_id')->orderBy('attempt_no');
    }

    public function latestSubmission()
    {
        return $this->hasOne(PaymentSubmission::class, 'quote_id')->ofMany('attempt_no', 'max');
    }

    public function totals(): array
    {
        $t = DB::table('quote_totals')->where('quote_id', $this->id)->first();

        return collect((array) $t)->only(['subtotal', 'discount', 'pickup_fee', 'total'])->map(fn ($v) => (int) $v)->all();
    }
}
