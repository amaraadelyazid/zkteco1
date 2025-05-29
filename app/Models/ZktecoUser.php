<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZktecoUser extends Model
{
    protected $fillable = [
        'device_id',
        'uid',
        'userid',
        'name',
        'password',
        'role',
        'cardno',
    ];

    protected $casts = [
        'uid' => 'integer',
        'role' => 'integer',
        'cardno' => 'integer',
    ];

    public function device()
    {
        return $this->belongsTo(dispositif_biometrique::class, 'device_id');
    }
} 