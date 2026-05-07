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
        Schema::create('loyalty_earn_rules', function (Blueprint $table) {
            $table->id();
            $table->string('loyalty_type')->nullable();
            $table->integer('minimum_amount')->nullable();
            $table->integer('maximum_amount')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('points')->default(0);
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
        Schema::dropIfExists('loyalty_earn_rules');
    }
};
