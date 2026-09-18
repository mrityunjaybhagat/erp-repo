<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// If you have Sanctum installed (for API token auth), also add:
//   use Laravel\Sanctum\HasApiTokens;
//   use HasApiTokens, HasFactory, Notifiable;
// Left out here since I don't know if it's installed in this project.
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'user_type_id'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['email_verified_at' => 'datetime'];

    public function userType()
    {
        return $this->belongsTo(UserType::class);
    }
}
