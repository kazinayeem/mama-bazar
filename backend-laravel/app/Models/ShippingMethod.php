<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $table = 'shipping_methods';

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'charge',
        'estimated_delivery',
        'description',
        'applicable_areas',
        'priority',
        'free_shipping_min_amount',
        'cod_available',
        'status',
    ];

    protected $casts = [
        'charge' => 'float',
        'priority' => 'integer',
        'free_shipping_min_amount' => 'float',
        'cod_available' => 'boolean',
        'created_at' => 'datetime',
    ];

    /** Normalised list of applicable districts/areas. Empty = nationwide. */
    public function getAreasArrayAttribute(): array
    {
        $raw = $this->applicable_areas;
        if (empty($raw)) {
            return [];
        }

        return collect(preg_split('/[,\n]+/', (string) $raw))
            ->map(fn ($v) => trim(mb_strtolower($v)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function isApplicableTo(?string $district): bool
    {
        $areas = $this->areas_array;
        if (empty($areas)) {
            return true;
        }
        $d = mb_strtolower(trim((string) $district));
        if ($d === '') {
            return true;
        }
        // Match exact district or broad zones: "inside_dhaka", "outside_dhaka", "nationwide".
        if (in_array($d, $areas, true)) {
            return true;
        }
        $isDhaka = in_array($d, ['dhaka', 'dhaka city', 'inside dhaka'], true);
        if ($isDhaka && count(array_intersect($areas, ['inside_dhaka', 'inside dhaka', 'dhaka'])) > 0) {
            return true;
        }
        if (! $isDhaka && count(array_intersect($areas, ['outside_dhaka', 'outside dhaka', 'outside'])) > 0) {
            return true;
        }
        // Partial match fallback (e.g. "Dhaka North" matches "dhaka").
        foreach ($areas as $area) {
            if ($area !== '' && (str_contains($d, $area) || str_contains($area, $d))) {
                return true;
            }
        }

        return false;
    }
}
