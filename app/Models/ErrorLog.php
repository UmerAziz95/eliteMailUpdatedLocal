<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'exception_class',
        'message',
        'file',
        'line',
        'trace',
        'url',
        'method',
        'ip_address',
        'user_agent',
        'user_id',
        'request_data',
        'severity',
    ];

    protected $casts = [
        'request_data' => 'array',
        'line' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function logException(\Throwable $exception, array $context = [])
    {
        $request = request();
        
        return static::create([
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => $request ? $request->fullUrl() : null,
            'method' => $request ? $request->method() : null,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
            'user_id' => auth()->id(),
            'request_data' => $request ? $request->except(['password', 'password_confirmation', 'current_password']) : null,
            'severity' => $context['severity'] ?? 'error',
        ]);
    }
}
