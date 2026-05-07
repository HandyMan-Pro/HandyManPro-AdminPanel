<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update provider_subscriptions foreign key to prevent cascade delete on plan removal.
        Schema::table('provider_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->foreign('plan_id')->references('id')->on('plans');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert provider_subscriptions foreign key to cascading behavior.
        Schema::table('provider_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }
};
