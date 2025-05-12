<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointageBiometrique extends Model
{
    protected $table = 'pointages_biometriques';
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'user_type', 'user_id');
    }
    
    public function dispositif()
    {
        return $this->belongsTo(Dispositif_biometrique::class, 'dispositif_id');
    }
}