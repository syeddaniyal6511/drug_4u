<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Model
{
    protected $table = 'user_';
    protected $primaryKey = 'userID';
    public $timestamps = false;

    protected $fillable = [
        'firstname', 'lastname', 'dob', 'username', 'pwd', 'role'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'userID');
    }
}
