<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;

class Order extends Record
{
    public $timestamps = true;

    protected $hidden = ['access_code_ciphertext', 'access_code_hash', 'customer_name', 'customer_phone', 'customer_address'];

    protected function casts(): array
    {
        return ['express_requested' => 'boolean', 'distance_km' => 'float', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tariff()
    {
        return $this->belongsTo(TariffVersion::class, 'tariff_version_id');
    }

    public function shippingPolicy()
    {
        return $this->belongsTo(ShippingPolicy::class, 'shipping_policy_version_id');
    }

    public function promotionVersion()
    {
        return $this->belongsTo(PromotionVersion::class, 'promotion_version_id');
    }

    public function qrisVersion()
    {
        return $this->belongsTo(QrisAccountVersion::class, 'qris_version_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class)->orderBy('item_no');
    }

    public function quotes()
    {
        return $this->hasMany(PriceQuote::class)->orderBy('version');
    }

    public function currentQuote()
    {
        return $this->hasOne(PriceQuote::class)->ofMany('version', 'max');
    }

    public function verifiedPayment()
    {
        return $this->hasOne(VerifiedPayment::class);
    }

    public function receipt()
    {
        return $this->hasOne(OrderReceipt::class);
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class)->orderBy('request_no');
    }

    public function latestInspection()
    {
        return $this->hasOne(Inspection::class)->ofMany('request_no', 'max');
    }

    public function tasks()
    {
        return $this->hasMany(CourierTask::class);
    }

    public function returnDelivery()
    {
        return $this->hasOne(CourierTask::class)
            ->where('task_type', 'RETURN')
            ->where('status', 'COMPLETED')
            ->whereNotNull('proof_media_id');
    }

    public function treatments()
    {
        return $this->hasMany(TreatmentLog::class)->orderBy('cycle_no');
    }

    public function latestTreatment()
    {
        return $this->hasOne(TreatmentLog::class)->ofMany('cycle_no', 'max');
    }

    public function cancellation()
    {
        return $this->hasOne(OrderCancellation::class);
    }

    public function history()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('sequence_no');
    }

    public function getAccessCodeAttribute(): string
    {
        return Crypt::decryptString($this->access_code_ciphertext);
    }

    public function treatmentPaid(): bool
    {
        return $this->verifiedPayment?->submission?->quote?->kind !== 'LOGISTICS' && $this->verifiedPayment()->exists();
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['CANCELLED', 'COMPLETE']);
    }

    public function total(): ?int
    {
        return $this->currentQuote?->totals()['total'];
    }
}
