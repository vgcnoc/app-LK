<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $role = Role::where('name', 'sales')->first();
        if ($role) {
            $permission = Permission::firstOrCreate(['name' => 'akses_data_pelanggan']);
            if (!$role->hasPermissionTo('akses_data_pelanggan')) {
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('name', 'sales')->first();
        if ($role) {
            $role->revokePermissionTo('akses_data_pelanggan');
        }
    }
};
