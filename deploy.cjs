const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    
    const deployCmd = `
    echo "Pindah ke direktori web server..."
    mkdir -p /var/www/html
    cd /var/www/html

    if [ -d "app-LK" ]; then
        echo "Aplikasi app-LK sudah ada. Melakukan git pull..."
        cd app-LK
        git reset --hard HEAD
        git pull origin main
    else
        echo "Melakukan git clone app-LK..."
        git clone https://github.com/vgcnoc/app-LK.git
        cd app-LK
    fi

    echo "Menyiapkan environment..."
    if [ ! -f ".env" ]; then
        cp .env.example .env
        php artisan key:generate
    fi

    echo "Menginstal dependensi backend (Composer)..."
    composer install --no-interaction --prefer-dist --optimize-autoloader

    echo "Menginstal dependensi frontend (Node & Vite)..."
    # Ensure npm and node are installed on VPS, if not they need to be.
    # Assuming they are available.
    npm install
    npm run build

    echo "Menyiapkan database SQLite..."
    touch database/database.sqlite
    php artisan migrate --force

    echo "Mengatur hak akses folder..."
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache || echo "chown www-data failed, skipping..."

    echo "Membersihkan cache..."
    php artisan optimize:clear

    echo "Deploy selesai dengan sukses!"
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
