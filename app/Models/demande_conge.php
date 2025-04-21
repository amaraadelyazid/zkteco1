<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class demande_conge extends Model
{
    use HasFactory;

    protected $fillable = [
        'employe_id',
        'type',
        'message',
        'photo',
        'status',
        'reponse',
        'grh_id',
        'date_demande',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'date_demande' => 'datetime',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function grh()
    {
        return $this->belongsTo(GRH::class, 'grh_id');
    }
}
