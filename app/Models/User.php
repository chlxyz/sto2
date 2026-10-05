<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'display_name',
        'full_name',
        'address',
        'address_lat',
        'address_lng',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Single source of truth for the admin check. Case-insensitive because the OTP
    // flow lowercases emails on create but config('admin.email') may not.
    public function isAdmin(): bool
    {
        return strcasecmp((string) $this->email, (string) config('admin.email')) === 0;
    }

    public function cartItems()
    {
        return $this->belongsToMany(Product::class, 'carts')->withPivot('quantity');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
