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
        Schema::create('loyalty_redeem_rules', function (Blueprint $table) {
            $table->id();
            $table->string('loyalty_type')->nullable();
            $table->string('redeem_type')->nullable();
            $table->integer('threshold_points')->nullable();
            $table->integer('max_discount')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('is_stackable')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_redeem_rules');
    }
};
