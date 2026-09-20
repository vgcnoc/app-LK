<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

$permission = Permission::firstOrCreate(['name' => 'akses_integrasi', 'guard_name' => 'web']);

$roles = Role::whereIn('name', ['Admin', 'Super Admin', 'Superadmin'])->get();
foreach ($roles as $role) {
    if (!$role->hasPermissionTo('akses_integrasi')) {
        $role->givePermissionTo('akses_integrasi');
    }
}

// Optionally, give to user 1
$user = User::find(1);
if ($user && !$user->hasPermissionTo('akses_integrasi')) {
    $user->givePermissionTo('akses_integrasi');
}

echo "Permission akses_integrasi berhasil ditambahkan!\n";
