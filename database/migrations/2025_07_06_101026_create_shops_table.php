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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->string('shop_name');
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->text('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('long', 10, 7)->nullable();
            $table->string('registration_number')->unique();
            $table->time('shop_start_time')->nullable();
            $table->time('shop_end_time')->nullable();
            $table->string('contact_number', 20);
            $table->string('email')->unique();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        $permissions = [
            [
                'id' => 154,
                'name' => 'shop',
                'guard_name' => 'web',
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 155,
                'name' => 'shop add',
                'guard_name' => 'web',
                'parent_id' => 154,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 156,
                'name' => 'shop edit',
                'guard_name' => 'web',
                'parent_id' => 154,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 157,
                'name' => 'shop delete',
                'guard_name' => 'web',
                'parent_id' => 154,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 158,
                'name' => 'shop list',
                'guard_name' => 'web',
                'parent_id' => 154,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        
        // Insert all permissions
        DB::table('permissions')->insert($permissions);
        
        // Step 2: Assign permissions to roles
        $roles = Role::whereIn('name', ['admin', 'demo_admin', 'provider'])->get();
        $permissionsToAssign = Permission::whereIn('name', ['shop', 'shop add', 'shop edit', 'shop delete','shop list'])->get();
        
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
        Schema::dropIfExists('shops');
    }
};
