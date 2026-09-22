const { Client } = require('ssh2');
const fs = require('fs');
const conn = new Client();
conn.on('ready', () => {
    conn.sftp((err, sftp) => {
        if (err) throw err;
        sftp.fastPut('c:/xampp/htdocs/app-LK/test_route_logic.php', '/var/www/lk.viruzs.my.id/test_route_logic.php', (err) => {
            if (err) throw err;
            conn.exec('cd /var/www/lk.viruzs.my.id && php test_route_logic.php', (err, stream) => {
                if (err) throw err;
                stream.on('close', () => conn.end())
                      .on('data', (data) => console.log(''+data));
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
