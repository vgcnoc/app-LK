<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$settings = \App\Models\Setting::pluck('value', 'key')->toArray();
echo json_encode($settings, JSON_PRETTY_PRINT);
?>
