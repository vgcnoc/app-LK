const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    
    // Read local files
    const webPhp = fs.readFileSync('c:/xampp/htdocs/app-LK/routes/web.php', 'utf8');
    const billingVue = fs.readFileSync('c:/xampp/htdocs/app-LK/resources/js/Pages/Billing.vue', 'utf8');
    const pelangganVue = fs.readFileSync('c:/xampp/htdocs/app-LK/resources/js/Pages/Pelanggan.vue', 'utf8');
    const pelangganPantauanVue = fs.readFileSync('c:/xampp/htdocs/app-LK/resources/js/Pages/PelangganPantauan.vue', 'utf8');

    const escapeShell = (cmd) => cmd.replace(/(["'$`\\])/g,'\\$1');

    const deployCmd = `
    echo "=== DIRECT FILE UPLOAD (Bypassing GitHub) ==="
    cd /var/www/lk.viruzs.my.id
    
    cat << 'EOF_MARKER' > routes/web.php
${webPhp.replace(/\$/g, '\\$')}
EOF_MARKER

    cat << 'EOF_MARKER' > resources/js/Pages/Billing.vue
${billingVue.replace(/\$/g, '\\$')}
EOF_MARKER

    cat << 'EOF_MARKER' > resources/js/Pages/Pelanggan.vue
${pelangganVue.replace(/\$/g, '\\$')}
EOF_MARKER

    cat << 'EOF_MARKER' > resources/js/Pages/PelangganPantauan.vue
${pelangganPantauanVue.replace(/\$/g, '\\$')}
EOF_MARKER

    echo "Files uploaded successfully."
    
    echo "Running build..."
    npm install xlsx
    npm run build
    php artisan optimize:clear
    systemctl restart php*-fpm 2>/dev/null || echo "No FPM restart"
    
    echo "Direct Deploy Completed!"
    `;

    conn.exec(deployCmd, (err, stream) => {
        if (err) throw err;
        stream.on('close', (code, signal) => {
            console.log('Stream :: close :: code: ' + code);
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
