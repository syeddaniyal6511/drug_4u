<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class invoice extends Model
{
    protected $primaryKey = 'invoiceID';
    public $timestamps = false;

    protected $fillable = [
        'orderID', 'total_amount', 'date_invoice', 'status'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID');
    }
}
