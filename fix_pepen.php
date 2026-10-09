<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Models\Transaction::where('id', 1764)->delete();
App\Models\Customer::where('id', 287)->update(['last_paid_date' => null, 'status' => 'pending']);
echo "Fixed PEPEN\n";
