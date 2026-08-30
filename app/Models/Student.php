<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'bus_id',
        'student_name',
        'parent_name',
        'whatsapp_number',
        'pickup_stop',
        'monthly_fee',
        'active',
        'start_date',
        'notes',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'active' => 'boolean',
        'start_date' => 'date',
    ];

    /**
     * Student belongs to a bus.
     */
    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    /**
     * Student fees.
     */
    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    /**
     * Student payments.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Total generated fee amount.
     *
     * Excludes late fees.
     */
    public function getTotalFeesAttribute(): float
    {
        return round(
            (float) $this->fees()->sum('amount'),
            2
        );
    }

    /**
     * Total late fees.
     */
    public function getTotalLateFeesAttribute(): float
    {
        return round(
            (float) $this->fees()->sum('late_fee'),
            2
        );
    }

    /**
     * Total amount received.
     */
    public function getTotalPaidAttribute(): float
    {
        return round(
            (float) $this->payments()->sum('amount'),
            2
        );
    }

    /**
     * Total amount allocated to fees.
     *
     * payment_allocations is the accounting source of truth.
     */
    public function getTotalAllocatedAttribute(): float
    {
        return round(
            (float) $this->payments()
                ->join(
                    'payment_allocations',
                    'payments.id',
                    '=',
                    'payment_allocations.payment_id'
                )
                ->sum('payment_allocations.amount'),
            2
        );
    }

    /**
     * Unallocated payment credit.
     */
    public function getAdvanceAmountAttribute(): float
    {
        return max(
            0,
            round(
                $this->total_paid
                - $this->total_allocated,
                2
            )
        );
    }

    /**
     * Total amount required for all fees.
     */
    public function getTotalRequiredAttribute(): float
    {
        return round(
            $this->total_fees
            + $this->total_late_fees,
            2
        );
    }

    /**
     * Outstanding amount.
     *
     * This is based on actual fee allocations.
     */
    public function getDueAmountAttribute(): float
    {
        return max(
            0,
            round(
                $this->total_required
                - $this->total_allocated,
                2
            )
        );
    }

    /**
     * Current account balance.
     *
     * Negative = due
     * Zero = settled
     * Positive = advance
     */
    public function getCurrentBalanceAttribute(): float
    {
        return round(
            $this->total_paid
            - $this->total_required,
            2
        );
    }

    /**
     * Student has outstanding due.
     */
    public function getHasDueAttribute(): bool
    {
        return $this->due_amount > 0.01;
    }

    /**
     * Student has advance.
     */
    public function getHasAdvanceAttribute(): bool
    {
        return $this->advance_amount > 0.01;
    }

    /**
     * Account is settled.
     */
    public function getIsSettledAttribute(): bool
    {
        return (
            $this->due_amount <= 0.01
            && $this->advance_amount <= 0.01
        );
    }

    /**
     * Approximate number of monthly periods covered by advance.
     */
    public function getAdvanceMonthsAttribute(): float
    {
        $monthlyFee = (float) $this->monthly_fee;

        if ($monthlyFee <= 0) {
            return 0;
        }

        return round(
            $this->advance_amount / $monthlyFee,
            2
        );
    }
}