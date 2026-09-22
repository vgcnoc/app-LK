<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$gratis = \App\Models\Customer::where('area', 'like', 'Gratis%')->get();
foreach($gratis as $c) {
    echo "ID: {$c->id}, Area: '{$c->area}', Length: " . strlen($c->area) . "\n";
}
?>
