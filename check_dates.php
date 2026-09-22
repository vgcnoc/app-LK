<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$customers = \App\Models\Customer::whereIn('name', ['Febri Permana/YR-COBRA', 'Yeti Farida', 'Siti nurhasanah', 'U.SUDANA', 'YAYU NURHASANAH', 'MIZEL TANDRA WINATA', 'AGUS', 'ROMSIYAH', 'RIKI RIANSYAH', 'UNGGUL DERAJAT', 'ONIH/BUCEK', 'APRIANTO'])->get();
foreach ($customers as $c) {
    echo $c->name . ' - Reg: ' . $c->register_date . ' - Created: ' . $c->created_at . ' - Status DB: ' . $c->status . "\n";
}
?>
