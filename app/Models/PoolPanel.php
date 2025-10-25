<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PoolPanel extends Model
{
    use HasFactory;

    protected $fillable = [
        'auto_generated_id',
        'title',
        'description',
        'limit',
        'remaining_limit',
        'used_limit',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (PoolPanel $poolPanel) {
            if ($poolPanel->getKey()) {
                return;
            }

            $poolPanel->setAttribute($poolPanel->getKeyName(), static::getNextAvailableId());
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function poolPanelSplits()
    {
        return $this->hasMany(PoolPanelSplit::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // Mutators and Accessors
    public function getUsageLimitAttribute()
    {
        return $this->limit - $this->remaining_limit;
    }

    public function getUsagePercentageAttribute()
    {
        if ($this->limit <= 0) {
            return 0;
        }
        return round(($this->usage_limit / $this->limit) * 100, 2);
    }

    public function getRemainingPercentageAttribute()
    {
        if ($this->limit <= 0) {
            return 0;
        }
        return round(($this->remaining_limit / $this->limit) * 100, 2);
    }

    // Helper methods
    public function updateUsage($amount)
    {
        if ($this->remaining_limit >= $amount) {
            $this->remaining_limit -= $amount;
            $this->used_limit += $amount;
            $this->save();
            return true;
        }
        return false;
    }

    public function releaseUsage($amount)
    {
        $this->remaining_limit += $amount;
        $this->used_limit = max(0, $this->used_limit - $amount);
        $this->save();
    }

    public function isAvailable()
    {
        return $this->is_active && $this->remaining_limit > 0;
    }

    /**
     * Determine the smallest positive pool panel ID that is not yet used.
     */
    public static function getNextAvailableId(): int
    {
        $keyName = (new static())->getKeyName();

        /** @var Collection<int,int> $ids */
        $ids = static::query()
            ->orderBy($keyName)
            ->pluck($keyName);

        $candidate = 1;

        foreach ($ids as $id) {
            if ($id > $candidate) {
                break;
            }

            if ($id === $candidate) {
                $candidate++;
            }
        }

        return $candidate;
    }
}
