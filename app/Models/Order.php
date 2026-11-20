<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'order_no';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'staff_id',
        'date_received',
        'expected_ready_date',
        'status',
        'delivery_address',
        'delivery_date',
        'delivery_notes',
        'delivery_status',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'user_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id', 'user_id');
    }

    public function garments()
    {
        return $this->hasMany(Garment::class, 'order_no', 'order_no');
    }

    public function bill()
    {
        return $this->hasOne(Bill::class, 'order_no', 'order_no');
    }
}