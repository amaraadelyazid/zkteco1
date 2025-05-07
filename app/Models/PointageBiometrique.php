<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointageBiometrique extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function user()
    {
        return $this->morphTo(__FUNCTION__, 'user_type', 'user_id');
    }
    
    public function dispositif()
    {
        return $this->belongsTo(Dispositif_biometrique::class, 'dispositif_id');
    }
}