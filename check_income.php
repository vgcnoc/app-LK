<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'Total Income in DB: ' . \App\Models\Transaction::where('type', 'income')->sum('amount') . "\n";
echo 'Total Income where paid: ' . \App\Models\Transaction::where('type', 'income')->where(function($q) {
    $q->where('payment_status', 'paid')->orWhereNull('payment_status');
})->sum('amount') . "\n";
echo 'Total Income count: ' . \App\Models\Transaction::where('type', 'income')->count() . "\n";
?>
