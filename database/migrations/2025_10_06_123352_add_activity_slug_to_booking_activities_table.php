<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivitySlugToBookingActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('booking_activities', function (Blueprint $table) {
            $table->string('activity_slug')->nullable(); // Adding the 'activity_slug' column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('booking_activities', function (Blueprint $table) {
            $table->dropColumn('activity_slug'); // Dropping the 'activity_slug' column if rolling back
        });
    }
}
