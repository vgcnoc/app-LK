const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    
    const deployCmd = `
    echo "=== Nginx Config ==="
    cat /etc/nginx/sites-enabled/lk.viruzs.my.id || echo "Config not found"
    
    echo "=== Git Status in /var/www/lk.viruzs.my.id ==="
    cd /var/www/lk.viruzs.my.id
    git log -n 3 --oneline
    
    echo "=== Cek file public/build/manifest.json ==="
    cat public/build/manifest.json | grep "Pelanggan"
    `;

    conn.exec(deployCmd, (err, stream) => {
        if (err) throw err;
        stream.on('close', (code, signal) => {
            console.log('Stream :: close :: code: ' + code + ', signal: ' + signal);
            conn.end();
        }).on('data', (data) => {
            console.log('STDOUT: ' + data);
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
