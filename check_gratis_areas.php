<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$gratis = \App\Models\Customer::where('status_pelanggan', 'Gratis')->orWhere('area', 'like', 'Gratis%')->get();
foreach($gratis as $c) {
    echo $c->area . ' | ' . $c->status_pelanggan . "\n";
}
?>
