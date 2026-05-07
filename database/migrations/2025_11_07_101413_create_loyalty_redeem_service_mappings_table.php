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
        Schema::create('loyalty_redeem_service_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_redeem_id')
                ->constrained('loyalty_redeem_rules') // ✅ explicitly reference the parent table
                ->onDelete('cascade');

            $table->unsignedBigInteger('service_id'); // ID of service / package / category
            $table->string('loyalty_type')->nullable(); // service / service_package / service_category
            $table->timestamps();

            $table->unique(['loyalty_redeem_id', 'service_id', 'loyalty_type'], 'loyalty_redeem_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_redeem_service_mappings');
    }
};
