<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class presence extends Model
{
    use HasFactory;

    protected $fillable = [
        'employe_id',
        'timestamp',
        'type',
        'methode',
        'is_anomalie',
        'etat',
        'jour',
        'dispositif_id',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'jour' => 'date',
        'is_anomalie' => 'boolean',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function dispositif()
    {
        return $this->belongsTo(Dispositif_biometrique::class, 'dispositif_id');
    }
}
