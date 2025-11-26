<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use App\Models\Configuration;

class Panel extends Model
{
    use HasFactory;

    protected $fillable = [
        'auto_generated_id',
        'title',
        'description',
        'limit',
        'remaining_limit',
        'is_active',
        'created_by',
        // newly added attributes
        'provider_type',
        'panel_sr_no',
    ];

    protected static function booted(): void
    {
        static::creating(function (Panel $panel) {
            if ($panel->getKey()) {
                return;
            }

            $panel->setAttribute($panel->getKeyName(), static::getNextAvailableId());
        });
    }

    /**
     * Determine the smallest positive panel ID that is not yet used.
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

    /**
     * Determine the next available panel_sr_no for the given provider.
     * It fills the first missing serial number before moving to max+1.
     */
    public static function getNextSerialForProvider(?string $providerType): int
    {
        $providerType = $providerType ?: Configuration::get('PROVIDER_TYPE', 'Google');

        $query = static::query();

        if ($providerType !== null) {
            $query->where('provider_type', $providerType);
        } else {
            $query->whereNull('provider_type');
        }

        /** @var Collection<int,int> $serials */
        $serials = $query
            ->whereNotNull('panel_sr_no')
            ->orderBy('panel_sr_no')
            ->pluck('panel_sr_no');

        // Find the smallest missing positive serial number; if none are missing, return max + 1
        $candidate = 1;
        foreach ($serials as $serial) {
            if ($serial < $candidate) {
                continue;
            }
            if ($serial === $candidate) {
                $candidate++;
                continue;
            }
            // Found a gap
            if ($serial > $candidate) {
                break;
            }
        }

        return $candidate;
    }

    public function order_panels()
    {
        return $this->hasMany(OrderPanel::class);
    }
  
}
