<?php

namespace App\Models;

use illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class reclamations extends Model
{
    use HasFactory;

    protected $fillable = [
        'employe_id',
        'message',
        'statut',
        'reponse',
        'grh_id',
        'date_reclamation',
    ];

    protected $casts = [
        'date_reclamation' => 'datetime',
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
