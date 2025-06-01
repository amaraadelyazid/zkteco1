<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avance extends Model
{
    protected $fillable = [
        'user_type',
        'user_id',
        'mois',
        'montant',
        'motif',
    ];

    
    public function user()
{
    return $this->morphTo();
}

}
