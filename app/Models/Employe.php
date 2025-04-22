<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Employe extends Model
{
    use HasFactory, HasApiTokens;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
}

