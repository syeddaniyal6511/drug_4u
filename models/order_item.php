<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class order_item extends Model
{
    protected $primaryKey = 'order_itemID';
    public $timestamps = false;

    protected $fillable = [
        'orderID', 'price'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID');
    }
}
