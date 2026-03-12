<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class stock extends Model
{
    protected $primaryKey = 'stockID';
    public $timestamps = false;

    protected $fillable = [
        'drugID', 'name', 'quantity', 'batch_number', 'buying_price_per_pack', 'selling_price_per_pack', 'expiry_date'
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class, 'drugID');
    }
}
