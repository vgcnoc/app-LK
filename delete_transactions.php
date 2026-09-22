<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\Transaction::count();
echo "Deleting " . $count . " transactions...\n";
\App\Models\Transaction::truncate();
echo "All transactions deleted.\n";
