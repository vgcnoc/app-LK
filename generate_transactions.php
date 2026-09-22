<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

$paidCustomers = Customer::where('status', 'paid')->get();
$count = 0;
$totalAmount = 0;

DB::beginTransaction();
try {
    foreach ($paidCustomers as $customer) {
        $amount = $customer->base_amount > 0 ? $customer->base_amount : 0;
        if ($amount == 0) continue; // Skip if no base amount
        
        $paymentDate = $customer->last_paid_date ?: now()->toDateString();

        Transaction::create([
            'type' => 'income',
            'date' => $paymentDate,
            'description' => 'Pembayaran dari ' . $customer->name,
            'amount' => $amount,
            'area' => $customer->area,
            'payment_method' => 'Tunai',
            'payment_status' => 'paid'
        ]);
        
        $count++;
        $totalAmount += $amount;
    }
    DB::commit();
    echo "Successfully generated $count transactions with total amount Rp " . number_format($totalAmount, 0, ',', '.') . "\n";
} catch (\Exception $e) {
    DB::rollback();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
