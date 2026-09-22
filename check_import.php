<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\Customer::count();
echo "Total Customers: " . $count . "\n";

$pendingCount = \App\Models\Customer::where('status', 'pending')->count();
echo "Pending Customers: " . $pendingCount . "\n";

$nunggakCount = \App\Models\Customer::where('status', 'nunggak')->count();
echo "Nunggak Customers: " . $nunggakCount . "\n";

$prorataCount = \App\Models\Customer::where('status', 'prorata')->count();
echo "Prorata Customers: " . $prorataCount . "\n";

$latest = \App\Models\Customer::orderBy('id', 'desc')->take(5)->get(['id', 'name', 'area', 'status', 'status_pelanggan', 'register_date']);
echo "Latest 5 Customers:\n";
foreach($latest as $c) {
    echo "- ID: {$c->id}, Name: {$c->name}, Area: {$c->area}, Status: {$c->status}, Status_Pel: {$c->status_pelanggan}, RegDate: {$c->register_date}\n";
}
