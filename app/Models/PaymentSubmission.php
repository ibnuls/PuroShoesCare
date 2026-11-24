<?php

namespace App\Models;

class PaymentSubmission extends Record
{
    protected $table = 'payment_submissions';

    public function quote()
    {
        return $this->belongsTo(PriceQuote::class, 'quote_id');
    }

    public function reference()
    {
        return $this->belongsTo(PaymentReference::class, 'reference_id');
    }

    public function proof()
    {
        return $this->belongsTo(MediaAsset::class, 'proof_media_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(StaffUser::class, 'reviewed_by');
    }

    protected function casts(): array
    {
        return ['claimed_amount' => 'integer', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }
}
