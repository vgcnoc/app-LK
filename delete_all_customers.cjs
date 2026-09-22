const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  // Delete all customers and reset auto-increment
  const cmd = `cd /var/www/lk.viruzs.my.id && php artisan tinker --execute="
    \$count = \\App\\Models\\Customer::count();
    echo 'Deleting ' . \$count . ' customers...' . PHP_EOL;
    \\App\\Models\\Customer::truncate();
    echo 'All customers deleted. Remaining: ' . \\App\\Models\\Customer::count() . PHP_EOL;
  "`;
  conn.exec(cmd, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      console.log('Done. Exit code: ' + code);
      conn.end();
    }).on('data', (data) => {
      console.log('' + data);
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
