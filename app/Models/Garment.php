<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garment extends Model
{
    protected $table = 'garments';
    protected $primaryKey = 'garment_id';
    public $timestamps = false;

    protected $fillable = [
        'order_no',
        'service_id',
        'tag_no',
        'garment_type',
        'colour',
        'quantity',
        'condition_note',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_no', 'order_no');
    }

    public function service()
    {
        return $this->belongsTo(ServiceItem::class, 'service_id', 'service_id');
    }

    public function serviceItem()
    {
        return $this->belongsTo(ServiceItem::class, 'service_id', 'service_id');
    }
}