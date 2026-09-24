<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customers = App\Models\Customer::all();
$found = false;
foreach ($customers as $c) {
    if (stripos($c->name, 'deri') !== false || stripos($c->name, 'rumah') !== false) {
        echo $c->id . ' - ' . $c->name . ' (Area: ' . $c->area . ', Paket: ' . $c->paket . ') ' . PHP_EOL;
        $found = true;
    }
}
if (!$found) echo "No customers found.\n";
