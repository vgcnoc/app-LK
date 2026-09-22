const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
    const phpScript = `<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\\Models\\Customer;
use App\\Models\\Commission;

// Delete dummy commissions first (foreign key constraints if any)
$delComms = Commission::where('description', 'like', '%Dummy%')->delete();

// Delete dummy customers
$delCusts = Customer::where('name', 'like', 'Dummy Batal %')->delete();

echo "Deleted \$delComms dummy commissions and \$delCusts dummy customers.";
`;

    conn.exec('cd /var/www/lk.viruzs.my.id && cat << \'EOF\' > delete_dummy.php\n' + phpScript + '\nEOF\nphp delete_dummy.php && rm delete_dummy.php', (err, stream) => {
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
