const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
    conn.exec('cat /var/www/lk.viruzs.my.id/resources/js/Pages/PelangganInaktif.vue | grep -A 5 -B 5 "isAreaGratis"', (err, stream) => {
        if (err) throw err;
        stream.on('close', () => conn.end())
              .on('data', (data) => console.log(''+data));
    });
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: 'viruzs123',
    readyTimeout: 99999
});
