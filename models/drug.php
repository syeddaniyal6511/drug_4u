<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Drug extends Model
{
    protected $table = 'drug';
    protected $primaryKey = 'drugID';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'basic_unit',
        'collective_unit',
        'no_of_basic_units_in_collective_unit',
        'age_limit',
    ];

    protected $casts = [
        'basic_unit' => 'integer',
        'collective_unit' => 'integer',
        'no_of_basic_units_in_collective_unit' => 'decimal:2',
        'age_limit' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    | Add relationships here when needed, for example:
    | - A Drug may belong to a DrugCategory
    | - A Drug may have many Prescriptions
    */

    // Example:
    // public function prescriptions()
    // {
    //     return $this->hasMany(Prescription::class, 'drugID');
    // }
}
?>