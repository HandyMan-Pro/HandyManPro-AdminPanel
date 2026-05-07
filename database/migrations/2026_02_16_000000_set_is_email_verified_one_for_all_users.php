<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Set is_email_verified = 1 for every user.
     */
    public function up(): void
    {
        DB::table('users')->update(['is_email_verified' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->update(['is_email_verified' => 0]);
    }
};
