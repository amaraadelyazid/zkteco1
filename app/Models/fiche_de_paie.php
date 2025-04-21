<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class fiche_de_paie extends Model
{
    use HasFactory;

    protected $fillable = [
        'employe_id',
        'mois',
        'montant',
        'avance',
        'heures_sup',
        'primes',
        'status',
        'date_generation',
    ];

    protected $casts = [
        'date_generation' => 'datetime',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }
}
