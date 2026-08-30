<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'student_id',
        'payment_date',
        'amount',
        'reference',
        'payment_mode',
        'notes',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Payment belongs to a student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Payment allocations.
     *
     * A payment can be allocated across multiple fees.
     *
     * payment_allocations is the authoritative relationship
     * between payments and fees.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Total amount allocated from this payment.
     *
     * This amount comes directly from payment_allocations.
     */
    public function getAllocatedAmountAttribute(): float
    {
        return round(
            (float) $this->allocations()->sum('amount'),
            2
        );
    }

    /**
     * Amount remaining from this payment.
     *
     * Positive value means the payment has unallocated
     * advance credit remaining.
     */
    public function getAdvanceAmountAttribute(): float
    {
        return max(
            0,
            round(
                (float) $this->amount
                - $this->allocated_amount,
                2
            )
        );
    }

    /**
     * Whether this payment is fully allocated.
     *
     * A tolerance of 0.01 is used for monetary rounding.
     */
    public function getIsFullyAllocatedAttribute(): bool
    {
        return $this->advance_amount <= 0.01;
    }

    /**
     * Whether this payment still has advance credit.
     */
    public function getHasAdvanceAttribute(): bool
    {
        return $this->advance_amount > 0.01;
    }

    /**
     * Amount that has not yet been allocated.
     *
     * Alias for advance_amount, useful when displaying
     * payment information in the UI.
     */
    public function getUnallocatedAmountAttribute(): float
    {
        return $this->advance_amount;
    }

    /**
     * Whether this payment has any allocation records.
     */
    public function getHasAllocationsAttribute(): bool
    {
        return $this->allocations()->exists();
    }
}

