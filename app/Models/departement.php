<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class departement extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
    ];

    public function employes()
    {
        return $this->hasMany(Employe::class);
    }
}

