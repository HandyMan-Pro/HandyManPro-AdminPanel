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
        Schema::table('provider_subscriptions', function (Blueprint $table) {
            $table->json('other_detail')->nullable()->after('plan_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_subscriptions', function (Blueprint $table) {
            $table->dropColumn('other_detail');
        });
    }
};
