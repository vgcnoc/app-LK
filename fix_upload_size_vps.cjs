const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    const deployCmd = `
    echo "=== Memperbaiki Batas Ukuran Upload di VPS ==="
    
    echo "1. Memperbarui konfigurasi Nginx (client_max_body_size)..."
    if grep -q "client_max_body_size" /etc/nginx/nginx.conf; then
        sed -i 's/client_max_body_size.*/client_max_body_size 100M;/g' /etc/nginx/nginx.conf
    else
        sed -i '/http {/a \\tclient_max_body_size 100M;' /etc/nginx/nginx.conf
    fi
    
    echo "2. Memperbarui konfigurasi PHP (upload_max_filesize & post_max_size)..."
    PHP_INI=$(find /etc/php -name "php.ini" | grep fpm | head -n 1)
    if [ -n "$PHP_INI" ]; then
        echo "Ditemukan: $PHP_INI"
        sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/g' "$PHP_INI"
        sed -i 's/post_max_size = .*/post_max_size = 100M/g' "$PHP_INI"
    else
        echo "PHP-FPM php.ini tidak ditemukan!"
    fi
    
    echo "3. Me-restart service Nginx dan PHP-FPM..."
    systemctl restart nginx
    
    PHP_FPM_SERVICE=$(systemctl list-units --type=service | grep php | grep fpm | awk '{print $1}')
    if [ -n "$PHP_FPM_SERVICE" ]; then
        echo "Me-restart $PHP_FPM_SERVICE..."
        systemctl restart "$PHP_FPM_SERVICE"
    else
        echo "Service PHP-FPM tidak ditemukan!"
    fi
    
    echo "Selesai! Batas upload sekarang menjadi 100MB."
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
