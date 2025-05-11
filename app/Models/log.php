<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class log extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'level',
        'action',
        'message',
        'context',
        'device_ip',
        'device_port',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Crée un log pour une action ZKTeco
     */
    public static function logZKTecoAction(
        string $action,
        string $message,
        string $level = 'info',
        ?array $context = null,
        ?string $deviceIp = null,
        ?string $devicePort = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'level' => $level,
            'action' => $action,
            'message' => $message,
            'context' => $context,
            'device_ip' => $deviceIp,
            'device_port' => $devicePort,
        ]);
    }

    /**
     * Crée un log pour une erreur ZKTeco
     */
    public static function logZKTecoError(
        string $action,
        string $message,
        ?array $context = null,
        ?string $deviceIp = null,
        ?string $devicePort = null
    ): self {
        return self::logZKTecoAction($action, $message, 'error', $context, $deviceIp, $devicePort);
    }

    /**
     * Crée un log pour un avertissement ZKTeco
     */
    public static function logZKTecoWarning(
        string $action,
        string $message,
        ?array $context = null,
        ?string $deviceIp = null,
        ?string $devicePort = null
    ): self {
        return self::logZKTecoAction($action, $message, 'warning', $context, $deviceIp, $devicePort);
    }
}
