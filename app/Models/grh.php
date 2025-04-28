<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class grh extends Authenticatable implements FilamentUser
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'prenom',
        'email',
        'password',
        'biometric_id',
        'salaire',
        'shift_id',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function demandeCongesTraitees()
    {
        return $this->hasMany(demande_conge::class, 'grh_id');
    }

    public function reclamationsTraitees()
    {
        return $this->hasMany(reclamations::class, 'grh_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;

    }
}
