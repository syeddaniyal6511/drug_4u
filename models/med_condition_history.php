<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class med_condition_history extends Model
{
    protected $table = 'med_condition_history';
    protected $primaryKey = 'historyID';
    public $timestamps = false;

    protected $fillable = [
        'description', 'customerID'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID');
    }
}
