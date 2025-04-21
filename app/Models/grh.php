<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class grh extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'grhs';

    protected $fillable = [
        'user_id',
        'biometric_id',
        'salaire',
        'shift_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function demandeCongesTraitees()
    {
        return $this->hasMany(demande_conge::class, 'grh_id');
    }

    public function reclamationsTraitees()
    {
        return $this->hasMany(reclamations::class, 'grh_id');
    }
}
