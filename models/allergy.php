<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class allergy extends Model
{
    protected $primaryKey = 'allergyID';
    public $timestamps = false;

    protected $fillable = [
        'drugID', 'description', 'customerID'
    ];

    public function drug()
    {
        return $this->belongsTo(Drug::class, 'drugID');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID');
    }
}
