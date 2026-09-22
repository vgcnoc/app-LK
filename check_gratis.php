<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo 'Total Gratis: ' . \App\Models\Customer::where('status_pelanggan', 'Gratis')->orWhere('area', 'like', 'Gratis%')->count() . "\n";
?>
