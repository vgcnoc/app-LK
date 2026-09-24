const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    
    const deployCmd = `
    tail -n 50 /var/www/lk.viruzs.my.id/storage/logs/laravel.log
    `;

    conn.exec(deployCmd, (err, stream) => {
        if (err) throw err;
        stream.on('close', (code, signal) => {
            conn.end();
        }).on('data', (data) => {
            console.log(data.toString());
        }).stderr.on('data', (data) => {
            console.error(data.toString());
        });
    });
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: 'viruzs123',
    readyTimeout: 99999
});
