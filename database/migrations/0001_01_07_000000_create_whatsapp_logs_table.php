<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('template_name')->nullable();
            $table->string('message_type')->default('due');
            $table->decimal('balance_at_send',10,2)->default(0);
            $table->string('status')->default('queued');
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['student_id','fee_id','message_type']);
        });
    }
    public function down(): void { Schema::dropIfExists('whatsapp_logs'); }
};
