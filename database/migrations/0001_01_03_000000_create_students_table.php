<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bus_id')->nullable()->constrained('buses')->nullOnDelete();
            $table->string('student_name');
            $table->string('parent_name')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('pickup_stop')->nullable();
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->date('start_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['bus_id','active']);
        });
    }
    public function down(): void { Schema::dropIfExists('students'); }
};
