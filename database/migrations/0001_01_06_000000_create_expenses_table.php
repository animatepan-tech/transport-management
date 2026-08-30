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
        Schema::create('expenses', function (Blueprint $table) {

            $table->id();

            /*
             * Optional bus association.
             *
             * If the bus is deleted, the expense remains
             * but bus_id becomes NULL.
             */
            $table->foreignId('bus_id')
                ->nullable()
                ->constrained('buses')
                ->nullOnDelete();

            $table->date('expense_date');

            $table->string('category', 100);

            $table->decimal('amount', 10, 2);

            $table->string('vendor', 150)
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->timestamps();

            /*
             * Useful for bus-wise expense reports.
             */
            $table->index([
                'bus_id',
                'expense_date',
            ]);

            /*
             * Useful for date-based expense reports.
             */
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};