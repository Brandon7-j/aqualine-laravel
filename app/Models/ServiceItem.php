<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceItem extends Model
{
    protected $table = 'service_items';
    protected $primaryKey = 'service_id';
    public $timestamps = false;

    protected $fillable = [
        'service_name',
        'unit',
        'unit_price',
        'is_active',
        'defined_by',
    ];
}