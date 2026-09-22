const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
    const phpScript = `<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\\Models\\Customer;
use App\\Models\\Commission;
use App\\Models\\Sales;
use Illuminate\\Support\\Str;

$sales = Sales::first();

// Batal Bookings
$kec = ['Sukasari', 'Sukasari', 'Sukasari', 'Sukasari', 'Sukajadi', 'Sukajadi', 'Sukajadi', 'Gegerkalong', 'Gegerkalong', 'Pasteur', 'Pasteur'];
$alasan = ['ODP Penuh', 'ODP Penuh', 'ODP Penuh', 'Tiang Jauh', 'Tiang Jauh', 'Pelanggan Mundur', 'Area Tidak Terjangkau', 'Pelanggan Mundur'];

for ($i = 1; $i <= 25; $i++) {
    Customer::create([
        'name' => 'Dummy Batal ' . $i,
        'base_amount' => 150000,
        'amount' => 150000,
        'status_pelanggan' => 'Batal',
        'keterangan_status' => $alasan[array_rand($alasan)],
        'kecamatan' => $kec[array_rand($kec)],
        'desa_kelurahan' => 'Desa Dummy',
        'alamat' => 'Jl. Dummy No. ' . $i,
        'paket' => '10 Mbps',
        'sales_id' => $sales ? $sales->id : null,
    ]);
}

if ($sales) {
    $cust = Customer::where('status_pelanggan', 'Aktif')->first();
    $custId = $cust ? $cust->id : 1;
    for ($i = 1; $i <= 10; $i++) {
        Commission::create([
            'sales_id' => $sales->id,
            'customer_id' => $custId,
            'amount' => 50000,
            'type' => 'booking',
            'description' => 'Komisi Pasang Baru Dummy ' . $i,
            'status' => 'pending',
        ]);
    }
    for ($i = 1; $i <= 10; $i++) {
        Commission::create([
            'sales_id' => $sales->id,
            'customer_id' => $custId,
            'amount' => 50000,
            'type' => 'booking',
            'description' => 'Komisi Pasang Baru Paid Dummy ' . $i,
            'status' => 'paid',
            'proof_of_payment' => 'dummy.jpg',
        ]);
    }
}
echo "Dummy data created on VPS!";
`;

    conn.exec('cd /var/www/lk.viruzs.my.id && cat << \'EOF\' > create_dummy.php\n' + phpScript + '\nEOF\nphp create_dummy.php && rm create_dummy.php', (err, stream) => {
        if (err) throw err;
        stream.on('close', () => conn.end())
              .on('data', (data) => process.stdout.write(''+data))
              .stderr.on('data', (data) => process.stderr.write(''+data));
    });
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: 'viruzs123',
    readyTimeout: 99999
});
