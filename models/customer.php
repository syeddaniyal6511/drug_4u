<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customer';
    protected $primaryKey = 'customerID';
    public $timestamps = false;

    protected $fillable = [
        'firstname',
        'lastname',
        'gender',   // enum('man', 'woman')
        'dob',      // DATE
        'postcode', // BIGINT
    ];

    protected $casts = [
        'dob' => 'date',
        'postcode' => 'string', // safer for BIGINTs / leading-zero postcodes
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | These match the relationships referenced in your other models:
    | - Order::class   belongsTo(Customer::class)
    | - Allergy::class belongsTo(Customer::class)
    | - MedConditionHistory::class belongsTo(Customer::class)
    */

    public function orders()
    {
        return $this->hasMany(Order::class, 'customerID');
    }

    public function allergies()
    {
        return $this->hasMany(Allergy::class, 'customerID');
    }

    public function medConditionHistories()
    {
        return $this->hasMany(MedConditionHistory::class, 'customerID');
    }
}