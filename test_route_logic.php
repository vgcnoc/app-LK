<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$excludedAreas = ['Gratis BC 1', 'Gratis BC 2', 'Gratis BC 3']; 
$count = \App\Models\Customer::where(function($query) use ($excludedAreas) { 
    $query->whereIn('status_pelanggan', ['Berhenti', 'Nonaktif', 'Suspend', 'Isolir', 'Gratis', 'Stop Permanen', 'Berhenti sementara'])
          ->orWhereIn('area', $excludedAreas); 
})->count(); 
echo 'Count returned by route logic: ' . $count . "\n";
?>
