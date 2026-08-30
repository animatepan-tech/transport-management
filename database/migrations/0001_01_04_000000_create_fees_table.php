<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Primary key
            |--------------------------------------------------------------------------
            */

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Student
            |--------------------------------------------------------------------------
            */

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Fee period
            |--------------------------------------------------------------------------
            */

            $table->date('period_start');

            $table->date('period_end');

            /*
            |--------------------------------------------------------------------------
            | Billing type
            |--------------------------------------------------------------------------
            */

            $table->enum('billing_type', [
                'monthly',
                'quarterly',
                'half_yearly',
                'yearly',
            ])->default('monthly');

            /*
            |--------------------------------------------------------------------------
            | Fee amounts
            |--------------------------------------------------------------------------
            */

            $table->decimal('amount', 10, 2);

            $table->decimal('paid_amount', 10, 2)
                ->default(0);

            $table->decimal('late_fee', 10, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Fee status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'pending',
                'partial',
                'paid',
                'carried_forward',
            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Reminder tracking
            |--------------------------------------------------------------------------
            */

            $table->timestamp('last_reminder_at')
                ->nullable();

            $table->unsignedInteger('reminder_count')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate fee periods
            |--------------------------------------------------------------------------
            |
            | A student cannot have two fees for exactly the same
            | start/end period.
            |
            */

            $table->unique([
                'student_id',
                'period_start',
                'period_end',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Index for oldest-first fee allocation
            |--------------------------------------------------------------------------
            |
            | PaymentAllocationService searches outstanding fees
            | for a student and orders them by period_start and id.
            |
            */

            $table->index([
                'student_id',
                'period_start',
                'id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};