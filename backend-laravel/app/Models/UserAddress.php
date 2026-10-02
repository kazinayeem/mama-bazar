<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $table = 'user_addresses';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'recipient_name',
        'phone',
        'alternative_phone',
        'email',
        'country',
        'division',
        'district',
        'upazila',
        'area',
        'shipping_area',
        'address',
        'apartment',
        'postal_code',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->apartment ? 'Apt/Suite: '.$this->apartment : null,
            $this->area,
            $this->upazila,
            $this->district,
            $this->division,
            $this->postal_code ? 'Postal Code: '.$this->postal_code : null,
        ]);

        return implode(', ', $parts);
    }
}
