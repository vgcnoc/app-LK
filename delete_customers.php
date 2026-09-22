<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\Customer::count();
echo "Deleting " . $count . " customers...\n";
\App\Models\Customer::truncate();
echo "All customers deleted. Remaining: " . \App\Models\Customer::count() . "\n";
