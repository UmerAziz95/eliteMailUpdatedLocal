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
     * Determine the next sequential panel_sr_no for the given provider.
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

        $maxSerial = $query->max('panel_sr_no');

        return ((int) $maxSerial) + 1;
    }

    public function order_panels()
    {
        return $this->hasMany(OrderPanel::class);
    }
  
}
