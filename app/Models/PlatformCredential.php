<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCredential extends Model
{
    protected $fillable = [
        'order_id',
        'platform_type',
        'credentials',
    ];

    protected $casts = [
        'credentials' => 'array',
    ];

    /**
     * Get the order that owns the credential.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get a specific credential value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCredential(string $key, $default = null)
    {
        return $this->credentials[$key] ?? $default;
    }

    /**
     * Set a specific credential value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setCredential(string $key, $value): void
    {
        $credentials = $this->credentials ?? [];
        $credentials[$key] = $value;
        $this->credentials = $credentials;
    }
}



