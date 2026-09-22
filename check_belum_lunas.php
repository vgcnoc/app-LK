<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$customers = \App\Models\Customer::where(function($q) {
    $q->whereNull('status_pelanggan')->orWhereIn('status_pelanggan', ['Aktif', '']);
})->whereNotIn('area', ['Gratis BC 1', 'Gratis BC 2', 'Gratis BC 3'])->get();

$belum_lunas = $customers->filter(function($c) {
    return strtolower($c->status) !== 'paid' && strtolower($c->status) !== 'prorata';
});

echo 'Total Belum Lunas: ' . $belum_lunas->count() . "\n";
foreach ($belum_lunas as $c) {
    echo $c->name . ' - Status: ' . $c->status . ' - Amount: ' . $c->amount . ' - Promise: ' . $c->promise_date . "\n";
}
?>
