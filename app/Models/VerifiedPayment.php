<?php

namespace App\Models;

class VerifiedPayment extends Record
{
    protected $table = 'verified_payments';

    public $incrementing = false;

    protected $primaryKey = 'order_id';

    public function submission()
    {
        return $this->belongsTo(PaymentSubmission::class, 'submission_id');
    }
}
