const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
    conn.sftp((err, sftp) => {
        if (err) throw err;
        sftp.fastPut('c:/xampp/htdocs/app-LK/resources/js/Pages/Billing.vue', '/var/www/lk.viruzs.my.id/resources/js/Pages/Billing.vue', (err) => {
            if (err) throw err;
            conn.exec('cd /var/www/lk.viruzs.my.id && npm run build', (err, stream) => {
                if (err) throw err;
                stream.on('close', () => conn.end())
                      .on('data', (data) => process.stdout.write(''+data))
                      .stderr.on('data', (data) => process.stderr.write(''+data));
            });
        });
    });
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: 'viruzs123',
    readyTimeout: 99999
});
