<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class order extends Model
{
    protected $table = 'order_';
    protected $primaryKey = 'orderID';
    public $timestamps = false;

    protected $fillable = [
        'status', 'created_at', 'customerID', 'userID'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userID');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'orderID');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'orderID');
    }
}
