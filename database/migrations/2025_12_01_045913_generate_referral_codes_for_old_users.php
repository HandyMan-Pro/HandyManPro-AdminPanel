<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('user_type', 'user')
            ->whereNull('referral_code')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {

                    do {
                        $code = strtoupper(Str::random(8));
                    } while (
                        DB::table('users')->where('referral_code', $code)->exists()
                    );

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['referral_code' => $code]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('user_type', 'user')
            ->update(['referral_code' => null]);
    }
};
