<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo App\Models\Customer::whereNull('status_pelanggan')->count() . ' nulls, ';
echo App\Models\Customer::where('status_pelanggan', '!=', 'Booking')->count() . ' not booking, ';
echo App\Models\Customer::where('status_pelanggan', 'Booking')->count() . ' booking, ';
echo App\Models\Customer::count() . ' total';
