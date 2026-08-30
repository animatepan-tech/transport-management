<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'bus_id',
        'expense_date',
        'category',
        'amount',
        'vendor',
        'description',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Expense belongs to a bus.
     *
     * bus_id is nullable because an expense may be
     * a general transport expense not assigned to
     * a specific bus.
     */
    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }
}