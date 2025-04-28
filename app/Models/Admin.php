<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Admin  extends Authenticatable implements FilamentUser
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'biometric_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;

    }
}
