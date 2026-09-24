const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    
    const deployCmd = `
    echo "=== Menjalankan Update di VPS ==="
    cd /var/www/lk.viruzs.my.id
    
    echo "Merapikan git (reset) dan menarik perubahan terbaru..."
    git pull origin main
    
    echo "Menjalankan migrasi database..."
    php artisan migrate --force
    
    echo "Membangun aset..."
    npm run build
    
    echo "Update VPS selesai 100%!"
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
