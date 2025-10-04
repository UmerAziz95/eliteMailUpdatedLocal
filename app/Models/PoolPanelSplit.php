<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoolPanelSplit extends Model
{
    use HasFactory;

    protected $fillable = [
        'pool_panel_id',
        'pool_id',
        'inboxes_per_domain',
        'domains',
        'uploaded_file_path',
    ];

    protected $casts = [
        'domains' => 'array',
    ];

    // Relationships
    public function poolPanel()
    {
        return $this->belongsTo(PoolPanel::class);
    }

    // Scopes
    public function scopeForPool($query, $poolId)
    {
        return $query->where('pool_id', $poolId);
    }

    public function scopeForPoolPanel($query, $poolPanelId)
    {
        return $query->where('pool_panel_id', $poolPanelId);
    }

    // Helper methods
    public function getDomainCount()
    {
        return is_array($this->domains) ? count($this->domains) : 0;
    }

    public function getTotalInboxes()
    {
        return $this->getDomainCount() * $this->inboxes_per_domain;
    }
}
