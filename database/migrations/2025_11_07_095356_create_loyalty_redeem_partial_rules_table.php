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
        Schema::create('loyalty_redeem_partial_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('redeem_rule_id');
            $table->string('rule_name')->nullable();
            $table->integer('point_from')->default(0);
            $table->integer('point_to')->default(0);
            $table->integer('amount')->default(0);
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_redeem_partial_rules');
    }
};
