<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class fiche_de_paie extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'mois',
        'montant',
        'prime',
        'avance',
        'heures_sup',
        'taux_horaire_sup',
        'montant_heures_sup',
        'status',
        'date_generation',
    ];

    protected $casts = [
        'date_generation' => 'datetime',
    ];

    public function user()
    {
        return $this->morphTo(__FUNCTION__, 'user_type', 'user_id');
    }
}
