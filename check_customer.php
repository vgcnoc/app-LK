<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Customer::where('name', 'contoh')->get();
foreach($c as $cust) {
    var_dump($cust->toArray());
}
