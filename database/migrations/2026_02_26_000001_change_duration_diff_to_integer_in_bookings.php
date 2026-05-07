<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDurationDiffToIntegerInBookings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Convert existing string duration_diff (in minutes) to integer (in seconds)
            // First, we need to convert existing data
            \DB::statement('UPDATE bookings SET duration_diff = CAST(duration_diff AS UNSIGNED) * 60 WHERE duration_diff IS NOT NULL AND duration_diff != ""');
            
            // Now change the column type to integer
            $table->integer('duration_diff')->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Convert back from seconds to minutes
            \DB::statement('UPDATE bookings SET duration_diff = CAST(duration_diff / 60 AS CHAR) WHERE duration_diff IS NOT NULL');
            
            $table->string('duration_diff')->nullable()->default('0')->change();
        });
    }
}
