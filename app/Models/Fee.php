<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fee extends Model
{
    protected $fillable = [
        'student_id',
        'period_start',
        'period_end',
        'billing_type',
        'amount',
        'paid_amount',
        'late_fee',
        'status',
        'last_reminder_at',
        'reminder_count',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'last_reminder_at' => 'datetime',
    ];

    /**
     * Fee belongs to a student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Payment allocations belonging to this fee.
     *
     * payment_allocations is the accounting source of truth.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Total amount required for this fee.
     *
     * Base fee + late fee.
     */
    public function getTotalRequiredAttribute(): float
    {
        return round(
            (float) $this->amount
            + (float) $this->late_fee,
            2
        );
    }

    /**
     * Total amount actually allocated to this fee.
     *
     * This reads from payment_allocations instead of relying
     * on the potentially stale paid_amount column.
     */
    public function getAllocatedAmountAttribute(): float
    {
        return round(
            (float) $this->allocations()->sum('amount'),
            2
        );
    }

    /**
     * Remaining amount due.
     *
     * Total required - actual payment allocations.
     */
    public function getOutstandingAmountAttribute(): float
    {
        return max(
            0,
            round(
                $this->total_required
                - $this->allocated_amount,
                2
            )
        );
    }

    /**
     * Amount still due.
     *
     * Alias for outstanding_amount.
     */
    public function getDueAmountAttribute(): float
    {
        return $this->outstanding_amount;
    }

    /**
     * Current fee balance.
     *
     * Positive = overpayment
     * Zero = settled
     * Negative = due
     */
    public function getBalanceAttribute(): float
    {
        return round(
            $this->allocated_amount
            - $this->total_required,
            2
        );
    }

    /**
     * Whether this fee is fully paid.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->outstanding_amount <= 0.01;
    }

    /**
     * Whether this fee is partially paid.
     */
    public function getIsPartialAttribute(): bool
    {
        return (
            $this->allocated_amount > 0.01
            && $this->outstanding_amount > 0.01
        );
    }

    /**
     * Whether this fee has no payment allocation.
     */
    public function getIsPendingAttribute(): bool
    {
        return (
            $this->allocated_amount <= 0.01
            && $this->outstanding_amount > 0.01
        );
    }

    /**
     * Keep the paid_amount field synchronized with allocations.
     *
     * Call this after creating/deleting/updating a PaymentAllocation.
     */
    public function syncPaidAmount(): void
    {
        $allocated = round(
            (float) $this->allocations()->sum('amount'),
            2
        );

        $this->update([
            'paid_amount' => $allocated,
            'status' => $allocated >= $this->total_required - 0.01
                ? 'paid'
                : (
                    $allocated > 0.01
                        ? 'partial'
                        : 'pending'
                ),
        ]);
    }
}

