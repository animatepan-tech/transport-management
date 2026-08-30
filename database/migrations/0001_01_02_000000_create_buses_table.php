<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string('bus_number')->unique();
            $table->string('registration_number')->nullable();
            $table->unsignedInteger('capacity')->default(40);
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->date('license_expiry')->nullable();
            $table->date('puc_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('buses'); }
};
