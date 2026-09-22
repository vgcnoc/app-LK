const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  const cmd = `cd /var/www/lk.viruzs.my.id && php artisan tinker --execute="echo 'Total Customers: ' . \\App\\Models\\Customer::count() . PHP_EOL; echo 'Prorata: ' . \\App\\Models\\Customer::where('status', 'prorata')->count() . PHP_EOL; echo 'Pending: ' . \\App\\Models\\Customer::where('status', 'pending')->count() . PHP_EOL; echo 'Nunggak: ' . \\App\\Models\\Customer::where('status', 'nunggak')->count() . PHP_EOL; echo 'Latest: ' . json_encode(\\App\\Models\\Customer::orderBy('id', 'desc')->take(2)->get(['name','area','status','status_pelanggan'])) . PHP_EOL;"`;
  conn.exec(cmd, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      conn.end();
    }).on('data', (data) => {
      console.log('STDOUT: ' + data);
    }).stderr.on('data', (data) => {
      console.error('STDERR: ' + data);
    });
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123'
});
