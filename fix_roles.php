<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::where('role', 'admin_cs')->orWhere('role', 'Admin_cs')->get();
foreach ($users as $u) {
    $u->role = 'admin_cs';
    $u->save();
    if (method_exists($u, 'syncRoles')) {
        $u->syncRoles(['admin_cs']);
    }
    echo $u->name . ' fixed.' . PHP_EOL;
}
echo 'Done.' . PHP_EOL;
