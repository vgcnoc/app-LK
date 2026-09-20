const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    
    const deployCmd = `
    cd /var/www/lk.viruzs.my.id
    echo "Current Branch:"
    git branch
    
    echo "Fetching all..."
    git fetch origin
    
    echo "Checkout main..."
    git checkout main || git checkout -b main origin/main
    
    echo "Melakukan git pull di direktori sebenarnya..."
    git reset --hard HEAD
    git pull origin main
    
    echo "Update composer dan npm..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    npm install
    npm run build
    
    echo "Membersihkan cache..."
    php artisan optimize:clear
    
    echo "Fix permissions..."
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    
    echo "Deploy selesai dengan sukses di branch main!"
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
