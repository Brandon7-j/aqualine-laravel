<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $table = 'bills';
    protected $primaryKey = 'bill_id';
    public $timestamps = false;

    protected $fillable = [
        'order_no',
        'total_amount',
        'amount_due',
        'amount_paid',
        'balance',
        'payment_status',
        'generated_on',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_no', 'order_no');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'bill_id', 'bill_id');
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class, 'bill_id', 'bill_id');
    }
}