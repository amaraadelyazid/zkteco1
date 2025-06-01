<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prime extends Model
{
    protected $fillable = [
        'user_type',
        'user_id',
        'mois',
        'montant',
        'description',
    ];

    public function user()
    {
        return $this->morphTo();
    }

}
