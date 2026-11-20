<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    public $timestamps = false;

    protected $fillable = [
        'bill_id',
        'bill_no',
        'amount',
        'payment_method',
        'status',
        'phone_number',
        'checkout_request_id',
        'mpesa_receipt_code',
        'received_by',
        'created_at',
        'paid_at',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id', 'bill_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'bill_id', 'bill_id');
    }
}