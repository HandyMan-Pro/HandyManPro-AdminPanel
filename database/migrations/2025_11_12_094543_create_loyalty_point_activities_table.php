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
        Schema::create('loyalty_point_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // The user earning/redeeming
            $table->string('type')->nullable(); // "earn", "redeem", "referral"
            $table->integer('points')->default(0); // Number of points
            $table->string('source')->nullable(); // booking, referral_code, etc.
            $table->string('earn_type')->nullable(); // credit or debit
            $table->unsignedBigInteger('related_id')->nullable(); // booking_id or referral_user_id
            $table->string('description')->nullable(); // Optional details
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_activities');
    }
};
