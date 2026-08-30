<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bus extends Model
{
    protected $fillable = [
        'bus_number',
        'registration_number',
        'capacity',
        'driver_name',
        'driver_phone',
        'license_expiry',
        'puc_expiry',
        'insurance_expiry',
        'notes',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'puc_expiry' => 'date',
        'insurance_expiry' => 'date',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}