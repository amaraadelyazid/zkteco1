<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Presence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'name', // Added for mass assignment
        'prenom', // Added for mass assignment
        'date',
        'check_in',
        'etat_check_in',
        'check_out',
        'etat_check_out',
        'heures_travaillees',
        'anomalie_type',
        'anomalie_resolue',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'anomalie_resolue' => 'boolean',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'user_type', 'user_id');
    }
}