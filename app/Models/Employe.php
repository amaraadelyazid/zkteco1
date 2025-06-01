<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Employe extends Authenticatable implements FilamentUser
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'prenom',
        'biometric_id',
        'salaire',
        'departement_id',
        'shift_id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function demandeConges()
    {
        return $this->hasMany(demande_conge::class);
    }

    public function reclamations()
    {
        return $this->hasMany(reclamations::class);
    }

    public function presences()
    {
        return $this->hasMany(Presence::class);
    }

    public function ficheDePaies()
    {
        return $this->hasMany(fiche_de_paie::class);
    }

    public function primes()
    {
        return $this->morphMany(Prime::class, 'user');
    }

    public function avances()
    {
        return $this->morphMany(Avance::class, 'user');
    }
    
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
