const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    
    const deployCmd = `
    cd /var/www/lk.viruzs.my.id
    php -r "require 'vendor/autoload.php'; \\$app = require_once 'bootstrap/app.php'; \\$kernel = \\$app->make(Illuminate\\Contracts\\Console\\Kernel::class); \\$kernel->bootstrap(); 
    
    echo '=== Breakdown status_pelanggan ===' . PHP_EOL;
    \\$statuses = \\App\\Models\\Customer::selectRaw('COALESCE(status_pelanggan, \\'(NULL)\\') as sp, count(*) as cnt')
        ->groupBy('status_pelanggan')
        ->orderByDesc('cnt')
        ->get();
    foreach (\\$statuses as \\$s) {
        echo \\$s->sp . ': ' . \\$s->cnt . PHP_EOL;
    }
    
    echo PHP_EOL . '=== Jatuh Tempo (overdue) per area ===' . PHP_EOL;
    \\$settings = \\App\\Models\\Setting::pluck('value', 'key')->toArray();
    \\$dueDate = \\$settings['global_due_date'] ?? null;
    \\$today = \\Carbon\\Carbon::now();
    
    \\$overdue = \\App\\Models\\Customer::whereIn('status', ['pending', 'nunggak'])
        ->where(function(\\$q) {
            \\$q->where('status_pelanggan', 'Aktif')
              ->orWhereNull('status_pelanggan')
              ->orWhere('status_pelanggan', '');
        })
        ->whereNotIn('area', ['Gratis BC 1', 'Gratis BC 2', 'Gratis BC 3'])
        ->selectRaw('area, count(*) as cnt')
        ->groupBy('area')
        ->get();
    foreach (\\$overdue as \\$o) {
        echo \\$o->area . ': ' . \\$o->cnt . ' jatuh tempo' . PHP_EOL;
    }
    
    echo PHP_EOL . '=== Janji Bayar per area ===' . PHP_EOL;
    \\$jb = \\App\\Models\\Customer::where('status_pelanggan', 'Janji Bayar')
        ->whereNotIn('area', ['Gratis BC 1', 'Gratis BC 2', 'Gratis BC 3'])
        ->selectRaw('area, count(*) as cnt')
        ->groupBy('area')
        ->get();
    foreach (\\$jb as \\$j) {
        echo \\$j->area . ': ' . \\$j->cnt . ' janji bayar' . PHP_EOL;
    }
    
    echo PHP_EOL . '=== Suspend per area ===' . PHP_EOL;
    \\$sus = \\App\\Models\\Customer::whereIn('status_pelanggan', ['Suspend', 'Berhenti sementara', 'Isolir'])
        ->whereNotIn('area', ['Gratis BC 1', 'Gratis BC 2', 'Gratis BC 3'])
        ->selectRaw('area, count(*) as cnt')
        ->groupBy('area')
        ->get();
    foreach (\\$sus as \\$su) {
        echo \\$su->area . ': ' . \\$su->cnt . ' suspend' . PHP_EOL;
    }
    
    echo PHP_EOL . '=== Nonaktif per area ===' . PHP_EOL;
    \\$na = \\App\\Models\\Customer::whereIn('status_pelanggan', ['Nonaktif', 'Berhenti', 'Stop Permanen'])
        ->whereNotIn('area', ['Gratis BC 1', 'Gratis BC 2', 'Gratis BC 3'])
        ->selectRaw('area, count(*) as cnt')
        ->groupBy('area')
        ->get();
    foreach (\\$na as \\$n) {
        echo \\$n->area . ': ' . \\$n->cnt . ' nonaktif' . PHP_EOL;
    }
    "
    `;

    conn.exec(deployCmd, (err, stream) => {
        if (err) throw err;
        stream.on('close', (code, signal) => {
            conn.end();
        }).on('data', (data) => {
            console.log('' + data);
        }).stderr.on('data', (data) => {
            console.log('STDERR: ' + data);
        });
    });
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: 'viruzs123',
    readyTimeout: 99999
});
