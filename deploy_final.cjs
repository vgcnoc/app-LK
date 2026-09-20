const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    
    const deployCmd = `
    echo "=== Memulai Update VPS ==="
    cd /var/www/lk.viruzs.my.id
    
    echo "1. Menarik update dari Git..."
    git fetch origin
    git reset --hard HEAD
    git checkout main || git checkout -b main origin/main
    git pull origin main
    
    echo "2. Install dependensi Backend (PHP)..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    
    echo "3. Install & Build dependensi Frontend (Vue)..."
    npm install
    npm run build
    
    echo "4. Membersihkan seluruh Cache (Backend & Frontend)..."
    php artisan optimize:clear
    php artisan view:clear
    php artisan route:clear
    php artisan config:clear
    php artisan cache:clear
    
    echo "5. Restart PHP-FPM (jika ada) untuk membersihkan OPcache..."
    systemctl restart php*-fpm 2>/dev/null || echo "Tidak perlu restart FPM"
    
    echo "6. Fix perizinan file..."
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    
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
