<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$start = microtime(true);
\App\Models\Customer::syncBilling();
echo "Execution time: " . (microtime(true) - $start) . " seconds\n";
