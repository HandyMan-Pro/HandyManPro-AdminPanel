<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->integer('redeemed_points')->nullable()->after('discount');
            $table->integer('redeemed_discount')->nullable()->after('redeemed_points');
        });

        $permissions = [
            [
                'id' => 159,
                'name' => 'shop document',
                'guard_name' => 'web',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 160,
                'name' => 'shopdocument add',
                'guard_name' => 'web',
                'parent_id' => 159,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 161,
                'name' => 'shopdocument edit',
                'guard_name' => 'web',
                'parent_id' => 159,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 162,
                'name' => 'shopdocument delete',
                'guard_name' => 'web',
                'parent_id' => 159,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 163,
                'name' => 'shopdocument list',
                'guard_name' => 'web',
                'parent_id' => 159,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 164,
                'name' => 'loyalty rule',
                'guard_name' => 'web',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 165,
                'name' => 'loyalty add',
                'guard_name' => 'web',
                'parent_id' => 164,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 166,
                'name' => 'loyalty edit',
                'guard_name' => 'web',
                'parent_id' => 164,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 167,
                'name' => 'loyalty delete',
                'guard_name' => 'web',
                'parent_id' => 164,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 168,
                'name' => 'loyalty list',
                'guard_name' => 'web',
                'parent_id' => 164,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];


        if(!empty($permissions)){

            foreach ($permissions as $permission) {
                $exists = DB::table('permissions')->where('name', $permission['name'])->exists();
                if (!$exists) {
                   DB::table('permissions')->insert($permission);
                }
        }
    }


        // Step 2: Assign permissions to roles
        $roles = Role::whereIn('name', ['admin', 'demo_admin', 'provider'])->get();
        $permissionsToAssign = Permission::whereIn('name', [
            'shopdocument',
            'shopdocument add',
            'shopdocument edit',
            'shopdocument delete',
            'shopdocument list',
            'loyalty',
            'loyalty add',
            'loyalty edit',
            'loyalty delete',
            'loyalty list',
        ])->get();

        foreach ($roles as $role) {
            foreach ($permissionsToAssign as $permission) {
                // Check if the permission already exists for the role
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->where('role_id', $role->id)
                    ->exists();

                // If it doesn't exist, assign the permission to the role
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permission->id,
                        'role_id' => $role->id,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['redeemed_points', 'redeemed_discount']);
        });
    }
};
