<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'heure_debut',
        'heure_fin',
        'pause',
        'heure_debut_pause',
        'heure_fin_pause',
        'duree_pause',
        'jours_travail',
        'tolerance_retard',
        'depart_anticipe',
        'duree_min_presence',
        'is_decalable',
        'description',
    ];

    protected $casts = [
        'jours_travail' => 'array',
        'pause' => 'boolean',
        'is_decalable' => 'boolean',
    ];

    public function employes()
    {
        return $this->hasMany(Employe::class);
    }

    public function grhs()
    {
        return $this->hasMany(GRH::class);
    }
}
