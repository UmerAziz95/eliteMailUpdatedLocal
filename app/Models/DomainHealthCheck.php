<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainHealthCheck extends Model
{
    use HasFactory;

    protected $table = 'domain_health_checks';

    protected $fillable = [
        'order_id',
        'domain',
        'status',
        'summary',
        'dns_status',
        'dns_errors',
        'blacklist_listed',
        'blacklist_listed_on',
    ];

    protected $casts = [
        'dns_errors' => 'array',
        'blacklist_listed_on' => 'array',
        'blacklist_listed' => 'boolean',
    ];

    // Relationship to Order (assuming you have an Order model)
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}